<?php

namespace App\Http\Controllers;

use App\Models\FeeStructure;
use App\Models\Institution;
use App\Models\AcademicYear;
use App\Models\ClassModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FeeStructureController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Dropdown data
        if ($user->isSuperAdmin()) {
            $institutions = Institution::orderBy('name')->get();
        } elseif ($user->isStaff()) {
            $institutions = $user->institutions()->orderBy('name')->get();
        } else {
            $institutions = Institution::orderBy('name')->get();
        }
        $academicYears = AcademicYear::orderBy('year_start', 'desc')->get();

        // Base query
        $query = FeeStructure::with(['institution', 'academicYear', 'class']);

        if ($user->isStaff()) {
            $allowedInstitutionIds = $user->institutions()->pluck('institutions.id');
            if ($allowedInstitutionIds->isNotEmpty()) {
                $query->whereIn('institution_id', $allowedInstitutionIds);
            }
        }

        // Apply filters
        if ($request->filled('institution_id')) {
            $query->where('institution_id', $request->institution_id);
        }
        if ($request->filled('academic_year_id')) {
            $query->where('academic_year_id', $request->academic_year_id);
        }

        $feeStructures = $query->orderBy('institution_id')->orderBy('academic_year_id', 'desc')->get();
        
        return view('fee-structures.index', compact('feeStructures', 'institutions', 'academicYears'));
    }

    public function create()
    {
        $user = Auth::user();
        
        if ($user->isSuperAdmin()) {
            $institutions = Institution::all();
            $classes = ClassModel::all();
        } else {
            $institutions = $user->institutions;
            $classes = ClassModel::whereIn('institution_id', $institutions->pluck('id'))->get();
        }
        
        $academicYears = AcademicYear::all();
        
        return view('fee-structures.create', compact('institutions', 'academicYears', 'classes'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        
        // Validasi berdasarkan role
        $validationRules = [
            'academic_year_id' => 'required|exists:academic_years,id',
            'level' => 'required|in:VII,VIII,IX,X,XI,XII',
            'monthly_amount' => 'nullable|numeric|min:0',
            'yearly_amount' => 'nullable|numeric|min:0',
            'scholarship_discount' => 'nullable|numeric|min:0|max:100',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ];
        
        if ($user->isSuperAdmin()) {
            $validationRules['institution_id'] = 'required|exists:institutions,id';
        }
        
        $request->validate($validationRules);
        
        // Validasi: minimal salah satu dari monthly_amount atau yearly_amount harus diisi
        if (!$request->filled('monthly_amount') && !$request->filled('yearly_amount')) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['monthly_amount' => 'Biaya bulanan atau tahunan harus diisi salah satu']);
        }
        
        $data = $request->all();
        
        // Set institution_id berdasarkan role
        if (!$user->isSuperAdmin()) {
            $data['institution_id'] = $user->institutions()->first()->id;
        }
        
        // Auto-select class berdasarkan level dan institution
        if ($request->has('level') && $request->has('institution_id')) {
            $class = ClassModel::where('institution_id', $data['institution_id'])
                ->where('level', $request->level)
                ->first();
            
            if ($class) {
                $data['class_id'] = $class->id;
            } else {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['level' => 'Tidak ada kelas untuk level ' . $request->level . ' di lembaga ini']);
            }
        }
        
        // Auto-kalkulasi biaya bulanan atau tahunan
        if ($request->filled('monthly_amount') && !$request->filled('yearly_amount')) {
            // Jika hanya input bulanan, kalkulasi tahunan
            $data['yearly_amount'] = $request->monthly_amount * 12;
        } elseif ($request->filled('yearly_amount') && !$request->filled('monthly_amount')) {
            // Jika hanya input tahunan, kalkulasi bulanan
            $data['monthly_amount'] = round($request->yearly_amount / 12);
        }
        // Jika keduanya diisi, gunakan nilai yang diinput user
        
        // Set is_active
        $data['is_active'] = $request->has('is_active');
        
        FeeStructure::create($data);

        return redirect()->route('fee-structures.index')->with('success', 'Struktur biaya berhasil ditambahkan');
    }

    public function edit(FeeStructure $feeStructure)
    {
        $user = Auth::user();
        
        // Cek apakah user bisa edit fee structure ini
        if (!$user->isSuperAdmin() && !$user->institutions()->where('id', $feeStructure->institution_id)->exists()) {
            abort(403, 'Anda tidak memiliki akses ke struktur biaya ini');
        }
        
        if ($user->isSuperAdmin()) {
            $institutions = Institution::all();
        } else {
            $institutions = $user->institutions;
        }
        
        $academicYears = AcademicYear::all();
        
        return view('fee-structures.edit', compact('feeStructure', 'institutions', 'academicYears'));
    }
    
    /**
     * Cari struktur biaya berdasarkan level kelas
     */
    public function findByLevel(Request $request)
    {
        $request->validate([
            'institution_id' => 'required|exists:institutions,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'level' => 'required|in:VII,VIII,IX,X,XI,XII'
        ]);
        
        $feeStructure = FeeStructure::findByLevel(
            $request->institution_id,
            $request->academic_year_id,
            $request->level
        );
        
        if (!$feeStructure) {
            return response()->json([
                'success' => false,
                'message' => 'Struktur biaya tidak ditemukan untuk level ' . $request->level
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $feeStructure->id,
                'monthly_amount' => $feeStructure->monthly_amount,
                'yearly_amount' => $feeStructure->yearly_amount,
                'scholarship_discount' => $feeStructure->scholarship_discount,
                'description' => $feeStructure->description,
                'class_name' => $feeStructure->class->class_name,
                'level' => $feeStructure->class->safe_level
            ]
        ]);
    }
    
    /**
     * Cari struktur biaya berdasarkan level (tanpa academic year)
     */
    public function findByLevelOnly(Request $request)
    {
        $request->validate([
            'institution_id' => 'required|exists:institutions,id',
            'level' => 'required|in:VII,VIII,IX,X,XI,XII'
        ]);
        
        $feeStructure = FeeStructure::findByLevelOnly(
            $request->institution_id,
            $request->level
        );
        
        if (!$feeStructure) {
            return response()->json([
                'success' => false,
                'message' => 'Struktur biaya tidak ditemukan untuk level ' . $request->level
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $feeStructure->id,
                'monthly_amount' => $feeStructure->monthly_amount,
                'yearly_amount' => $feeStructure->yearly_amount,
                'scholarship_discount' => $feeStructure->scholarship_discount,
                'description' => $feeStructure->description,
                'class_name' => $feeStructure->class->class_name,
                'level' => $feeStructure->class->safe_level
            ]
        ]);
    }

    public function update(Request $request, FeeStructure $feeStructure)
    {
        $user = Auth::user();
        
        // Cek apakah user bisa update fee structure ini
        if (!$user->isSuperAdmin() && !$user->institutions()->where('id', $feeStructure->institution_id)->exists()) {
            abort(403, 'Anda tidak memiliki akses ke struktur biaya ini');
        }
        
        // Validasi berdasarkan role
        $validationRules = [
            'academic_year_id' => 'required|exists:academic_years,id',
            'level' => 'required|in:VII,VIII,IX,X,XI,XII',
            'monthly_amount' => 'nullable|numeric|min:0',
            'yearly_amount' => 'nullable|numeric|min:0',
            'scholarship_discount' => 'nullable|numeric|min:0|max:100',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ];
        
        if ($user->isSuperAdmin()) {
            $validationRules['institution_id'] = 'required|exists:institutions,id';
        }
        
        $request->validate($validationRules);
        
        // Validasi: minimal salah satu dari monthly_amount atau yearly_amount harus diisi
        if (!$request->filled('monthly_amount') && !$request->filled('yearly_amount')) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['monthly_amount' => 'Biaya bulanan atau tahunan harus diisi salah satu']);
        }
        
        $data = $request->all();
        
        // Set institution_id berdasarkan role
        if (!$user->isSuperAdmin()) {
            $data['institution_id'] = $user->institutions()->first()->id;
        }
        
        // Auto-select class berdasarkan level dan institution
        if ($request->has('level') && $request->has('institution_id')) {
            $class = ClassModel::where('institution_id', $data['institution_id'])
                ->where('level', $request->level)
                ->first();
            
            if ($class) {
                $data['class_id'] = $class->id;
            } else {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['level' => 'Tidak ada kelas untuk level ' . $request->level . ' di lembaga ini']);
            }
        }
        
        // Auto-kalkulasi biaya bulanan atau tahunan
        if ($request->filled('monthly_amount') && !$request->filled('yearly_amount')) {
            // Jika hanya input bulanan, kalkulasi tahunan
            $data['yearly_amount'] = $request->monthly_amount * 12;
        } elseif ($request->filled('yearly_amount') && !$request->filled('monthly_amount')) {
            // Jika hanya input tahunan, kalkulasi bulanan
            $data['monthly_amount'] = round($request->yearly_amount / 12);
        }
        // Jika keduanya diisi, gunakan nilai yang diinput user
        
        // Set is_active
        $data['is_active'] = $request->has('is_active');
        
        $feeStructure->update($data);

        return redirect()->route('fee-structures.index')->with('success', 'Struktur biaya berhasil diperbarui');
    }

    public function destroy(FeeStructure $feeStructure)
    {
        $user = Auth::user();
        
        // Cek apakah user bisa hapus fee structure ini
        if (!$user->isSuperAdmin() && !$user->institutions()->where('id', $feeStructure->institution_id)->exists()) {
            abort(403, 'Anda tidak memiliki akses ke struktur biaya ini');
        }
        
        $feeStructure->delete();

        return redirect()->route('fee-structures.index')->with('success', 'Struktur biaya berhasil dihapus');
    }

    public function show(FeeStructure $feeStructure)
    {
        $user = Auth::user();
        
        // Cek apakah user bisa lihat fee structure ini
        if (!$user->isSuperAdmin() && !$user->institutions()->where('id', $feeStructure->institution_id)->exists()) {
            abort(403, 'Anda tidak memiliki akses ke struktur biaya ini');
        }
        
        return view('fee-structures.show', compact('feeStructure'));
    }
    
    /**
     * Buat struktur biaya untuk semua level dalam satu lembaga
     */
    public function createForAllLevels(Request $request)
    {
        $request->validate([
            'institution_id' => 'required|exists:institutions,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'base_monthly_amount' => 'required|numeric|min:0',
            'description' => 'nullable|string'
        ]);
        
        $institution = Institution::findOrFail($request->institution_id);
        $levels = ['VII', 'VIII', 'IX', 'X', 'XI', 'XII'];
        $created = 0;
        $errors = [];
        
        foreach ($levels as $level) {
            try {
                // Cek apakah sudah ada struktur biaya untuk level ini
                $existingFeeStructure = FeeStructure::where('institution_id', $request->institution_id)
                    ->where('academic_year_id', $request->academic_year_id)
                    ->whereHas('class', function($query) use ($level) {
                        $query->where('level', $level);
                    })
                    ->first();
                
                if ($existingFeeStructure) {
                    $errors[] = "Struktur biaya untuk level {$level} sudah ada";
                    continue;
                }
                
                // Ambil kelas pertama dengan level yang sesuai
                $class = ClassModel::where('institution_id', $request->institution_id)
                    ->where('level', $level)
                    ->first();
                
                if (!$class) {
                    $errors[] = "Tidak ada kelas untuk level {$level} di {$institution->name}";
                    continue;
                }
                
                // Hitung biaya berdasarkan level
                $monthlyAmount = $this->calculateMonthlyAmountByLevel($level, $request->base_monthly_amount);
                $yearlyAmount = $monthlyAmount * 12;
                
                // Calculate smart distribution
                $smartDistribution = \App\Models\FeeStructure::calculateSmartMonthlyDistribution($yearlyAmount);
                
                FeeStructure::create([
                    'institution_id' => $request->institution_id,
                    'academic_year_id' => $request->academic_year_id,
                    'class_id' => $class->id,
                    'monthly_amount' => $monthlyAmount,
                    'yearly_amount' => $yearlyAmount,
                    'scholarship_discount' => 0,
                    'description' => $request->description ?: "Struktur biaya untuk tingkat {$level}",
                    'is_active' => true,
                ]);
                
                $created++;
                
            } catch (\Exception $e) {
                $errors[] = "Error membuat struktur biaya untuk level {$level}: " . $e->getMessage();
            }
        }
        
        $message = "Berhasil membuat {$created} struktur biaya berdasarkan level.";
        if (!empty($errors)) {
            $message .= " Errors: " . implode(', ', $errors);
        }
        
        return redirect()->route('fee-structures.index')
            ->with('success', $message)
            ->with('errors', $errors);
    }
    
    /**
     * Hitung biaya bulanan berdasarkan level
     */
    private function calculateMonthlyAmountByLevel($level, $baseAmount)
    {
        switch ($level) {
            case 'VII':
            case 'X':
                return $baseAmount; // Tingkat 1
            case 'VIII':
            case 'XI':
                return $baseAmount * 1.1; // Tingkat 2: +10%
            case 'IX':
            case 'XII':
                return $baseAmount * 1.2; // Tingkat 3: +20%
            default:
                return $baseAmount;
        }
    }

    /**
     * Salin struktur biaya dari tahun ajaran sebelumnya ke tahun ajaran target
     * - Menyalin per level (berdasarkan level kelas pada fee structure sumber)
     * - Hanya membuat jika belum ada pada tahun ajaran target
     */
    public function copyFromPreviousYear(Request $request)
    {
        $user = Auth::user();
        
        $request->validate([
            'institution_id' => 'required|exists:institutions,id',
            'source_academic_year_id' => 'required|exists:academic_years,id',
            'target_academic_year_id' => 'required|exists:academic_years,id',
        ]);
        
        if ($request->source_academic_year_id == $request->target_academic_year_id) {
            return redirect()->back()->with('success', 'Sumber dan target tahun ajaran tidak boleh sama.');
        }
        
        // Batasi hak akses untuk admin lembaga
        if (!$user->isSuperAdmin() && !$user->institutions()->where('id', $request->institution_id)->exists()) {
            abort(403, 'Anda tidak memiliki akses ke lembaga ini');
        }
        
        $sourceFeeStructures = FeeStructure::with('class')
            ->where('institution_id', $request->institution_id)
            ->where('academic_year_id', $request->source_academic_year_id)
            ->get();
        
        if ($sourceFeeStructures->isEmpty()) {
            return redirect()->back()->with('success', 'Tidak ada struktur biaya pada tahun ajaran sumber untuk disalin.');
        }
        
        $created = 0;
        $skipped = [];
        
        foreach ($sourceFeeStructures as $src) {
            $level = optional($src->class)->safe_level;
            if (!$level) {
                $skipped[] = 'Level tidak diketahui untuk kelas ID '.$src->class_id;
                continue;
            }
            
            // Cek apakah sudah ada di target
            $exists = FeeStructure::where('institution_id', $request->institution_id)
                ->where('academic_year_id', $request->target_academic_year_id)
                ->whereHas('class', function($q) use ($level) {
                    $q->where('level', $level);
                })
                ->exists();
            
            if ($exists) {
                $skipped[] = "Lewati level {$level} (sudah ada)";
                continue;
            }
            
            // Temukan kelas target dengan level sama pada tahun ajaran target
            $targetClass = ClassModel::where('institution_id', $request->institution_id)
                ->where('academic_year_id', $request->target_academic_year_id)
                ->where('level', $level)
                ->first();
            
            if (!$targetClass) {
                $skipped[] = "Tidak ada kelas level {$level} di tahun ajaran target";
                continue;
            }
            
            FeeStructure::create([
                'institution_id' => $request->institution_id,
                'academic_year_id' => $request->target_academic_year_id,
                'class_id' => $targetClass->id,
                'monthly_amount' => $src->monthly_amount,
                'yearly_amount' => $src->yearly_amount,
                'scholarship_discount' => $src->scholarship_discount,
                'description' => $src->description ?: 'Disalin dari tahun sebelumnya',
                'is_active' => true,
            ]);
            
            $created++;
        }
        
        $message = "Disalin: {$created}.";
        if (!empty($skipped)) {
            $message .= ' Terlewati: '.implode('; ', $skipped);
        }
        
        return redirect()->route('fee-structures.index')->with('success', $message);
    }
}
