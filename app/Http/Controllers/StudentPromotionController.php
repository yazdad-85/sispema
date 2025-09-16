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
        
        // Buat billing record baru untuk tahun ajaran baru
        $this->createBillingRecordsForPromotedStudent($student, $nextAcademicYear);
        
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
     * Menggunakan semua billing records tahun berjalan dan total pembayaran terverifikasi.
     * Menerapkan ketentuan beasiswa sesuai level.
     */
    private function calculateAndSetPreviousYearDebt(Student $student, ?AcademicYear $currentAcademicYear): void
    {
        if (!$currentAcademicYear) {
            return;
        }
        
        // Kolom previous_debt_year berdimensi 4 char, simpan tahun awal saja
        $previousYearKey = (string) $currentAcademicYear->year_start;
        
        \Log::info('Calculating previous year debt for student', [
            'student_id' => $student->id,
            'student_name' => $student->name,
            'current_academic_year' => $currentAcademicYear->year_start . '/' . $currentAcademicYear->year_end,
            'current_level' => $student->classRoom->level ?? 'Unknown'
        ]);
        
        // Catatan: pada proses promosi, kita SELALU menghitung tunggakan tahun berjalan
        // sebagai previous_debt untuk tahun berikutnya. Jangan treat sebagai siswa baru.
        
        // Ambil semua billing records tahun berjalan (yang akan menjadi "tahun sebelumnya")
        // Kompatibel dengan format tahun "YYYY-YYYY" atau "YYYY/YYYY"
        $originHyphen = $currentAcademicYear->year_start.'-'.$currentAcademicYear->year_end;
        $originSlash = $currentAcademicYear->year_start.'/'.$currentAcademicYear->year_end;
        
        $billingRecords = BillingRecord::where('student_id', $student->id)
            ->where('status', 'active')
            ->where(function($q) use ($originHyphen, $originSlash) {
                $q->where('origin_year', $originHyphen)
                  ->orWhere('origin_year', $originSlash);
            })
            ->get();
        
        \Log::info('Found billing records for debt calculation', [
            'student_id' => $student->id,
            'billing_records_count' => $billingRecords->count(),
            'origin_year_formats' => [$originHyphen, $originSlash]
        ]);
        
        $totalOutstanding = 0;
        
        if ($billingRecords->count() > 0) {
            // Hitung total outstanding dari semua billing records
            foreach ($billingRecords as $billingRecord) {
                // Total pembayaran terverifikasi untuk billing record ini
            $totalPaid = Payment::where('student_id', $student->id)
                    ->where('billing_record_id', $billingRecord->id)
                ->whereIn('status', [Payment::STATUS_VERIFIED, Payment::STATUS_COMPLETED])
                ->sum('total_amount');
            
                $remainingBalance = max(0, (float)$billingRecord->remaining_balance);
                $outstanding = max(0, (float)$billingRecord->amount - (float)$totalPaid);
                
                // Gunakan remaining_balance jika tersedia, jika tidak gunakan perhitungan manual
                $recordOutstanding = $remainingBalance > 0 ? $remainingBalance : $outstanding;
                $totalOutstanding += $recordOutstanding;
                
                \Log::info('Billing record debt calculation', [
                    'billing_record_id' => $billingRecord->id,
                    'billing_month' => $billingRecord->billing_month,
                    'amount' => $billingRecord->amount,
                    'remaining_balance' => $billingRecord->remaining_balance,
                    'total_paid' => $totalPaid,
                    'outstanding' => $recordOutstanding
                ]);
            }
        } else {
            // Fallback: estimasi dari FeeStructure tahun berjalan berdasarkan level saat ini
            if ($student->classRoom) {
                $fee = \App\Models\FeeStructure::where('institution_id', $student->classRoom->institution_id)
                    ->where('academic_year_id', $currentAcademicYear->id)
                    ->whereHas('class', function($query) use ($student) {
                        $query->where('level', $student->classRoom->level);
                    })
                ->first();
                
                    if ($fee) {
                    $totalOutstanding = (float)$fee->yearly_amount;
                    \Log::info('Using fee structure as fallback', [
                        'fee_structure_id' => $fee->id,
                        'yearly_amount' => $fee->yearly_amount
                    ]);
                }
            }
        }
        
        // Terapkan ketentuan beasiswa berdasarkan level sebelumnya
        $currentLevel = $student->classRoom->level ?? 'Unknown';
        $scholarshipCategory = $student->scholarshipCategory;
        $categoryName = $scholarshipCategory->name ?? '';
        $discountPercentage = (float)($scholarshipCategory->discount_percentage ?? 0);
        
        \Log::info('Applying scholarship rules', [
            'student_id' => $student->id,
            'current_level' => $currentLevel,
            'category_name' => $categoryName,
            'discount_percentage' => $discountPercentage,
            'total_outstanding_before' => $totalOutstanding
        ]);
        
        // Ketentuan beasiswa:
        // 1. Yatim piatu 100% hanya berlaku untuk kelas VII/X, selanjutnya tidak berlaku
        // 2. Alumni hanya berlaku untuk kelas X saja
        // 3. Anak guru 100% selama menjadi siswa dan ketika lulus juga tidak ada tagihan
        
        if ($categoryName === 'Yatim Piatu, Piatu, Yatim' && $discountPercentage >= 100) {
            // Yatim piatu 100% hanya berlaku untuk level VII/X
            if (in_array($currentLevel, ['VII', 'X'])) {
                $totalOutstanding = 0;
                \Log::info('Applied yatim piatu 100% discount for level ' . $currentLevel);
            } else {
                \Log::info('Yatim piatu 100% discount not applicable for level ' . $currentLevel . ' (only for VII/X)');
            }
        } elseif ($categoryName === 'Alumni' && $discountPercentage > 0) {
            // Alumni hanya berlaku untuk kelas X saja
            if ($currentLevel === 'X') {
                $totalOutstanding = $totalOutstanding * (1 - $discountPercentage / 100);
                \Log::info('Applied alumni discount ' . $discountPercentage . '% for level ' . $currentLevel);
            } else {
                \Log::info('Alumni discount not applicable for level ' . $currentLevel . ' (only for X)');
            }
        } elseif (strpos(strtolower($categoryName), 'guru') !== false && $discountPercentage >= 100) {
            // Anak guru 100% berlaku untuk semua level
            $totalOutstanding = 0;
            \Log::info('Applied anak guru 100% discount for level ' . $currentLevel);
        } elseif ($discountPercentage > 0) {
            // Beasiswa umum lainnya
            $totalOutstanding = $totalOutstanding * (1 - $discountPercentage / 100);
            \Log::info('Applied general scholarship discount ' . $discountPercentage . '% for level ' . $currentLevel);
        }
        
        // Fix .004 values to .000 (round down to nearest thousand)
        if ($totalOutstanding > 0 && $totalOutstanding % 1000 == 4) {
            $totalOutstanding = $totalOutstanding - 4;
            \Log::info('Fixed .004 value to .000 for student ' . $student->id);
        }
        
        \Log::info('Previous year debt calculation result', [
            'student_id' => $student->id,
            'total_outstanding_before_scholarship' => $totalOutstanding,
            'total_outstanding_after_scholarship' => $totalOutstanding,
            'previous_debt_year' => $previousYearKey
        ]);
        
        // Set previous_debt
            $student->update([
            'previous_debt' => $totalOutstanding,
                'previous_debt_year' => $previousYearKey,
            ]);
        
        // Handle excess payments
        $this->handleExcessPayments($student);
        
        // Apply excess payments to current billing
        $this->applyExcessPaymentsToCurrentBilling($student);
    }
    
    private function handleExcessPayments($student)
    {
        // Hitung excess berbasis tahun sebelumnya: total paid (verified/completed)
        // dikurangi total billed tahun sebelumnya. Hanya jika > 0 dianggap excess.
        $currentAcademicYear = $student->academicYear;
        if (!$currentAcademicYear) return;
        $previousYear = \App\Models\AcademicYear::where('year_start', $currentAcademicYear->year_start - 1)->first();
        if (!$previousYear) return;

        $prevHyphen = $previousYear->year_start . '-' . $previousYear->year_end;
        $prevSlash  = $previousYear->year_start . '/' . $previousYear->year_end;

        $totalBilledPrev = \App\Models\BillingRecord::where('student_id', $student->id)
            ->whereIn('origin_year', [$prevHyphen, $prevSlash])
            ->sum('amount');

        $totalPaidPrev = \App\Models\Payment::where('student_id', $student->id)
            ->whereIn('status', [\App\Models\Payment::STATUS_VERIFIED, \App\Models\Payment::STATUS_COMPLETED])
            ->whereHas('billingRecord', function($q) use ($prevHyphen, $prevSlash){
                $q->whereIn('origin_year', [$prevHyphen, $prevSlash]);
            })
            ->sum('total_amount');

        $excess = max(0, (float)$totalPaidPrev - (float)$totalBilledPrev);

        if ($excess > 0) {
            $this->createExcessPaymentBillingRecord($student, $excess);
            $this->updateStudentCreditBalance($student, $excess);
        } else {
            // Pastikan tidak membawa credit jika tidak ada excess
            $student->update(['credit_balance' => 0, 'credit_balance_year' => null]);
        }
    }
    
    private function createExcessPaymentBillingRecord($student, $excessAmount)
    {
        // Check if student already has excess payment applied
        $existingExcessBilling = \App\Models\BillingRecord::where('student_id', $student->id)
            ->where('notes', 'LIKE', '%Excess Payment Transfer%')
            ->first();

        if ($existingExcessBilling) {
            return; // Already exists
        }

        // Get current academic year
        $currentAcademicYear = $student->academicYear;
        if (!$currentAcademicYear) {
            return;
        }

        // Get a default fee structure for excess payment
        $defaultFeeStructure = \App\Models\FeeStructure::where('is_active', true)
            ->where('academic_year_id', $currentAcademicYear->id)
            ->first();
        
        if (!$defaultFeeStructure) {
            $defaultFeeStructure = \App\Models\FeeStructure::where('is_active', true)->first();
        }
        
        if (!$defaultFeeStructure) {
            $defaultFeeStructure = \App\Models\FeeStructure::first();
        }
        
        if (!$defaultFeeStructure) {
            return;
        }

        // Create new billing record for excess payment
        $billingRecord = new \App\Models\BillingRecord();
        $billingRecord->student_id = $student->id;
        $billingRecord->fee_structure_id = $defaultFeeStructure->id;
        $billingRecord->origin_year = $currentAcademicYear->year_start . '-' . $currentAcademicYear->year_end;
        $billingRecord->origin_class = $student->classRoom->name ?? 'Unknown';
        $billingRecord->amount = $excessAmount;
        $billingRecord->remaining_balance = $excessAmount;
        $billingRecord->status = 'active';
        $billingRecord->due_date = now()->addDays(30); // Due in 30 days
        $billingRecord->billing_month = 'Excess Payment Transfer';
        $billingRecord->notes = "Excess Payment Transfer from previous year - Amount: " . number_format($excessAmount);
        $billingRecord->save();

        \Log::info("Created excess payment billing record for student {$student->id}: " . number_format($excessAmount));
    }
    
    private function updateStudentCreditBalance($student, $excessAmount)
    {
        // Update student's credit_balance field
        $currentAcademicYear = $student->academicYear;
        if ($currentAcademicYear) {
            $student->update([
                'credit_balance' => $excessAmount,
                'credit_balance_year' => $currentAcademicYear->year_start
            ]);
            \Log::info("Updated credit_balance for student {$student->id}: " . number_format($excessAmount));
        }
    }
    
    private function applyExcessPaymentsToCurrentBilling($student)
    {
        // Get excess payment billing record
        $excessBilling = \App\Models\BillingRecord::where('student_id', $student->id)
            ->where('notes', 'LIKE', '%Excess Payment Transfer%')
            ->first();

        if (!$excessBilling || $excessBilling->remaining_balance <= 0) {
            return; // No excess payment to apply
        }

        $excessAmount = $excessBilling->remaining_balance;

        // Get current academic year billing records (excluding excess payment record)
        $currentAcademicYear = $student->academicYear;
        if (!$currentAcademicYear) {
            return;
        }

        $currentBillingRecords = \App\Models\BillingRecord::where('student_id', $student->id)
            ->where('origin_year', $currentAcademicYear->year_start . '-' . $currentAcademicYear->year_end)
            ->where('notes', 'NOT LIKE', '%Excess Payment Transfer%')
            ->where('remaining_balance', '>', 0)
            ->orderBy('due_date', 'asc')
            ->get();

        if ($currentBillingRecords->isEmpty()) {
            return;
        }

        $remainingExcess = $excessAmount;

        foreach ($currentBillingRecords as $billingRecord) {
            if ($remainingExcess <= 0) {
                break;
            }

            $currentRemaining = $billingRecord->remaining_balance;
            $amountToApply = min($remainingExcess, $currentRemaining);

            // Update billing record
            $billingRecord->remaining_balance = $currentRemaining - $amountToApply;
            
            // Update status based on remaining balance
            if ($billingRecord->remaining_balance <= 0) {
                $billingRecord->status = 'fully_paid';
            } else {
                $billingRecord->status = 'partially_paid';
            }
            
            $billingRecord->save();

            $remainingExcess -= $amountToApply;
        }

        // Update excess payment billing record
        $excessBilling->remaining_balance = $remainingExcess;
        if ($remainingExcess <= 0) {
            $excessBilling->status = 'fully_paid';
        } else {
            $excessBilling->status = 'partially_paid';
        }
        $excessBilling->save();

        $appliedAmount = $excessAmount - $remainingExcess;
        if ($appliedAmount > 0) {
            \Log::info("Applied excess payment: " . number_format($appliedAmount) . " to current billing for student {$student->id}");
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
        $currentLevel = $student->classRoom->level;
        $institutionId = $student->classRoom->institution_id;
        
        \Log::info('Auto distribution for student', [
            'student_id' => $student->id,
            'student_name' => $student->name,
            'current_level' => $currentLevel,
            'next_level' => $nextLevel,
            'institution_id' => $institutionId
        ]);
        
        // Get available classes for the next level
        $availableClasses = ClassModel::where('institution_id', $institutionId)
            ->where('level', $nextLevel)
            ->where('academic_year_id', $nextAcademicYear->id)
            ->where('is_active', true)
            ->where('is_graduated_class', false)
            ->orderBy('class_name')
            ->get();
        
        // Determine if we should create new classes or use existing ones
        $shouldCreateNewClasses = $this->shouldCreateNewClassesForPromotion($currentLevel, $nextLevel);
        
        if ($availableClasses->isEmpty() && $shouldCreateNewClasses) {
            // Get original class count from current academic year to follow structure
            $originalClassCount = $this->getOriginalClassCount($institutionId, $currentLevel, $student->academic_year_id);
            
            // Create new classes for VII→VIII and X→XI following original structure
            $availableClasses = $this->createClassesForLevel($institutionId, $nextLevel, $nextAcademicYear->id, $originalClassCount);
            \Log::info('Created new classes for promotion', [
                'current_level' => $currentLevel,
                'next_level' => $nextLevel,
                'original_class_count' => $originalClassCount,
                'created_classes' => $availableClasses->pluck('class_name')
            ]);
        } elseif ($availableClasses->isEmpty() && !$shouldCreateNewClasses) {
            // For VIII→IX and XI→XII, try to find existing classes from previous academic year
            $availableClasses = $this->findExistingClassesForPromotion($institutionId, $nextLevel, $nextAcademicYear);
            
        if ($availableClasses->isEmpty()) {
                // For VIII→IX and XI→XII, DO NOT create new classes
                // Instead, create a single class that represents the continuation of the previous class
                $availableClasses = $this->createContinuationClass($institutionId, $nextLevel, $nextAcademicYear, $student);
                \Log::info('Created continuation class for promotion', [
                    'current_level' => $currentLevel,
                    'next_level' => $nextLevel,
                    'created_classes' => $availableClasses->pluck('class_name')
                ]);
            } else {
                \Log::info('Found existing classes for promotion', [
                    'current_level' => $currentLevel,
                    'next_level' => $nextLevel,
                    'existing_classes' => $availableClasses->pluck('class_name')
                ]);
            }
        }
        
        if ($availableClasses->isEmpty()) {
            return null;
        }
        
        // Use round-robin distribution based on student ID
        $classIndex = $student->id % $availableClasses->count();
        $targetClass = $availableClasses[$classIndex];
        
        \Log::info('Auto distribution result', [
            'student_id' => $student->id,
            'student_name' => $student->name,
            'available_classes' => $availableClasses->pluck('class_name'),
            'selected_class' => $targetClass->class_name,
            'class_index' => $classIndex,
            'should_create_new' => $shouldCreateNewClasses
        ]);
        
        return $targetClass;
    }
    
    private function createClassesForLevel($institutionId, $level, $academicYearId, $originalClassCount = null)
    {
        $institution = Institution::find($institutionId);
        $academicYear = AcademicYear::find($academicYearId);
        
        \Log::info('Creating classes for level', [
            'institution' => $institution->name,
            'level' => $level,
            'academic_year' => $academicYear->year_start . '/' . $academicYear->year_end,
            'original_class_count' => $originalClassCount
        ]);
        
        // Define class names based on level, institution type, and original class count
        $classNames = $this->getClassNamesForLevel($level, $institution->name, $originalClassCount);
        
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
    
    /**
     * Determine if new classes should be created for promotion
     * VII→VIII and X→XI: Create new classes
     * VIII→IX and XI→XII: Use existing classes
     */
    private function shouldCreateNewClassesForPromotion($currentLevel, $nextLevel)
    {
        // Create new classes for VII→VIII and X→XI
        if (($currentLevel === 'VII' && $nextLevel === 'VIII') || 
            ($currentLevel === 'X' && $nextLevel === 'XI')) {
            return true;
        }
        
        // Use existing classes for VIII→IX and XI→XII
        if (($currentLevel === 'VIII' && $nextLevel === 'IX') || 
            ($currentLevel === 'XI' && $nextLevel === 'XII')) {
            return false;
        }
        
        // Default: create new classes
        return true;
    }
    
    /**
     * Find existing classes from previous academic year for promotion
     * Used for VIII→IX and XI→XII promotions
     */
    private function findExistingClassesForPromotion($institutionId, $nextLevel, $nextAcademicYear)
    {
        // Get previous academic year
        $previousAcademicYear = AcademicYear::where('year_start', $nextAcademicYear->year_start - 1)
            ->where('year_end', $nextAcademicYear->year_end - 1)
            ->first();
        
        if (!$previousAcademicYear) {
            \Log::warning('Previous academic year not found for promotion', [
                'next_academic_year' => $nextAcademicYear->year_start . '/' . $nextAcademicYear->year_end,
                'next_level' => $nextLevel
            ]);
            return collect();
        }
        
        // Find classes from previous academic year that can be reused
        $existingClasses = ClassModel::where('institution_id', $institutionId)
            ->where('level', $nextLevel)
            ->where('academic_year_id', $previousAcademicYear->id)
            ->where('is_active', true)
            ->where('is_graduated_class', false)
            ->orderBy('class_name')
            ->get();
        
        \Log::info('Found existing classes from previous academic year', [
            'institution_id' => $institutionId,
            'next_level' => $nextLevel,
            'previous_academic_year' => $previousAcademicYear->year_start . '/' . $previousAcademicYear->year_end,
            'existing_classes' => $existingClasses->pluck('class_name')
        ]);
        
        return $existingClasses;
    }
    
    /**
     * Create continuation classes for promotion based on previous level structure
     * Used for VIII→IX and XI→XII when no existing classes are found
     */
    private function createContinuationClass($institutionId, $nextLevel, $nextAcademicYear, $student)
    {
        $institution = Institution::find($institutionId);
        $academicYear = $nextAcademicYear;
        $currentLevel = $student->classRoom->level;
        
        \Log::info('Creating continuation classes for promotion', [
            'institution' => $institution->name,
            'current_level' => $currentLevel,
            'next_level' => $nextLevel,
            'academic_year' => $academicYear->year_start . '/' . $academicYear->year_end,
            'student_id' => $student->id
        ]);
        
        // Get the current academic year to find existing classes structure
        $currentAcademicYear = AcademicYear::where('is_current', true)->first();
        
        // Find all classes from the current level in the current academic year
        $existingClasses = ClassModel::where('institution_id', $institutionId)
            ->where('level', $currentLevel)
            ->where('academic_year_id', $currentAcademicYear->id)
            ->where('is_active', true)
            ->where('is_graduated_class', false)
            ->orderBy('class_name')
            ->get();
        
        \Log::info('Found existing classes structure', [
            'current_level' => $currentLevel,
            'existing_classes' => $existingClasses->pluck('class_name'),
            'count' => $existingClasses->count()
        ]);
        
        $createdClasses = collect();
        
        if ($existingClasses->count() > 0) {
            // Create classes based on existing structure
            foreach ($existingClasses as $existingClass) {
                // Extract the suffix from existing class name (A, B, C, D, etc.)
                $className = $this->generateNextLevelClassName($existingClass->class_name, $nextLevel);
                
                $class = ClassModel::create([
                    'class_name' => $className,
                    'level' => $nextLevel,
                    'grade_level' => $nextLevel,
                    'institution_id' => $institutionId,
                    'academic_year_id' => $academicYear->id,
                    'capacity' => $existingClass->capacity ?? 40,
                    'is_active' => true,
                    'is_graduated_class' => false
                ]);
                
                $createdClasses->push($class);
                
                \Log::info('Created continuation class', [
                    'original_class' => $existingClass->class_name,
                    'new_class' => $className,
                    'level' => $nextLevel,
                    'institution' => $institution->name,
                    'class_id' => $class->id
                ]);
            }
        } else {
            // Fallback: create a single class if no existing structure found
            $className = $nextLevel;
            
            $class = ClassModel::create([
                'class_name' => $className,
                'level' => $nextLevel,
                'grade_level' => $nextLevel,
                'institution_id' => $institutionId,
                'academic_year_id' => $academicYear->id,
                'capacity' => 40,
                'is_active' => true,
                'is_graduated_class' => false
            ]);
            
            $createdClasses->push($class);
            
            \Log::info('Created fallback continuation class', [
                'class_name' => $className,
                'level' => $nextLevel,
                'institution' => $institution->name,
                'class_id' => $class->id
            ]);
        }
        
        return $createdClasses;
    }
    
    /**
     * Generate next level class name based on existing class name
     * Examples: "VIII A" → "IX A", "XI B" → "XII B"
     */
    private function generateNextLevelClassName($existingClassName, $nextLevel)
    {
        // Extract the suffix (A, B, C, D, etc.) from existing class name
        $pattern = '/^[A-Z]+\s+([A-Z])$/'; // Matches "VIII A", "XI B", etc.
        
        if (preg_match($pattern, $existingClassName, $matches)) {
            $suffix = $matches[1];
            return $nextLevel . ' ' . $suffix;
        }
        
        // If no suffix found, just use the level name
        return $nextLevel;
    }
    
    private function getClassNamesForLevel($level, $institutionName, $originalClassCount = null)
    {
        // Define class names based on level, institution type, and original class count
        $classNames = [];
        
        if (in_array($level, ['VII', 'VIII', 'IX'])) {
            // SMP/MTs levels - follow original structure if available
            if ($originalClassCount && $originalClassCount > 0) {
                $classNames = $this->generateClassNamesByCount($level, $originalClassCount);
            } else {
                $classNames = [$level . ' A', $level . ' B', $level . ' C'];
            }
        } elseif (in_array($level, ['X', 'XI', 'XII'])) {
            // SMA/SMK/MA levels
            if (strpos($institutionName, 'SMK') !== false) {
                // SMK has specialized classes
                $classNames = $this->getSMKClassNames($level);
            } else {
                // SMA/MA regular classes - follow original structure if available
                if ($originalClassCount && $originalClassCount > 0) {
                    $classNames = $this->generateClassNamesByCount($level, $originalClassCount);
                } else {
                    $classNames = [$level . ' A', $level . ' B', $level . ' C'];
                }
            }
        }
        
        return $classNames;
    }
    
    /**
     * Generate class names based on count (follow original structure)
     * Examples: 1 class -> "XI", 2 classes -> "XI A", "XI B", 3 classes -> "XI A", "XI B", "XI C"
     */
    private function generateClassNamesByCount($level, $count)
    {
        $classNames = [];
        
        if ($count == 1) {
            // Single class - no suffix
            $classNames = [$level];
        } else {
            // Multiple classes - add suffix A, B, C, etc.
            $suffixes = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
            for ($i = 0; $i < $count; $i++) {
                if (isset($suffixes[$i])) {
                    $classNames[] = $level . ' ' . $suffixes[$i];
                }
            }
        }
        
        return $classNames;
    }
    
    /**
     * Get the number of classes in the original level to maintain structure
     */
    private function getOriginalClassCount($institutionId, $currentLevel, $academicYearId)
    {
        $originalClasses = ClassModel::where('institution_id', $institutionId)
            ->where('level', $currentLevel)
            ->where('academic_year_id', $academicYearId)
            ->where('is_active', true)
            ->where('is_graduated_class', false)
            ->count();
            
        \Log::info('Original class count for structure', [
            'institution_id' => $institutionId,
            'current_level' => $currentLevel,
            'academic_year_id' => $academicYearId,
            'original_class_count' => $originalClasses
        ]);
        
        return $originalClasses;
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
    
    /**
     * Buat billing record baru untuk siswa yang dipromosi
     */
    private function createBillingRecordsForPromotedStudent(Student $student, AcademicYear $nextAcademicYear)
    {
        try {
            // Cari fee structure untuk kelas dan tahun ajaran baru
            $feeStructure = \App\Models\FeeStructure::where('institution_id', $student->institution_id)
                ->where('academic_year_id', $nextAcademicYear->id)
                ->where('class_id', $student->class_id)
                ->first();
            
            // Jika tidak ada, cari berdasarkan level
            if (!$feeStructure && $student->classRoom) {
                $feeStructure = \App\Models\FeeStructure::where('institution_id', $student->institution_id)
                    ->where('academic_year_id', $nextAcademicYear->id)
                    ->where('level', $student->classRoom->level)
                    ->first();
            }
            
            // Jika masih tidak ada, gunakan fallback
            if (!$feeStructure) {
                $feeStructure = \App\Models\FeeStructure::where('institution_id', $student->institution_id)
                    ->where('academic_year_id', $nextAcademicYear->id)
                    ->first();
            }
            
            if ($feeStructure) {
                // Hapus billing record lama untuk tahun ajaran baru (jika ada)
                \App\Models\BillingRecord::where('student_id', $student->id)
                    ->where(function($q) use ($nextAcademicYear){
                        $q->where('origin_year', $nextAcademicYear->year_start . '-' . $nextAcademicYear->year_end)
                          ->orWhere('origin_year', $nextAcademicYear->year_start . '/' . $nextAcademicYear->year_end);
                    })
                    ->delete();
                
                // Buat billing record baru untuk 12 bulan
                for ($month = 1; $month <= 12; $month++) {
                    \App\Models\BillingRecord::create([
                        'student_id' => $student->id,
                        'fee_structure_id' => $feeStructure->id,
                        'billing_month' => $month,
                        // Simpan origin_year konsisten dengan format hyphen
                        'origin_year' => $nextAcademicYear->year_start . '-' . $nextAcademicYear->year_end,
                        // Simpan nama kelas (bukan id) agar level bisa diturunkan kembali
                        'origin_class' => optional($student->classRoom)->class_name,
                        'amount' => $feeStructure->monthly_amount,
                        'remaining_balance' => $feeStructure->monthly_amount,
                        'status' => 'active',
                        'notes' => 'ANNUAL',
                        'due_date' => now()->addDays(30), // 30 hari dari sekarang
                    ]);
                }
                
                \Log::info("Billing records created for promoted student", [
                    'student_id' => $student->id,
                    'student_name' => $student->name,
                    'new_academic_year' => $nextAcademicYear->year_start . '/' . $nextAcademicYear->year_end,
                    'new_class_id' => $student->class_id,
                    'billing_records_count' => 12
                ]);
            } else {
                \Log::warning("No fee structure found for promoted student", [
                    'student_id' => $student->id,
                    'student_name' => $student->name,
                    'institution_id' => $student->institution_id,
                    'academic_year_id' => $nextAcademicYear->id,
                    'class_id' => $student->class_id
                ]);
            }
        } catch (\Exception $e) {
            \Log::error("Failed to create billing records for promoted student", [
                'student_id' => $student->id,
                'student_name' => $student->name,
                'error' => $e->getMessage()
            ]);
        }
    }
}