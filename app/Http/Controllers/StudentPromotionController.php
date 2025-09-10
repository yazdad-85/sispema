<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\ClassModel;
use App\Models\AcademicYear;
use App\Models\Institution;
use App\Models\BillingRecord;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StudentPromotionController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Get current academic year
        $currentAcademicYear = AcademicYear::where('is_current', true)->first();
        
        if (!$currentAcademicYear) {
            return redirect()->back()->with('error', 'Tidak ada tahun ajaran aktif! Silakan buat tahun ajaran baru terlebih dahulu.');
        }
        
        // Get institutions for filter
        $institutions = null;
        if ($user && method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            $institutions = Institution::orderBy('name')->get();
        } elseif ($user && method_exists($user, 'isStaff') && $user->isStaff()) {
            $institutions = $user->institutions()->orderBy('name')->get();
        } else {
            $institutions = Institution::orderBy('name')->get();
        }
        
        // Get selected filters
        $selectedInstitution = $request->get('institution_id');
        $selectedLevel = $request->get('level');
        
        // Initialize query for active students only
        $query = Student::with(['classRoom.institution', 'classRoom.academicYear', 'classRoom'])
            ->whereHas('classRoom', function($subQ) use ($currentAcademicYear) {
                $subQ->where('academic_year_id', $currentAcademicYear->id);
            })
            ->where('status', 'active'); // Only active students for promotion
        
        // Apply institution filter
        if ($selectedInstitution) {
            $query->whereHas('classRoom', function($q) use ($selectedInstitution) {
                $q->where('institution_id', $selectedInstitution);
            });
        } elseif ($user && method_exists($user, 'isStaff') && $user->isStaff()) {
            $allowed = $user->institutions()->pluck('institutions.id');
            if ($allowed->isNotEmpty()) {
                $query->whereHas('classRoom', function($q) use ($allowed) {
                    $q->whereIn('institution_id', $allowed);
                });
            }
        }
        
        // Apply level filter
        if ($selectedLevel) {
            $query->whereHas('classRoom', function($q) use ($selectedLevel) {
                $q->where('level', $selectedLevel);
            });
        }
        
        // Get students grouped by current level
        $students = $query->orderBy('name')->get();
        
        // Group students by level for promotion display
        $studentsByLevel = $students->groupBy(function($student) {
            return $student->classRoom->level ?? 'Unknown';
        });
        
        // Get available levels for selected institution
        $availableLevels = [];
        if ($selectedInstitution) {
            $availableLevels = ClassModel::where('institution_id', $selectedInstitution)
                ->where('academic_year_id', $currentAcademicYear->id)
                ->where('is_active', true)
                ->where('is_graduated_class', false)
                ->distinct()
                ->pluck('level')
                ->sort()
                ->values();
        }
        
        return view('student-promotions.index', compact(
            'studentsByLevel', 
            'institutions', 
            'currentAcademicYear',
            'selectedInstitution',
            'selectedLevel',
            'availableLevels'
        ));
    }
    
    public function promote(Request $request)
    {
        \Log::info('Promotion request received', [
            'student_ids' => $request->student_ids,
            'promotion_type' => $request->promotion_type,
            'target_class_id' => $request->target_class_id,
            'user_id' => Auth::id()
        ]);
        
        $request->validate([
            'student_ids' => 'required|array',
            'student_ids.*' => 'exists:students,id',
            'promotion_type' => 'required|in:grade_up,graduate',
            'target_class_id' => 'nullable|string', // Can be 'auto_distribute' or class ID
        ]);
        
        // Check if next academic year exists
        $nextAcademicYear = $this->getNextAcademicYear();
        if (!$nextAcademicYear) {
            return redirect()->back()->with('error', 'Tahun ajaran baru belum dibuat! Silakan buat tahun ajaran baru terlebih dahulu.');
        }
        
        $user = Auth::user();
        $studentIds = $request->student_ids;
        $promotionType = $request->promotion_type;
        
        try {
            DB::beginTransaction();
            
            $promotedCount = 0;
            $errors = [];
            
            foreach ($studentIds as $studentId) {
                $student = Student::with('classRoom')->find($studentId);
                
                if (!$student) {
                    $errors[] = "Siswa ID {$studentId} tidak ditemukan";
                    continue;
                }
                
                if ($student->status !== 'active') {
                    $errors[] = "Siswa {$student->name} tidak aktif";
                    continue;
                }
                
                switch ($promotionType) {
                    case 'grade_up':
                        $result = $this->promoteToNextGrade($student, $request->target_class_id, $nextAcademicYear);
                        break;
                    case 'graduate':
                        $result = $this->graduateStudent($student, $nextAcademicYear);
                        break;
                    default:
                        $result = ['success' => false, 'message' => 'Jenis promosi tidak valid'];
                }
                
                if ($result['success']) {
                    $promotedCount++;
                } else {
                    $errors[] = "Siswa {$student->name}: {$result['message']}";
                }
            }
            
            if ($promotedCount > 0) {
                DB::commit();
                \Log::info('Promotion completed', [
                    'promoted_count' => $promotedCount,
                    'total_errors' => count($errors),
                    'user_id' => Auth::id()
                ]);
                
                $message = "Berhasil mempromosi {$promotedCount} siswa";
                if (!empty($errors)) {
                    $message .= ". Error: " . implode(', ', $errors);
                }
                
                return redirect()->back()->with('success', $message);
            } else {
                DB::rollback();
                return redirect()->back()->with('error', 'Tidak ada siswa yang berhasil dipromosi. Error: ' . implode(', ', $errors));
            }
            
        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Promotion failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id()
            ]);
            
            return redirect()->back()->with('error', 'Terjadi kesalahan saat promosi: ' . $e->getMessage());
        }
    }
    
    private function promoteToNextGrade($student, $targetClassId, $nextAcademicYear)
    {
        // Simpan referensi tahun ajaran saat ini (sebelum dipindah)
        $currentAcademicYear = $student->academicYear;
        $currentLevel = $student->classRoom->level;
        $nextLevel = $this->getNextLevel($currentLevel);
        
        if (!$nextLevel) {
            return ['success' => false, 'message' => 'Tidak ada level berikutnya untuk ' . $currentLevel];
        }
        
        // Find or create target class
        $targetClass = null;
        if ($targetClassId === 'auto_distribute') {
            $targetClass = $this->getAutoDistributedClass($student, $nextLevel, $nextAcademicYear);
        } elseif ($targetClassId) {
            $targetClass = ClassModel::find($targetClassId);
        } else {
            // Auto-find first available class
            $targetClass = ClassModel::where('institution_id', $student->classRoom->institution_id)
                ->where('level', $nextLevel)
                ->where('academic_year_id', $nextAcademicYear->id)
                ->where('is_active', true)
                ->where('is_graduated_class', false)
                ->first();
        }
        
        if (!$targetClass) {
            return ['success' => false, 'message' => 'Tidak ada kelas tersedia untuk level ' . $nextLevel];
        }
        
        // Hitung dan set tunggakan tahun sebelumnya berdasarkan annual record & pembayaran
        $this->calculateAndSetPreviousYearDebt($student, $currentAcademicYear);

        // Update student
        $student->update([
            'class_id' => $targetClass->id,
            'academic_year_id' => $nextAcademicYear->id
        ]);
        
        // Carry forward credit balance to next academic year (sesuai perilaku saat ini)
        $this->carryForwardCreditBalance($student, $nextAcademicYear);
        
        \Log::info('Student promoted to next grade', [
            'student_id' => $student->id,
            'student_name' => $student->name,
            'from_level' => $currentLevel,
            'to_level' => $nextLevel,
            'target_class' => $targetClass->class_name,
            'new_academic_year' => $nextAcademicYear->year_start . '/' . $nextAcademicYear->year_end
        ]);
        
        return ['success' => true, 'message' => 'Berhasil naik kelas'];
    }

    /**
     * Hitung sisa kewajiban tahun sebelumnya dan set ke previous_debt.
     * Menggunakan Annual Billing Record dan total pembayaran terverifikasi.
     */
    private function calculateAndSetPreviousYearDebt(Student $student, ?AcademicYear $currentAcademicYear): void
    {
        if (!$currentAcademicYear) {
            return;
        }
        
        // Kolom previous_debt_year berdimensi 4 char, simpan tahun awal saja
        $previousYearKey = (string) $currentAcademicYear->year_start;
        
        // Ambil annual billing record tahun berjalan (yang akan menjadi "tahun sebelumnya")
        // Kompatibel dengan format tahun "YYYY-YYYY" atau "YYYY/YYYY"
        $originHyphen = $currentAcademicYear->year_start.'-'.$currentAcademicYear->year_end;
        $originSlash = $currentAcademicYear->year_start.'/'.$currentAcademicYear->year_end;
        $annual = BillingRecord::where('student_id', $student->id)
            ->where('notes', 'ANNUAL')
            ->where(function($q) use ($originHyphen, $originSlash) {
                $q->where('origin_year', $originHyphen)
                  ->orWhere('origin_year', $originSlash);
            })
            ->first();
        
        if ($annual) {
            // Total pembayaran terverifikasi untuk annual record tersebut
            $totalPaid = Payment::where('student_id', $student->id)
                ->where('billing_record_id', $annual->id)
                ->whereIn('status', [Payment::STATUS_VERIFIED, Payment::STATUS_COMPLETED])
                ->sum('total_amount');
            
            $outstanding = max(0, (float)$annual->amount - (float)$totalPaid);
        } else {
            // Fallback: estimasi dari FeeStructure tahun sebelumnya berdasarkan level sebelumnya
            $prevAcademicYear = AcademicYear::where('year_start', $currentAcademicYear->year_start - 1)
                ->where('year_end', $currentAcademicYear->year_end - 1)
                ->first();
            $outstanding = 0;
            if ($prevAcademicYear && $student->classRoom) {
                $map = ['VIII'=>'VII','IX'=>'VIII','X'=>'IX','XI'=>'X','XII'=>'XI'];
                $prevLevel = $map[$student->classRoom->level] ?? null;
                if ($prevLevel) {
                    $fee = \App\Models\FeeStructure::findByLevel(
                        $student->classRoom->institution_id,
                        $prevAcademicYear->id,
                        $prevLevel
                    );
                    if ($fee) {
                        $outstanding = (float)$fee->yearly_amount;
                    }
                }
            }
        }
        
        // Set previous_debt bila ada sisa
        if ($outstanding > 0) {
            $student->update([
                'previous_debt' => $outstanding,
                'previous_debt_year' => $previousYearKey,
            ]);
        } else {
            // Jika tidak ada sisa, kosongkan previous_debt agar tidak menumpuk
            $student->update([
                'previous_debt' => 0,
                'previous_debt_year' => $previousYearKey,
            ]);
        }
    }
    
    private function graduateStudent($student, $nextAcademicYear)
    {
        $currentLevel = $student->classRoom->level;
        
        // Create or find graduated class
        $graduatedClass = $this->getOrCreateGraduatedClass($student->classRoom->institution, $nextAcademicYear);
        
        // Update student status
        $student->update([
            'status' => 'graduated',
            'class_id' => $graduatedClass->id,
            'academic_year_id' => $nextAcademicYear->id
        ]);
        
        // Carry forward credit balance to next academic year
        $this->carryForwardCreditBalance($student, $nextAcademicYear);
        
        \Log::info('Student graduated', [
            'student_id' => $student->id,
            'student_name' => $student->name,
            'from_level' => $currentLevel,
            'graduated_class' => $graduatedClass->class_name,
            'academic_year' => $nextAcademicYear->year_start . '/' . $nextAcademicYear->year_end
        ]);
        
        return ['success' => true, 'message' => 'Berhasil lulus'];
    }
    
    private function getAutoDistributedClass($student, $nextLevel, $nextAcademicYear)
    {
        // Get available classes for the next level
        $availableClasses = ClassModel::where('institution_id', $student->classRoom->institution_id)
            ->where('level', $nextLevel)
            ->where('academic_year_id', $nextAcademicYear->id)
            ->where('is_active', true)
            ->where('is_graduated_class', false)
            ->orderBy('class_name')
            ->get();
        
        // If no classes exist, create them automatically
        if ($availableClasses->isEmpty()) {
            $availableClasses = $this->createClassesForLevel($student->classRoom->institution_id, $nextLevel, $nextAcademicYear->id);
        }
        
        if ($availableClasses->isEmpty()) {
            return null;
        }
        
        // Use round-robin distribution based on student ID
        $classIndex = $student->id % $availableClasses->count();
        $targetClass = $availableClasses[$classIndex];
        
        \Log::info('Auto distribution for student', [
            'student_id' => $student->id,
            'student_name' => $student->name,
            'available_classes' => $availableClasses->pluck('class_name'),
            'selected_class' => $targetClass->class_name,
            'class_index' => $classIndex
        ]);
        
        return $targetClass;
    }
    
    private function createClassesForLevel($institutionId, $level, $academicYearId)
    {
        $institution = Institution::find($institutionId);
        $academicYear = AcademicYear::find($academicYearId);
        
        \Log::info('Creating classes for level', [
            'institution' => $institution->name,
            'level' => $level,
            'academic_year' => $academicYear->year_start . '/' . $academicYear->year_end
        ]);
        
        // Define class names based on level and institution type
        $classNames = $this->getClassNamesForLevel($level, $institution->name);
        
        $createdClasses = collect();
        
        foreach ($classNames as $className) {
            $class = ClassModel::create([
                'class_name' => $className,
                'level' => $level,
                'grade_level' => $level,
                'institution_id' => $institutionId,
                'academic_year_id' => $academicYearId,
                'capacity' => 40,
                'is_active' => true,
                'is_graduated_class' => false
            ]);
            
            $createdClasses->push($class);
            
            \Log::info('Created class', [
                'class_name' => $className,
                'level' => $level,
                'institution' => $institution->name
            ]);
        }
        
        return $createdClasses;
    }
    
    private function getClassNamesForLevel($level, $institutionName)
    {
        // Define class names based on level and institution type
        $classNames = [];
        
        if (in_array($level, ['VII', 'VIII', 'IX'])) {
            // SMP/MTs levels
            $classNames = [$level . ' A', $level . ' B', $level . ' C'];
        } elseif (in_array($level, ['X', 'XI', 'XII'])) {
            // SMA/SMK/MA levels
            if (strpos($institutionName, 'SMK') !== false) {
                // SMK has specialized classes
                $classNames = $this->getSMKClassNames($level);
            } else {
                // SMA/MA regular classes
                $classNames = [$level . ' A', $level . ' B', $level . ' C'];
            }
        }
        
        return $classNames;
    }
    
    private function getSMKClassNames($level)
    {
        // SMK specialized classes
        $specializations = ['TPM', 'TKR', 'TL', 'APL', 'MPK', 'DKV'];
        $classNames = [];
        
        foreach ($specializations as $spec) {
            $classNames[] = $level . ' ' . $spec . ' 1';
            $classNames[] = $level . ' ' . $spec . ' 2';
        }
        
        return $classNames;
    }
    
    private function getOrCreateGraduatedClass($institution, $academicYear)
    {
        // Create graduated class name based on previous academic year
        $previousYear = $academicYear->year_start - 1;
        $graduatedClassName = "Graduated {$previousYear}-{$academicYear->year_start}";
        
        // Find or create graduated class
        $graduatedClass = ClassModel::where('institution_id', $institution->id)
            ->where('academic_year_id', $academicYear->id)
            ->where('is_graduated_class', true)
            ->where('class_name', $graduatedClassName)
            ->first();
        
        if (!$graduatedClass) {
            $graduatedClass = ClassModel::create([
                'class_name' => $graduatedClassName,
                'level' => 'Graduated',
                'grade_level' => 'Graduated',
                'institution_id' => $institution->id,
                'academic_year_id' => $academicYear->id,
                'capacity' => 1000, // Large capacity for graduated students
                'is_active' => true,
                'is_graduated_class' => true
            ]);
            
            \Log::info('Created graduated class', [
                'class_name' => $graduatedClassName,
                'institution' => $institution->name,
                'academic_year' => $academicYear->year_start . '/' . $academicYear->year_end
            ]);
        }
        
        return $graduatedClass;
    }
    
    private function getNextLevel($currentLevel)
    {
        $levelMap = [
            'VII' => 'VIII',
            'VIII' => 'IX',
            'IX' => null, // IX is final level for SMP/MTs
            'X' => 'XI',
            'XI' => 'XII',
            'XII' => null, // XII is final level for SMA/SMK/MA
        ];
        
        return $levelMap[$currentLevel] ?? null;
    }
    
    private function carryForwardCreditBalance($student, $nextAcademicYear)
    {
        // Check if student has credit balance from previous year
        if ($student->credit_balance > 0) {
            // Bawa sebagai kredit ke tahun ajaran berikutnya agar mengurangi tagihan bulan Juli
            $student->update([
                'credit_balance' => $student->credit_balance,
                'credit_balance_year' => (string) $nextAcademicYear->year_start,
            ]);
            
            \Log::info('Credit balance carried forward to next year as credit', [
                'student_id' => $student->id,
                'student_name' => $student->name,
                'credit_balance' => $student->credit_balance,
                'new_credit_balance_year' => $nextAcademicYear->year_start
            ]);
        }
    }
    
    private function getNextAcademicYear()
    {
        $currentAcademicYear = AcademicYear::where('is_current', true)->first();
        
        if (!$currentAcademicYear) {
            return null;
        }
        
        // Find next academic year
        $nextAcademicYear = AcademicYear::where('year_start', $currentAcademicYear->year_start + 1)
            ->where('year_end', $currentAcademicYear->year_end + 1)
            ->first();
        
        return $nextAcademicYear;
    }
    
    public function showPaymentHistory($studentId)
    {
        $student = Student::with(['classRoom.institution', 'billingRecords', 'payments'])->findOrFail($studentId);
        
        // Get payment summary
        $summary = $this->getStudentPaymentSummary($studentId);
        
        return view('student-promotions.payment-history', compact('student', 'summary'));
    }
    
    public function getStudentPaymentSummary($studentId)
    {
        $student = Student::findOrFail($studentId);
        
        $billingRecords = $student->billingRecords()->get();
        $payments = $student->payments()->get();
        
        $totalBilled = $billingRecords->sum('total_amount');
        $totalPaid = $payments->sum('total_amount');
        $outstandingAmount = $totalBilled - $totalPaid;
        
        return [
            'total_billed' => $totalBilled,
            'total_paid' => $totalPaid,
            'outstanding_amount' => $outstandingAmount,
            'billing_records_count' => $billingRecords->count(),
            'payments_count' => $payments->count(),
        ];
    }
}