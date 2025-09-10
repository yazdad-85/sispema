<?php

namespace App\Http\Controllers;

use App\Models\BillingRecord;
use App\Models\Payment;
use App\Models\Student;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PDF;

class ReportController extends Controller
{
    public function outstanding(Request $request)
    {
        $user = Auth::user();
        
        $query = BillingRecord::with(['student.institution', 'student.classRoom', 'feeStructure.academicYear'])
            ->where('remaining_balance', '>', 0);
        
        if ($user->isStaff()) {
            $allowedInstitutionIds = $user->institutions()->pluck('institutions.id');
            $query->whereHas('student', function($q) use ($allowedInstitutionIds) {
                $q->whereIn('institution_id', $allowedInstitutionIds);
            });
        }
        
        // Apply filters
        if ($request->filled('institution_id')) {
            $query->whereHas('student', function($q) use ($request) {
                $q->where('institution_id', $request->institution_id);
            });
        }
        
        if ($request->filled('class_id')) {
            $query->whereHas('student', function($q) use ($request) {
                $q->where('class_id', $request->class_id);
            });
        }
        
        if ($request->filled('academic_year_id')) {
            $query->whereHas('feeStructure', function($q) use ($request) {
                $q->where('academic_year_id', $request->academic_year_id);
            });
        }
        
        if ($request->filled('cashier_id')) {
            $query->whereHas('payments', function($q) use ($request) {
                $q->where('kasir_id', $request->cashier_id);
            });
        }
        
        $billingRecords = $query->get();
        
        // Generate summary data
        $summaryData = $this->generateOutstandingSummary($billingRecords);
        
        // Get filter options
        if ($user->isSuperAdmin()) {
            $institutions = Institution::all();
            $classes = \App\Models\ClassModel::all();
            $cashiers = User::where('role', 'cashier')->get();
        } else {
            $institutions = $user->institutions;
            $classes = \App\Models\ClassModel::whereIn('institution_id', $institutions->pluck('id'))->get();
            $cashiers = collect(); // Empty collection for non-admin users
        }
        
        $academicYears = \App\Models\AcademicYear::all();
        
        return view('reports.outstanding', compact('summaryData', 'institutions', 'classes', 'academicYears', 'cashiers'));
    }

    private function generateOutstandingSummary($billingRecords)
    {
        $summary = [];
        
        foreach ($billingRecords as $record) {
            $institutionName = $record->student->institution->name ?? 'Tanpa Lembaga';
            $className = $record->student->classRoom->class_name ?? 'Tanpa Kelas';
            
            if (!isset($summary[$institutionName])) {
                $summary[$institutionName] = [
                    'total_students' => 0,
                    'total_outstanding' => 0,
                    'total_billed' => 0,
                    'classes' => [],
                    'student_ids' => []
                ];
            }
            
            if (!isset($summary[$institutionName]['classes'][$className])) {
                $summary[$institutionName]['classes'][$className] = [
                    'total_students' => 0,
                    'total_outstanding' => 0,
                    'total_billed' => 0,
                    'student_ids' => []
                ];
            }
            
            // Count unique students
            $studentId = $record->student_id;
            if (!in_array($studentId, $summary[$institutionName]['student_ids'])) {
                $summary[$institutionName]['student_ids'][] = $studentId;
                $summary[$institutionName]['total_students']++;
            }
            
            if (!in_array($studentId, $summary[$institutionName]['classes'][$className]['student_ids'])) {
                $summary[$institutionName]['classes'][$className]['student_ids'][] = $studentId;
                $summary[$institutionName]['classes'][$className]['total_students']++;
            }
            
            // Sum amounts
            $amount = (float)($record->amount ?? 0);
            $remaining = (float)($record->remaining_balance ?? $amount);
            
            $summary[$institutionName]['total_outstanding'] += $remaining;
            $summary[$institutionName]['total_billed'] += $amount;
            $summary[$institutionName]['classes'][$className]['total_outstanding'] += $remaining;
            $summary[$institutionName]['classes'][$className]['total_billed'] += $amount;
        }
        
        return $summary;
    }

    public function payments(Request $request)
    {
        $user = Auth::user();
        
        $query = Payment::with(['billingRecord.student.institution', 'billingRecord.student.classRoom', 'billingRecord.feeStructure.academicYear']);
        
        // Try to add kasir relation separately to avoid errors
        try {
            $query->with('kasir');
        } catch (\Exception $e) {
            // If kasir relation fails, continue without it
            \Log::warning('Kasir relation not available: ' . $e->getMessage());
        }
        
        if ($user->isStaff()) {
            $allowedInstitutionIds = $user->institutions()->pluck('institutions.id');
            $query->whereHas('billingRecord.student', function($q) use ($allowedInstitutionIds) {
                $q->whereIn('institution_id', $allowedInstitutionIds);
            });
        }
        
        // Apply filters
        if ($request->filled('institution_id')) {
            $query->whereHas('billingRecord.student', function($q) use ($request) {
                $q->where('institution_id', $request->institution_id);
            });
        }
        
        if ($request->filled('class_id')) {
            $query->whereHas('billingRecord.student', function($q) use ($request) {
                $q->where('class_id', $request->class_id);
            });
        }
        
        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }
        
        if ($request->filled('academic_year_id')) {
            $query->whereHas('billingRecord.feeStructure', function($q) use ($request) {
                $q->where('academic_year_id', $request->academic_year_id);
            });
        }
        
        if ($request->filled('cashier_id')) {
            $query->where('kasir_id', $request->cashier_id);
        }
        
        if ($request->filled('date_from')) {
            $query->where('payment_date', '>=', $request->date_from);
        }
        
        if ($request->filled('date_to')) {
            $query->where('payment_date', '<=', $request->date_to);
        }
        
        $payments = $query->get();
        $totalNominal = $payments->sum('total_amount');
        
        // Manually load kasir relation to avoid errors
        foreach ($payments as $payment) {
            if ($payment->kasir_id) {
                try {
                    $payment->setRelation('kasir', User::find($payment->kasir_id));
                } catch (\Exception $e) {
                    \Log::warning('Could not load kasir for payment ' . $payment->id . ': ' . $e->getMessage());
                }
            }
        }
        
        // Generate summary data per institution and class
        $summaryData = $this->generatePaymentSummary($payments, $user);
        
        // Get filter options - Show cashier filter for all users who can see it
        if ($user->isSuperAdmin() || $user->role === 'admin_pusat') {
            $institutions = Institution::all();
            $classes = \App\Models\ClassModel::all();
            $cashiers = User::where('role', 'cashier')->get();
        } else {
            $institutions = $user->institutions;
            $classes = \App\Models\ClassModel::whereIn('institution_id', $institutions->pluck('id'))->get();
            $cashiers = collect(); // Empty collection for non-admin users
        }
        
        $academicYears = \App\Models\AcademicYear::all();
        $paymentMethods = ['cash' => 'Cash', 'transfer' => 'Transfer', 'qris' => 'QRIS'];
        
        // Generate cashier summary for admin users
        $cashierSummary = ($user->isSuperAdmin() || $user->role === 'admin_pusat') ? $this->generateCashierSummary($payments) : [];
        
        return view('reports.payments', compact('payments', 'totalNominal', 'summaryData', 'institutions', 'classes', 'academicYears', 'paymentMethods', 'cashiers', 'cashierSummary'));
    }

    private function generatePaymentSummary($payments, $user)
    {
        $summary = [];
        
        foreach ($payments as $payment) {
            $institutionName = $payment->billingRecord->student->institution->name ?? 'Tanpa Lembaga';
            $className = $payment->billingRecord->student->classRoom->class_name ?? 'Tanpa Kelas';
            $institutionId = $payment->billingRecord->student->institution->id ?? null;
            $classId = $payment->billingRecord->student->classRoom->id ?? null;
            
            if (!isset($summary[$institutionName])) {
                $summary[$institutionName] = [
                    'institution_id' => $institutionId,
                    'total_students' => 0,
                    'total_paid' => 0,
                    'total_billed' => 0,
                    'classes' => []
                ];
            }
            
            if (!isset($summary[$institutionName]['classes'][$className])) {
                $summary[$institutionName]['classes'][$className] = [
                    'class_id' => $classId,
                    'total_students' => 0,
                    'total_paid' => 0,
                    'total_billed' => 0
                ];
            }
            
            // Count unique students
            $studentId = $payment->billingRecord->student_id;
            if (!isset($summary[$institutionName]['student_ids'])) {
                $summary[$institutionName]['student_ids'] = [];
            }
            if (!isset($summary[$institutionName]['classes'][$className]['student_ids'])) {
                $summary[$institutionName]['classes'][$className]['student_ids'] = [];
            }
            
            if (!in_array($studentId, $summary[$institutionName]['student_ids'])) {
                $summary[$institutionName]['student_ids'][] = $studentId;
                $summary[$institutionName]['total_students']++;
            }
            
            if (!in_array($studentId, $summary[$institutionName]['classes'][$className]['student_ids'])) {
                $summary[$institutionName]['classes'][$className]['student_ids'][] = $studentId;
                $summary[$institutionName]['classes'][$className]['total_students']++;
            }
            
            // Sum amounts
            $amount = (float)($payment->total_amount ?? 0);
            $summary[$institutionName]['total_paid'] += $amount;
            $summary[$institutionName]['classes'][$className]['total_paid'] += $amount;
        }
        
        // Get total billed amounts from BillingRecord
        foreach ($summary as $institutionName => $institutionData) {
            $totalBilled = 0;
            foreach ($institutionData['classes'] as $className => $classData) {
                $classBilled = BillingRecord::whereHas('student', function($q) use ($institutionName) {
                    $q->whereHas('institution', function($iq) use ($institutionName) {
                        $iq->where('name', $institutionName);
                    });
                })->whereHas('student.classRoom', function($cq) use ($className) {
                    $cq->where('class_name', $className);
                })->sum('total_amount');
                
                $summary[$institutionName]['classes'][$className]['total_billed'] = $classBilled;
                $totalBilled += $classBilled;
            }
            $summary[$institutionName]['total_billed'] = $totalBilled;
        }
        
        return $summary;
    }

    private function generateCashierSummary($payments)
    {
        $cashierSummary = [];
        foreach ($payments as $payment) {
            $kasirId = $payment->kasir_id;
            if ($kasirId) {
                if (!isset($cashierSummary[$kasirId])) {
                    $cashierSummary[$kasirId] = [
                        'total_payments' => 0,
                        'total_amount' => 0
                    ];
                }
                $cashierSummary[$kasirId]['total_payments']++;
                $cashierSummary[$kasirId]['total_amount'] += $payment->total_amount;
            }
        }
        return $cashierSummary;
    }

    public function students()
    {
        $user = Auth::user();
        $students = null;
        
        if ($user->role === 'admin_pusat') {
            $students = Student::with(['institution', 'classRoom', 'academicYear', 'billingRecords'])
                              ->get()
                              ->groupBy(function($s){ return $s->institution->name ?? 'Tanpa Lembaga'; });
        } else {
            $students = Student::with(['institution', 'classRoom', 'academicYear', 'billingRecords'])
                              ->where('institution_id', $user->institution_id)
                              ->get()
                              ->groupBy(function($s){ return $s->classRoom->class_name ?? 'Tanpa Kelas'; });
        }
        
        return view('reports.students', compact('students'));
    }

    public function exportOutstanding(Request $request)
    {
        $user = Auth::user();
        $outstandingData = null;
        
        if ($user->role === 'admin_pusat') {
            $outstandingData = BillingRecord::with(['student.institution', 'student.class', 'student.academicYear'])
                                           ->where('status', '!=', 'paid')
                                           ->get()
                                           ->groupBy('student.institution.name');
        } else {
            $outstandingData = BillingRecord::with(['student.institution', 'student.class', 'student.academicYear'])
                                           ->whereHas('student', function($query) use ($user) {
                                               $query->where('institution_id', $user->institution_id);
                                           })
                                           ->where('status', '!=', 'paid')
                                           ->get()
                                           ->groupBy('student.class.name');
        }
        
        if ($request->format === 'pdf') {
            $pdf = PDF::loadView('reports.outstanding-pdf', compact('outstandingData'));
            return $pdf->download('laporan-tunggakan-' . date('Y-m-d') . '.pdf');
        } else {
            // Excel export logic here
            return response()->json(['message' => 'Excel export not implemented yet']);
        }
    }

    public function exportPayments(Request $request)
    {
        $user = Auth::user();
        
        // Get filter parameters
        $institutionId = $request->get('institution_id');
        $classId = $request->get('class_id');
        $academicYearId = $request->get('academic_year_id');
        if (!$academicYearId) {
            $activeYear = \App\Models\AcademicYear::where('is_current', true)->first();
            $academicYearId = $activeYear ? $activeYear->id : null;
        }
        
        // Build query for students
        $query = Student::with([
            'institution', 
            'classRoom', 
            'billingRecords.feeStructure.academicYear',
            'payments'
        ]);
        
        // Apply institution filter
        if ($institutionId && $institutionId !== 'all') {
            $query->where('institution_id', $institutionId);
        } elseif ($user->isStaff()) {
            // Staff can only see their assigned institutions
            $allowedInstitutionIds = $user->institutions()->pluck('institutions.id');
            $query->whereIn('institution_id', $allowedInstitutionIds);
        }
        
        // Apply class filter
        if ($classId) {
            $query->where('class_id', $classId);
        }
        
        // Apply academic year filter
        if ($academicYearId) {
            $query->whereHas('billingRecords.feeStructure', function($q) use ($academicYearId) {
                $q->where('academic_year_id', $academicYearId);
            });
        }
        
        $students = $query->get();
        
        // Prepare data for PDF
        $institutionName = $institutionId && $institutionId !== 'all' 
            ? Institution::find($institutionId)->name 
            : 'Semua Lembaga';
        
        $className = $classId 
            ? \App\Models\ClassModel::find($classId)->class_name 
            : 'Semua Kelas';
        
        $academicYear = $academicYearId 
            ? \App\Models\AcademicYear::find($academicYearId)->name 
            : 'Tahun Ajaran Aktif';
        
        // Generate PDF
        $pdf = PDF::loadView('reports.payments-pdf', compact(
            'students', 
            'institutionName', 
            'className', 
            'academicYear',
            'user'
        ));
        
        // Set PDF to landscape A4
        $pdf->setPaper('A4', 'landscape');
        
        $filename = 'rekapitulasi-pembayaran-' . 
                   strtolower(str_replace(' ', '-', $institutionName)) . '-' . 
                   strtolower(str_replace(' ', '-', $className)) . '-' . 
                   date('Y-m-d') . '.pdf';
        
        return $pdf->download($filename);
    }
}
