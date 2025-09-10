<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Institution;
use App\Models\AcademicYear;
use App\Models\ClassModel;
use App\Models\ScholarshipCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\FeeStructure;
use Carbon\Carbon;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Get filter parameters
        $institutionId = $request->get('institution_id');
        $classId = $request->get('class_id');
        $academicYearId = $request->get('academic_year_id');
        $search = $request->get('search');
        
        // Build query
        $query = Student::with(['institution', 'academicYear', 'classRoom', 'scholarshipCategory']);
        
        // Apply role-based filtering
        if ($user->isSuperAdmin()) {
            // Super Admin can see all data
        } elseif ($user->isStaff()) {
            $allowedInstitutionIds = $user->institutions()->pluck('institutions.id');
            $query->whereIn('institution_id', $allowedInstitutionIds);
        }
        
        // Apply filters
        if ($institutionId) {
            $query->where('institution_id', $institutionId);
        }
        
        if ($classId) {
            $query->where('class_id', $classId);
        }
        
        if ($academicYearId) {
            $query->where('academic_year_id', $academicYearId);
        }
        
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('nis', 'like', "%{$search}%");
            });
        }
        
        // Get paginated results with custom pagination settings
        $students = $query->paginate(10)->onEachSide(1);
        
        // Ensure all students have billing records
        foreach ($students as $student) {
            $this->ensureBillingRecords($student);
        }
        
        // Get filter options
        if ($user->isSuperAdmin()) {
            $institutions = Institution::all();
            // For super admin, show classes based on selected institution or all if none selected
            if ($institutionId) {
                $classes = ClassModel::where('institution_id', $institutionId)->get();
            } else {
                $classes = collect(); // Empty collection initially
            }
        } else {
            $institutions = $user->institutions;
            // For staff, show classes based on selected institution or their allowed institutions
            if ($institutionId) {
                $classes = ClassModel::where('institution_id', $institutionId)->get();
            } else {
                $classes = ClassModel::whereIn('institution_id', $institutions->pluck('id'))->get();
            }
        }
        
        $academicYears = AcademicYear::all();
        
        return view('students.index', compact('students', 'institutions', 'classes', 'academicYears'));
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
        $scholarshipCategories = ScholarshipCategory::all();
        
        return view('students.create', compact('institutions', 'academicYears', 'classes', 'scholarshipCategories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nis' => 'required|string|max:20|unique:students',
            'name' => 'required|string|max:255',
            'institution_id' => 'required|exists:institutions,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'class_id' => 'required|exists:classes,id',
            'scholarship_category_id' => 'nullable|exists:scholarship_categories,id',
            'parent_name' => 'nullable|string|max:255',
            'parent_phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'previous_debt' => 'nullable|numeric|min:0',
            'previous_debt_year' => 'nullable|string|max:4|regex:/^[0-9]{4}$/',
        ]);

        // Cross-field validation: class must belong to same institution & academic year
        $class = ClassModel::find($request->class_id);
        if ($class) {
            if ((int)$class->institution_id !== (int)$request->institution_id) {
                return back()->withInput()->withErrors(['class_id' => 'Kelas yang dipilih tidak sesuai dengan Lembaga siswa.']);
            }
            if ((int)$class->academic_year_id !== (int)$request->academic_year_id) {
                return back()->withInput()->withErrors(['class_id' => 'Kelas yang dipilih tidak sesuai dengan Tahun Ajaran siswa.']);
            }
        }

        Student::create($request->all());

        return redirect()->route('students.index')->with('success', 'Siswa berhasil ditambahkan');
    }

    /**
     * Display the specified student
     */
    public function show(Student $student)
    {
        // Ensure billing records exist
        $this->ensureBillingRecords($student);

        // Load complete student data with relationships for real-time sync
        $student->load([
            'institution', 'academicYear', 'classRoom', 'scholarshipCategory',
            'billingRecords' => function($query) {
                $query->orderBy('created_at', 'desc');
            },
            'payments' => function($query) {
                $query->orderBy('payment_date', 'desc');
            }
        ]);

        return view('students.show', compact('student'));
    }

    public function print(Student $student)
    {
        // Pastikan record tagihan tahunan dibuat jika belum ada
        $this->ensureBillingRecords($student);
        
        $student->load([
            'institution', 'academicYear', 'classRoom', 'scholarshipCategory',
            'billingRecords' => function($q){
                $q->orderBy('created_at', 'desc');
            },
            'payments' => function($q){
                $q->orderBy('payment_date', 'desc');
            }
        ]);
        $html = view('students.print', compact('student'))->render();
        
        $pdf = \PDF::loadHTML($html);
        $filename = 'student_' . $student->nis . '.pdf';
        return $pdf->stream($filename);
    }

    public function edit(Student $student)
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
        $scholarshipCategories = ScholarshipCategory::all();
        
        return view('students.edit', compact('student', 'institutions', 'academicYears', 'classes', 'scholarshipCategories'));
    }

    public function update(Request $request, Student $student)
    {
        $request->validate([
            'nis' => 'required|string|max:20|unique:students,nis,' . $student->id,
            'name' => 'required|string|max:255',
            'institution_id' => 'required|exists:institutions,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'class_id' => 'required|exists:classes,id',
            'scholarship_category_id' => 'nullable|exists:scholarship_categories,id',
            'parent_name' => 'nullable|string|max:255',
            'parent_phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'previous_debt' => 'nullable|numeric|min:0',
            'previous_debt_year' => 'nullable|string|max:4|regex:/^[0-9]{4}$/',
            'credit_balance' => 'nullable|numeric|min:0',
            'credit_balance_year' => 'nullable|string|max:4|regex:/^[0-9]{4}$/',
        ]);

        // Cross-field validation: class must belong to same institution & academic year
        $class = ClassModel::find($request->class_id);
        if ($class) {
            if ((int)$class->institution_id !== (int)$request->institution_id) {
                return back()->withInput()->withErrors(['class_id' => 'Kelas yang dipilih tidak sesuai dengan Lembaga siswa.']);
            }
            if ((int)$class->academic_year_id !== (int)$request->academic_year_id) {
                return back()->withInput()->withErrors(['class_id' => 'Kelas yang dipilih tidak sesuai dengan Tahun Ajaran siswa.']);
            }
        }

        $student->update($request->all());

        return redirect()->route('students.index')->with('success', 'Data siswa berhasil diperbarui');
    }

    public function destroy(Student $student)
    {
        $student->delete();

        return redirect()->route('students.index')->with('success', 'Data siswa berhasil dihapus');
    }

    /**
     * Auto-generate billing records for students who don't have them
     */
    public function ensureBillingRecords($student)
    {
        // Check if student already has an annual billing record for CURRENT academic year
        $currentYearName = optional($student->academicYear) ? ($student->academicYear->year_start.'-'.$student->academicYear->year_end) : null;
        $existingBilling = $student->billingRecords()
            ->where('notes', 'ANNUAL')
            ->when($currentYearName, function($q) use ($currentYearName){
                $q->where('origin_year', $currentYearName)->orWhere('origin_year', str_replace('-', '/', $currentYearName));
            })
            ->first();
        
        if (!$existingBilling) {
            // Get the student's class level (prefer safe_level)
            $level = $student->classRoom ? ($student->classRoom->safe_level ?? $student->classRoom->level) : 'VII';
            
            // Use the FeeStructure model's findByLevel method
            $feeStructure = FeeStructure::findByLevel(
                $student->institution_id,
                $student->academic_year_id,
                $level
            );
            
            // Fallback: if not found by safe_level, try raw level
            if (!$feeStructure && $student->classRoom && $student->classRoom->level) {
                $feeStructure = FeeStructure::findByLevel(
                    $student->institution_id,
                    $student->academic_year_id,
                    $student->classRoom->level
                );
            }
            
            if (!$feeStructure) {
                // As a last resort, try any class_id already linked on fee structures for this AY & institution
                $feeStructure = FeeStructure::where('institution_id', $student->institution_id)
                    ->where('academic_year_id', $student->academic_year_id)
                    ->whereHas('class', function($q) use ($level){
                        $q->where('level', $level);
                    })
                    ->first();
            }

            if ($feeStructure) {
                // Get academic year info
                $academicYear = $student->academicYear;
                $dueDate = $academicYear ? Carbon::create($academicYear->year_end, 12, 31) : now()->addYear();
                
                // Create annual billing record with all required fields
                $student->billingRecords()->create([
                    'fee_structure_id' => $feeStructure->id,
                    'origin_year' => $academicYear ? $academicYear->getNameAttribute() : date('Y'),
                    'origin_class' => $student->classRoom ? $student->classRoom->class_name : 'Unknown',
                    'amount' => $feeStructure->yearly_amount,
                    'remaining_balance' => $feeStructure->yearly_amount, // Initially same as amount
                    'status' => 'active',
                    'due_date' => $dueDate,
                    'billing_month' => 'ANNUAL',
                    'notes' => 'ANNUAL',
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                
                \Log::info("Auto-generated billing record for student {$student->id}: Rp " . number_format($feeStructure->yearly_amount, 0, ',', '.'));
            } else {
                \Log::warning("No fee structure found for student {$student->id} (Level: {$level}, Institution: {$student->institution_id}, Academic Year: {$student->academic_year_id})");
            }
        }
    }

    /**
     * Display a listing of students for staff/kasir
     */
    public function staffIndex(Request $request)
    {
        $user = Auth::user();
        if (!in_array($user->role, ['staff', 'kasir'])) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini');
        }

        $institutionId = $request->get('institution_id');
        $classId = $request->get('class_id');
        $academicYearId = $request->get('academic_year_id');
        $search = $request->get('search');

        // Build query with all necessary relationships
        $query = Student::with(['institution', 'academicYear', 'classRoom', 'scholarshipCategory', 'billingRecords'])
            ->whereIn('institution_id', $user->institutions()->pluck('institutions.id'));

        // Apply filters
        if ($institutionId) {
            $query->where('institution_id', $institutionId);
        }
        if ($classId) {
            $query->where('class_id', $classId);
        }
        if ($academicYearId) {
            $query->where('academic_year_id', $academicYearId);
        }
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('nis', 'like', "%{$search}%");
            });
        }

        // Get paginated results
        $students = $query->paginate(15)->onEachSide(1);
        
        // Ensure all students have billing records
        foreach ($students as $student) {
            $this->ensureBillingRecords($student);
        }

        $institutions = $user->institutions;
        $classes = ClassModel::whereIn('institution_id', $institutions->pluck('id'))->get();
        $academicYears = AcademicYear::all();

        return view('staff.students.index', compact('students', 'institutions', 'classes', 'academicYears'));
    }

    /**
     * Display the specified student for staff/kasir
     */
    public function staffShow(Student $student)
    {
        $user = Auth::user();
        if (!in_array($user->role, ['staff', 'kasir'])) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini');
        }

        // Check if user has access to this student's institution
        if (!$user->institutions()->where('institutions.id', $student->institution_id)->exists()) {
            abort(403, 'Anda tidak memiliki akses ke data siswa ini');
        }

        // Ensure billing records exist
        $this->ensureBillingRecords($student);

        // Load student with relationships
        $student->load([
            'institution', 'academicYear', 'classRoom', 'scholarshipCategory',
            'billingRecords' => function($query) {
                $query->orderBy('created_at', 'desc');
            },
            'payments' => function($query) {
                $query->orderBy('payment_date', 'desc');
            }
        ]);

        return view('staff.students.show', compact('student'));
    }

    /**
     * Calculate real-time billing data for student
     */
    private function calculateRealTimeBillingData($student)
    {
        // Get annual billing record
        $annualBilling = $student->billingRecords->where('notes', 'ANNUAL')->first();
        $yearlyAmount = $annualBilling ? $annualBilling->amount : 0;
        
        // Use smart calculation instead of simple division
        $smartDistribution = $yearlyAmount > 0 ? 
            \App\Models\FeeStructure::calculateSmartMonthlyDistribution($yearlyAmount) : 
            ['monthly_breakdown' => []];
        
        // Calculate total verified payments - include both 'verified' and 'completed' statuses
        $totalPayments = $student->payments->whereIn('status', ['verified', 'completed'])->sum('total_amount');
        
        // Calculate previous debt
        $previousDebt = $student->previous_debt ?? 0;
        $totalObligation = $yearlyAmount + $previousDebt;
        
        // Calculate remaining balance and detect overpayment
        $remainingBalance = max(0, $totalObligation - $totalPayments);
        $overpayment = max(0, $totalPayments - $totalObligation);
        
        // Update credit balance if there's overpayment
        if ($overpayment > 0 && $student->credit_balance != $overpayment) {
            $currentYear = $student->academicYear->year_start ?? date('Y');
            $student->update([
                'credit_balance' => $overpayment,
                'credit_balance_year' => $currentYear
            ]);
        }
        
        // Calculate payment allocation for months using smart distribution
        $months = ['Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember', 
                 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni'];
        
        $monthlyData = [];
        $monthlyBreakdown = $smartDistribution['monthly_breakdown'] ?? [];
        
        foreach($months as $index => $month) {
            // Get monthly amount from smart distribution
            $monthlyRequired = $monthlyBreakdown[$month] ?? 0;
            $monthlyPaid = 0;
            
            // Calculate payment allocation for this month
            if ($totalPayments > $previousDebt) {
                $availableForMonths = $totalPayments - $previousDebt;
                
                // Calculate cumulative required amount up to this month
                $cumulativeRequired = 0;
                for ($i = 0; $i <= $index; $i++) {
                    $cumulativeRequired += $monthlyBreakdown[$months[$i]] ?? 0;
                }
                
                // Calculate how much has been paid for previous months
                $previousMonthsPaid = 0;
                for ($i = 0; $i < $index; $i++) {
                    $previousMonthsPaid += $monthlyBreakdown[$months[$i]] ?? 0;
                }
                
                // Calculate payment for this month
                $availableForThisMonth = max(0, $availableForMonths - $previousMonthsPaid);
                $monthlyPaid = min($monthlyRequired, $availableForThisMonth);
            }
            
            $monthlyRemaining = max(0, $monthlyRequired - $monthlyPaid);
            $isPaid = $monthlyRemaining == 0;
            
            $monthlyData[$month] = [
                'required' => $monthlyRequired,
                'paid' => $monthlyPaid,
                'remaining' => $monthlyRemaining,
                'isPaid' => $isPaid
            ];
        }
        
        return [
            'yearlyAmount' => $yearlyAmount,
            'smartDistribution' => $smartDistribution,
            'totalPayments' => $totalPayments,
            'previousDebt' => $previousDebt,
            'totalObligation' => $totalObligation,
            'remainingBalance' => $remainingBalance,
            'overpayment' => $overpayment,
            'creditBalance' => $student->credit_balance ?? 0,
            'creditBalanceYear' => $student->credit_balance_year,
            'monthlyData' => $monthlyData,
            'months' => $months
        ];
    }

    public function importTemplate()
    {
        $user = Auth::user();
        
        // Data untuk dropdown di template - hanya yang aktif
        $academicYears = AcademicYear::where('status', 'active')->get();
        $scholarshipCategories = ScholarshipCategory::where('is_active', true)->get();
        
        if ($user->isSuperAdmin()) {
            $institutions = Institution::where('is_active', true)->get();
            $classes = ClassModel::where('is_active', true)->get();
        } else {
            $institutions = $user->institutions->where('is_active', true);
            $classes = ClassModel::whereIn('institution_id', $institutions->pluck('id'))
                ->where('is_active', true)
                ->get();
        }

        return view('students.import-template', compact('institutions', 'academicYears', 'classes', 'scholarshipCategories'));
    }

    public function downloadTemplate()
    {
        $user = Auth::user();
        
        // Data untuk template - hanya yang aktif
        $academicYears = AcademicYear::where('status', 'active')->get();
        $scholarshipCategories = ScholarshipCategory::where('is_active', true)->get();
        
        if ($user->isSuperAdmin()) {
            $institutions = Institution::where('is_active', true)->get();
            $classes = ClassModel::where('is_active', true)->get();
        } else {
            $institutions = $user->institutions->where('is_active', true);
            $classes = ClassModel::whereIn('institution_id', $institutions->pluck('id'))
                ->where('is_active', true)
                ->get();
        }

        // Buat Excel dengan PhpSpreadsheet
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        
        // Sheet 1: Template Data Siswa
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template Data Siswa');
        
        // Header (dengan kolom ID)
        $headers = [
            'NIS*',               // A
            'Nama Lengkap*',      // B
            'Lembaga ID',         // C
            'Tahun Ajaran ID',    // D
            'Kelas ID',           // E
            'Email',              // F
            'No. HP',             // G
            'Alamat',             // H
            'Nama Orang Tua',     // I
            'No. HP Orang Tua',   // J
            'Kategori Beasiswa'   // K (nama, opsional)
        ];
        
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $sheet->getColumnDimension($col)->setAutoSize(true);
            $col++;
        }
        
        // Prefill Lembaga ID untuk admin lembaga
        if ($user->isSuperAdmin()) {
            // Isi 100 baris contoh
            for ($row = 2; $row <= 201; $row++) {
                $sheet->setCellValue('C' . $row, $institutions->first()->id);
            }
        }
        
        // Tidak lagi memakai dropdown Data Validation untuk kolom ID agar semua bisa diinput bebas
        
        // Sheet 2: Petunjuk
        $this->createInstructionsSheet($spreadsheet, $user);
        
        // Sheet 3: Data Referensi
        $this->createReferenceSheet($spreadsheet, $institutions, $academicYears, $classes, $scholarshipCategories);
        
        // Output Excel
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $filename = 'template_import_siswa.xlsx';
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        
        $writer->save('php://output');
        exit;
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls',
        ]);

        $user = Auth::user();
        $file = $request->file('file');
        
        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getPathname());
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();
            
            // Skip header row
            array_shift($rows);
            
            $imported = 0;
            $errors = [];
            
            foreach ($rows as $index => $row) {
                if (empty(array_filter($row))) continue; // Skip empty rows
                
                try {
                    $this->importStudentRow($row, $user, $index + 2, $errors);
                    $imported++;
                } catch (\Exception $e) {
                    $errors[] = "Baris " . ($index + 2) . ": " . $e->getMessage();
                }
            }
            
            if (empty($errors)) {
                return redirect()->route('students.index')
                    ->with('success', "Berhasil import {$imported} data siswa");
            } else {
                return redirect()->route('students.index')
                    ->with('warning', "Import selesai dengan {$imported} data berhasil, namun ada beberapa error")
                    ->with('import_errors', $errors);
            }
            
        } catch (\Exception $e) {
            return redirect()->route('students.index')
                ->with('error', 'Error saat membaca file: ' . $e->getMessage());
        }
    }

    private function addDataValidation($sheet, $institutions, $academicYears, $classes, $scholarshipCategories)
    {
        // Buat validasi list berdasarkan sheet Data Referensi
        $dvFactory = function(string $formula) {
            $dv = new \PhpOffice\PhpSpreadsheet\Cell\DataValidation();
            $dv->setType( \PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST );
            $dv->setErrorStyle( \PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_STOP );
            $dv->setAllowBlank(true);
            $dv->setShowInputMessage(true);
            $dv->setShowErrorMessage(true);
            $dv->setShowDropDown(true);
            $dv->setErrorTitle('Input tidak valid');
            $dv->setError('Pilih nilai dari daftar.');
            $dv->setFormula1($formula);
            return $dv;
        };

        // Tentukan rentang data pada sheet referensi (ID saja)
        $instCount = max(1, $institutions->count());
        $yearCount = max(1, $academicYears->count());
        $classCount = max(1, $classes->count());
        
        $instRange = "'Data Referensi'!A3:A" . (2 + $instCount);
        $yearRange = "'Data Referensi'!D3:D" . (2 + $yearCount);
        $classRange = "'Data Referensi'!G3:G" . (2 + $classCount);

        // Terapkan ke 100 baris (2..101)
        for ($row = 2; $row <= 201; $row++) {
            // Lembaga ID di kolom C
            $dvInst = $dvFactory($instRange);
            $sheet->getCell('C' . $row)->setDataValidation($dvInst);

            // Tahun Ajaran ID di kolom D
            $dvYear = $dvFactory($yearRange);
            $sheet->getCell('D' . $row)->setDataValidation($dvYear);

            // Kelas ID di kolom E
            $dvClass = $dvFactory($classRange);
            $sheet->getCell('E' . $row)->setDataValidation($dvClass);
        }
    }

    private function createInstructionsSheet($spreadsheet, $user)
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Petunjuk');
        
        $instructions = [
            ['PETUNJUK IMPORT DATA SISWA'],
            [''],
            ['1. PERSIAPAN:'],
            ['   - Pastikan file Excel menggunakan format .xlsx'],
            ['   - Jangan mengubah struktur kolom atau header'],
            ['   - Kolom dengan tanda * adalah wajib diisi'],
            [''],
            ['2. PENGISIAN DATA:'],
            ['   - NIS: Nomor Induk Siswa (unik, tidak boleh duplikat)'],
            ['   - Nama Lengkap: Nama lengkap siswa'],
            ['   - Lembaga ID: isi angka ID sesuai sheet Data Referensi (kolom A)'],
            ['   - Tahun Ajaran ID: isi angka ID sesuai sheet Data Referensi (kolom D)'],
            ['   - Kelas ID: isi angka ID sesuai sheet Data Referensi (kolom G)'],
            ['   - Email: Email siswa (opsional)'],
            ['   - No. HP: Nomor HP siswa (opsional)'],
            ['   - Alamat: Alamat lengkap siswa (opsional)'],
            ['   - Nama Orang Tua: Nama orang tua (opsional)'],
            ['   - No. HP Orang Tua: No HP orang tua (opsional)'],
            ['   - Kategori Beasiswa: tulis nama kategori (opsional), referensi tersedia'],
            [''],
            ['3. KETENTUAN KHUSUS:'],
            ['   - Lembaga/Kelas/Tahun Ajaran: gunakan ID dari sheet Data Referensi'],
            [''],
            ['4. VALIDASI:'],
            ['   - NIS harus unik'],
            ['   - Email harus valid (jika diisi)'],
            ['   - No. HP harus valid (jika diisi)'],
            [''],
            ['5. CATATAN:'],
            ['   - Data yang berhasil diimport akan otomatis terdaftar'],
            ['   - Jika ada error, periksa log error di bawah'],
            ['   - Backup data sebelum melakukan import besar-besaran']
        ];
        
        foreach ($instructions as $rowIndex => $instruction) {
            $sheet->setCellValue('A' . ($rowIndex + 1), $instruction[0]);
        }
        
        $sheet->getColumnDimension('A')->setWidth(80);
    }

    private function createReferenceSheet($spreadsheet, $institutions, $academicYears, $classes, $scholarshipCategories)
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Data Referensi');
        
        // Data Lembaga
        $sheet->setCellValue('A1', 'LEMBAGA');
        $sheet->setCellValue('A2', 'ID');
        $sheet->setCellValue('B2', 'Nama Lembaga');
        
        $row = 3;
        foreach ($institutions as $institution) {
            $sheet->setCellValue('A' . $row, $institution->id);
            $sheet->setCellValue('B' . $row, $institution->name);
            $row++;
        }
        
        // Data Tahun Ajaran
        $sheet->setCellValue('D1', 'TAHUN AJARAN');
        $sheet->setCellValue('D2', 'ID');
        $sheet->setCellValue('E2', 'Tahun Ajaran');
        
        $row = 3;
        foreach ($academicYears as $academicYear) {
            $sheet->setCellValue('D' . $row, $academicYear->id);
            $sheet->setCellValue('E' . $row, $academicYear->year_start . '-' . $academicYear->year_end);
            $row++;
        }
        
        // Data Kelas
        $sheet->setCellValue('G1', 'KELAS');
        $sheet->setCellValue('G2', 'ID');
        $sheet->setCellValue('H2', 'Nama Kelas');
        $sheet->setCellValue('I2', 'Lembaga ID');
        
        $row = 3;
        foreach ($classes as $class) {
            $sheet->setCellValue('G' . $row, $class->id);
            $sheet->setCellValue('H' . $row, $class->class_name);
            $sheet->setCellValue('I' . $row, $class->institution_id);
            $row++;
        }
        
        // Data Kategori Beasiswa
        $sheet->setCellValue('K1', 'KATEGORI BEASISWA');
        $sheet->setCellValue('K2', 'ID');
        $sheet->setCellValue('L2', 'Nama Kategori');
        $sheet->setCellValue('M2', 'Diskon (%)');
        
        $row = 3;
        foreach ($scholarshipCategories as $category) {
            $sheet->setCellValue('K' . $row, $category->id);
            $sheet->setCellValue('L' . $row, $category->name);
            $sheet->setCellValue('M' . $row, $category->discount_percentage);
            $row++;
        }
        
        // Auto-size columns
        foreach (['A', 'B', 'D', 'E', 'G', 'H', 'I', 'K', 'L', 'M'] as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    private function importStudentRow($row, $user, $rowNumber, &$errors)
    {
        // Validasi data row minimal
        if (empty($row[0]) || empty($row[1])) {
            throw new \Exception('NIS dan Nama Lengkap wajib diisi');
        }
        
        $nis = trim($row[0]);
        $name = trim($row[1]);
        $institutionIdFromFile = isset($row[2]) ? trim($row[2]) : null;
        $academicYearIdFromFile = isset($row[3]) ? trim($row[3]) : null;
        $classIdFromFile = isset($row[4]) ? trim($row[4]) : null;
        $email = trim($row[5] ?? '');
        $phone = trim($row[6] ?? '');
        $address = trim($row[7] ?? '');
        $parentName = trim($row[8] ?? '');
        $parentPhone = trim($row[9] ?? '');
        $scholarshipCategoryName = trim($row[10] ?? '');
        
        if (Student::where('nis', $nis)->exists()) {
            throw new \Exception("NIS '{$nis}' sudah ada");
        }
        if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \Exception("Email '{$email}' tidak valid");
        }
        
        $scholarshipCategoryId = null;
        if ($scholarshipCategoryName !== '') {
            // Terima baik ID maupun Nama pada kolom Kategori Beasiswa
            $category = null;
            if (is_numeric($scholarshipCategoryName)) {
                $category = ScholarshipCategory::find((int)$scholarshipCategoryName);
            }
            if (!$category) {
                $category = ScholarshipCategory::where('name', $scholarshipCategoryName)->first();
            }
            if ($category) {
                $scholarshipCategoryId = $category->id;
            }
        }
        
        if ($user->isSuperAdmin()) {
            $institutionId = $institutionIdFromFile ? (int)$institutionIdFromFile : null;
            $academicYearId = $academicYearIdFromFile ? (int)$academicYearIdFromFile : (AcademicYear::where('is_current', true)->value('id'));
            $classId = $classIdFromFile ? (int)$classIdFromFile : null;
        } else { // staff
            $allowedInstitutionIds = $user->institutions()->pluck('institutions.id');
            $institutionId = $institutionIdFromFile ? (int)$institutionIdFromFile : ($allowedInstitutionIds->first());
            if (!$allowedInstitutionIds->contains($institutionId)) {
                throw new \Exception('Lembaga pada file tidak diizinkan untuk user ini');
            }
            if ($classIdFromFile) {
                $classId = (int)$classIdFromFile;
                if (!ClassModel::where('id', $classId)->whereIn('institution_id', $allowedInstitutionIds)->exists()) {
                    throw new \Exception('Kelas tidak sesuai dengan lembaga yang diizinkan');
                }
            } else {
                $classId = ClassModel::whereIn('institution_id', $allowedInstitutionIds)->value('id');
            }
            $academicYearId = AcademicYear::where('is_current', true)->value('id');
        }
        
        if (!$institutionId) { throw new \Exception('Lembaga ID wajib diisi'); }
        if (!$academicYearId) { throw new \Exception('Tidak ada tahun ajaran aktif'); }
        if (!$classId) { throw new \Exception('Tidak ada kelas tersedia / Kelas ID tidak valid'); }
        
        // Validasi kesesuaian class terhadap lembaga & tahun ajaran
        if ($classId) {
            $class = ClassModel::find($classId);
            if (!$class) {
                throw new \Exception("Kelas ID '{$classId}' tidak ditemukan");
            }
            if ((int)$class->institution_id !== (int)$institutionId) {
                throw new \Exception("Kelas '{$class->class_name}' bukan milik lembaga yang dipilih (ID {$institutionId})");
            }
            if ((int)$class->academic_year_id !== (int)$academicYearId) {
                throw new \Exception("Kelas '{$class->class_name}' tidak sesuai Tahun Ajaran yang dipilih (ID {$academicYearId})");
            }
        }

        Student::create([
            'nis' => $nis,
            'name' => $name,
            'email' => $email ?: null,
            'phone' => $phone ?: null,
            'address' => $address ?: null,
            'parent_name' => $parentName ?: null,
            'parent_phone' => $parentPhone ?: null,
            'institution_id' => $institutionId,
            'academic_year_id' => $academicYearId,
            'class_id' => $classId,
            'scholarship_category_id' => $scholarshipCategoryId,
            'status' => 'active',
            'enrollment_date' => now(),
        ]);
    }
}
