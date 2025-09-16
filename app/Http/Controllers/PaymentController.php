<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Payment;
use App\Models\Student;
use App\Models\BillingRecord;
use App\Models\PaymentAllocation;
use App\Services\SppFinancialService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Institution;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        
        if ($user->isSuperAdmin()) {
            $paymentsQuery = Payment::with(['billingRecord.student.institution', 'billingRecord.student.classRoom', 'billingRecord.feeStructure.academicYear']);
            $students = Student::with(['institution', 'classRoom'])->get();
            $institutions = Institution::all();
        } elseif ($user->isStaff()) {
            $allowedInstitutionIds = $user->institutions()->pluck('institutions.id');
            $paymentsQuery = Payment::whereHas('billingRecord.student', function($query) use ($allowedInstitutionIds) {
                $query->whereIn('institution_id', $allowedInstitutionIds);
            })->with(['billingRecord.student.institution', 'billingRecord.student.classRoom', 'billingRecord.feeStructure.academicYear']);
            $students = Student::whereIn('institution_id', $allowedInstitutionIds)->with(['institution', 'classRoom'])->get();
            $institutions = Institution::whereIn('id', $allowedInstitutionIds)->get();
        } else {
            $paymentsQuery = Payment::with(['billingRecord.student.institution', 'billingRecord.student.classRoom', 'billingRecord.feeStructure.academicYear']);
            $students = Student::with(['institution', 'classRoom'])->get();
            $institutions = Institution::all();
        }
        
        // Get all payments for statistics (before pagination)
        $allPayments = $paymentsQuery->get();
        
        // Apply pagination
        $payments = $paymentsQuery->orderBy('created_at', 'desc')->paginate(15);
        
        // Calculate summary statistics using all payments
        $totalPayments = $allPayments->count();
        $totalAmount = $allPayments->sum('total_amount');
        
        // Calculate pending payments (status pending + transfer/QRIS that might need confirmation)
        $pendingPayments = $allPayments->where('status', 'pending')->count();
        $transferQrisPayments = $allPayments->whereIn('payment_method', ['transfer', 'qris'])->count();
        
        // Calculate completed bills (tagihan yang sudah lunas)
        $completedBills = 0;
        $billingRecords = collect();
        
        // Get unique billing records from all payments
        foreach ($allPayments as $payment) {
            if ($payment->billingRecord) {
                $billingRecords->push($payment->billingRecord);
            }
        }
        
        // Count billing records that are fully paid (remaining_balance = 0)
        $completedBills = $billingRecords->unique('id')->where('remaining_balance', 0)->count();
        
        return view('payments.index', compact('payments', 'students', 'institutions', 'totalPayments', 'totalAmount', 'pendingPayments', 'transferQrisPayments', 'completedBills'));
    }

    public function create()
    {
        $user = Auth::user();
        
        if ($user->isSuperAdmin()) {
            $billingRecords = BillingRecord::with(['student.institution', 'student.classRoom', 'feeStructure.academicYear'])->get();
        } elseif ($user->isStaff()) {
            $allowedInstitutionIds = $user->institutions()->pluck('institutions.id');
            $billingRecords = BillingRecord::whereHas('student', function($query) use ($allowedInstitutionIds) {
                $query->whereIn('institution_id', $allowedInstitutionIds);
            })->with(['student.institution', 'student.classRoom', 'feeStructure.academicYear'])->get();
        } else {
            $billingRecords = BillingRecord::with(['student.institution', 'student.classRoom', 'feeStructure.academicYear'])->get();
        }
        
        return view('payments.create', compact('billingRecords'));
    }

    public function store(Request $request)
    {
        // Log the incoming request data
        \Log::info('Payment store request received', [
            'request_data' => $request->all(),
            'user_id' => auth()->id()
        ]);

        $request->validate([
            'student_id' => 'required|exists:students,id',
            'billing_record_id' => 'required|exists:billing_records,id',
            'payment_date' => 'required|date',
            'total_amount' => 'required|integer|min:0',
            'payment_method' => 'required|in:cash,transfer,qris',
            'notes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            // Check if billing record exists and has remaining balance
            $billingRecord = BillingRecord::findOrFail($request->billing_record_id);
            \Log::info('Billing record found', ['billing_record' => $billingRecord->toArray()]);
            
            if ($billingRecord->student_id != $request->student_id) {
                throw new \Exception('Tagihan tidak sesuai dengan siswa yang dipilih');
            }

            // Determine payment status based on payment method
            $paymentStatus = 'verified'; // Default for cash
            if (in_array($request->payment_method, ['transfer', 'qris'])) {
                $paymentStatus = 'pending'; // Needs verification for transfer/QRIS
            }

            $payment = Payment::create([
                'student_id' => $request->student_id,
                'billing_record_id' => $request->billing_record_id,
                'payment_date' => now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
                'total_amount' => (int) $request->total_amount,
                'payment_method' => $request->payment_method,
                'status' => $paymentStatus, // Set status based on payment method
                'notes' => $request->notes,
                'receipt_number' => 'RCP-' . date('Ymd') . '-' . str_pad(Payment::count() + 1, 4, '0', STR_PAD_LEFT),
                'kasir_id' => auth()->id(), // Add the kasir_id (current user)
            ]);

            \Log::info('Payment created successfully', ['payment' => $payment->toArray()]);

            // Update billing record remaining balance only for verified/completed
            if (in_array($paymentStatus, [Payment::STATUS_VERIFIED, Payment::STATUS_COMPLETED])) {
                $billingRecord->update([
                    'remaining_balance' => max(0, $billingRecord->remaining_balance - $request->total_amount)
                ]);
            }

            \Log::info('Billing record updated', ['new_remaining_balance' => $billingRecord->remaining_balance]);

            // Process SPP financial integration
            try {
                // Only process financials for verified/completed payments
                if (in_array($payment->status, [Payment::STATUS_VERIFIED, Payment::STATUS_COMPLETED])) {
                    $sppFinancialService = new SppFinancialService();
                    $sppFinancialService->processSppPayment($payment);
                }
            } catch (\Exception $e) {
                \Log::error('SPP financial integration failed', [
                    'payment_id' => $payment->id,
                    'error' => $e->getMessage()
                ]);
                // Don't fail the payment if financial integration fails
            }

            DB::commit();

            return redirect()->route('payments.show', $payment)
                ->with('success', 'Pembayaran berhasil disimpan');

        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Payment creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function show(Payment $payment)
    {
        $payment->load(['student.institution', 'student.classRoom', 'billingRecord']);
        
        return view('payments.show', compact('payment'));
    }

    public function edit(Payment $payment)
    {
        $user = Auth::user();
        
        if ($user->isSuperAdmin()) {
            $billingRecords = BillingRecord::with(['student.institution', 'student.classRoom', 'feeStructure.academicYear'])->get();
        } elseif ($user->isStaff()) {
            $allowedInstitutionIds = $user->institutions()->pluck('institutions.id');
            $billingRecords = BillingRecord::whereHas('student', function($query) use ($allowedInstitutionIds) {
                $query->whereIn('institution_id', $allowedInstitutionIds);
            })->with(['student.institution', 'student.classRoom', 'feeStructure.academicYear'])->get();
        } else {
            $billingRecords = BillingRecord::with(['student.institution', 'student.classRoom', 'feeStructure.academicYear'])->get();
        }
        
        return view('payments.edit', compact('payment', 'billingRecords'));
    }

    public function update(Request $request, Payment $payment)
    {
        $user = Auth::user();
        
        // Check if user has access to this payment
        if ($user->role !== 'admin_pusat' && $payment->student->institution_id !== $user->institution_id) {
            abort(403, 'Anda tidak memiliki akses ke pembayaran ini');
        }
        
        $request->validate([
            'payment_date' => 'required|date',
            'total_amount' => 'required|integer|min:0',
            'payment_method' => 'required|in:cash,transfer,qris',
            'status' => 'required|in:pending,completed,failed,cancelled',
            'notes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();
            
            // Calculate difference in amount
            $amountDifference = (int) $request->total_amount - $payment->total_amount;
            
            $payment->update([
                'payment_date' => $request->payment_date,
                'total_amount' => (int) $request->total_amount,
                'payment_method' => $request->payment_method,
                'status' => $request->status,
                'notes' => $request->notes,
            ]);

            // Update billing record remaining balance
            if ($payment->billingRecord) {
                $payment->billingRecord->update([
                    'remaining_balance' => max(0, $payment->billingRecord->remaining_balance - $amountDifference)
                ]);
            }

            DB::commit();

            return redirect()->route('payments.show', $payment)
                ->with('success', 'Pembayaran berhasil diperbarui');

        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function destroy(Payment $payment)
    {
        $user = Auth::user();
        
        // Check if user has access to this payment
        if ($user->role !== 'admin_pusat' && $payment->student->institution_id !== $user->institution_id) {
            abort(403, 'Anda tidak memiliki akses ke pembayaran ini');
        }
        
        try {
            DB::beginTransaction();
            
            // Restore billing record remaining balance
            if ($payment->billingRecord) {
                $payment->billingRecord->update([
                    'remaining_balance' => $payment->billingRecord->remaining_balance + $payment->total_amount
                ]);
            }
            
            $payment->delete();
            
            DB::commit();

            return redirect()->route('payments.index')
                ->with('success', 'Pembayaran berhasil dihapus');

        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * Verify a pending payment (change status from pending to verified or failed)
     */
    public function verify(Payment $payment, Request $request)
    {
        $user = Auth::user();
        
        // Check if user has access to this payment
        $hasAccess = false;
        
        // Super admin can access all payments
        if ($user->role === 'super_admin') {
            $hasAccess = true;
        }
        // Admin pusat can access all payments
        elseif ($user->role === 'admin_pusat') {
            $hasAccess = true;
        }
        // Staff can access payments from their institutions
        elseif ($user->role === 'staff') {
            $allowedInstitutionIds = $user->institutions()->pluck('institutions.id');
            if ($payment->student && in_array($payment->student->institution_id, $allowedInstitutionIds->toArray())) {
                $hasAccess = true;
            }
        }
        // Kasir can verify their own payments
        elseif ($payment->kasir_id === $user->id) {
            $hasAccess = true;
        }
        
        if (!$hasAccess) {
            abort(403, 'Anda tidak memiliki akses ke pembayaran ini');
        }
        
        $request->validate([
            'verification_status' => 'required|in:verified,failed',
            'verification_notes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();
            
            $oldStatus = $payment->status;
            $newStatus = $request->verification_status;
            
            // Update payment status
            $payment->update([
                'status' => $newStatus,
                'notes' => $payment->notes . "\n\n[VERIFIKASI] " . now()->format('d/m/Y H:i') . " - Status: " . 
                           ($newStatus === 'verified' ? 'Terverifikasi' : 'Gagal') . 
                           ($request->verification_notes ? " - Catatan: " . $request->verification_notes : '')
            ]);

            // If payment is verified, update billing record
            if ($newStatus === 'verified' && $oldStatus === 'pending') {
                if ($payment->billingRecord) {
                    $payment->billingRecord->update([
                        'remaining_balance' => max(0, $payment->billingRecord->remaining_balance - $payment->total_amount)
                    ]);
                }

                // Trigger financial processing now that payment is verified
                try {
                    $sppFinancialService = new \App\Services\SppFinancialService();
                    $sppFinancialService->processSppPayment($payment);
                } catch (\Exception $e) {
                    \Log::error('Failed processing financials on verify', [
                        'payment_id' => $payment->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            // If payment is failed, restore billing record (if it was previously updated)
            if ($newStatus === 'failed' && $oldStatus === 'pending') {
                // No need to restore since pending payments don't affect billing record
            }

            DB::commit();

            $statusText = $newStatus === 'verified' ? 'Terverifikasi' : 'Gagal';
            return redirect()->route('payments.show', $payment)
                ->with('success', "Pembayaran berhasil diverifikasi menjadi: {$statusText}");

        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function receipt(Payment $payment, Request $request)
    {
        $payment->load(['student.institution', 'student.classRoom', 'billingRecord']);
        
        // Get academic years for dropdown
        $academicYears = \App\Models\AcademicYear::orderBy('year_start', 'desc')->get();
        
        // Get selected academic year (default to payment's academic year)
        $selectedAcademicYearId = $request->get('academic_year_id', $payment->student->academic_year_id);
        $selectedAcademicYear = \App\Models\AcademicYear::find($selectedAcademicYearId);
        
        return view('payments.receipt', compact('payment', 'academicYears', 'selectedAcademicYear'));
    }

    public function import()
    {
        return view('payments.import');
    }

    public function downloadTemplate()
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        
        // Create Instructions Sheet
        $instructionsSheet = $spreadsheet->getActiveSheet();
        $instructionsSheet->setTitle('Petunjuk');
        
        $instructionsSheet->setCellValue('A1', 'PETUNJUK IMPORT DATA PEMBAYARAN');
        $instructionsSheet->setCellValue('A3', 'Kolom Wajib:');
        $instructionsSheet->setCellValue('A4', '1. nis - NIS siswa (harus ada di database)');
        $instructionsSheet->setCellValue('A5', '2. payment_date - Tanggal pembayaran (format: YYYY-MM-DD)');
        $instructionsSheet->setCellValue('A6', '3. total_amount - Jumlah pembayaran (angka)');
        $instructionsSheet->setCellValue('A7', '4. payment_method - Metode pembayaran (cash/transfer/qris/edc)');
        $instructionsSheet->setCellValue('A8', '5. status - Status pembayaran (pending/completed/failed/cancelled)');
        
        $instructionsSheet->setCellValue('A10', 'Kolom Opsional:');
        $instructionsSheet->setCellValue('A11', '1. notes - Catatan pembayaran');
        $instructionsSheet->setCellValue('A12', '2. billing_month - Bulan tagihan (jika kosong akan otomatis)');
        
        $instructionsSheet->setCellValue('A14', 'Catatan:');
        $instructionsSheet->setCellValue('A15', '- Format tanggal harus YYYY-MM-DD (contoh: 2024-01-15)');
        $instructionsSheet->setCellValue('A16', '- Metode pembayaran: cash, transfer, qris');
        $instructionsSheet->setCellValue('A17', '- Status: pending, completed, failed, cancelled');
        
        // Create Data Sheet
        $dataSheet = $spreadsheet->createSheet();
        $dataSheet->setTitle('Data Pembayaran');
        
        // Set headers
        $dataSheet->setCellValue('A1', 'nis');
        $dataSheet->setCellValue('B1', 'payment_date');
        $dataSheet->setCellValue('C1', 'total_amount');
        $dataSheet->setCellValue('D1', 'payment_method');
        $dataSheet->setCellValue('E1', 'status');
        $dataSheet->setCellValue('F1', 'notes');
        $dataSheet->setCellValue('G1', 'billing_month');
        
        // Add sample data
        $dataSheet->setCellValue('A2', '12345');
        $dataSheet->setCellValue('B2', '2024-01-15');
        $dataSheet->setCellValue('C2', '500000');
        $dataSheet->setCellValue('D2', 'cash');
        $dataSheet->setCellValue('E2', 'completed');
        $dataSheet->setCellValue('F2', 'Pembayaran SPP Januari 2024');
        $dataSheet->setCellValue('G2', 'Januari');
        
        // Auto-size columns
        foreach (range('A', 'G') as $col) {
            $dataSheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        // Create Reference Sheet
        $referenceSheet = $spreadsheet->createSheet();
        $referenceSheet->setTitle('Referensi');
        
        $referenceSheet->setCellValue('A1', 'REFERENSI DATA');
        $referenceSheet->setCellValue('A3', 'Metode Pembayaran:');
        $referenceSheet->setCellValue('A4', 'cash, transfer, qris, edc');
        
        $referenceSheet->setCellValue('A6', 'Status Pembayaran:');
        $referenceSheet->setCellValue('A7', 'pending, completed, failed, cancelled');
        
        $referenceSheet->setCellValue('A9', 'Format Tanggal:');
        $referenceSheet->setCellValue('A10', 'YYYY-MM-DD (contoh: 2024-01-15)');
        
        // Set active sheet back to instructions
        $spreadsheet->setActiveSheetIndex(0);
        
        // Create response
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $filename = 'template_import_pembayaran_' . date('Y-m-d') . '.xlsx';
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        
        $writer->save('php://output');
        exit;
    }

    public function importData(Request $request)
    {
        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls|max:5120',
        ]);

        try {
            $file = $request->file('excel_file');
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getPathname());
            $worksheet = $spreadsheet->getSheetByName('Data Pembayaran');
            
            if (!$worksheet) {
                return back()->with('error', 'Sheet "Data Pembayaran" tidak ditemukan dalam file Excel');
            }
            
            $rows = $worksheet->toArray();
            $headers = array_map('strtolower', $rows[0]);
            $dataRows = array_slice($rows, 1);
            
            $imported = 0;
            $errors = [];
            
            foreach ($dataRows as $index => $row) {
                if (empty(array_filter($row))) continue; // Skip empty rows
                
                try {
                    $data = array_combine($headers, $row);
                    
                    // Validate required fields
                    if (empty($data['nis']) || empty($data['payment_date']) || empty($data['total_amount']) || 
                        empty($data['payment_method']) || empty($data['status'])) {
                        $errors[] = "Baris " . ($index + 2) . ": Data tidak lengkap";
                        continue;
                    }
                    
                    // Find student by NIS
                    $student = Student::where('nis', $data['nis'])->first();
                    if (!$student) {
                        $errors[] = "Baris " . ($index + 2) . ": NIS {$data['nis']} tidak ditemukan";
                        continue;
                    }
                    
                    // Find billing record, create if not exists
                    $billingRecord = BillingRecord::where('student_id', $student->id)
                        ->where('status', 'active')
                        ->first();
                    
                    if (!$billingRecord) {
                        // Auto-create billing record if not exists
                        $studentController = new \App\Http\Controllers\StudentController();
                        $studentController->ensureBillingRecords($student);
                        
                        // Try to find again
                        $billingRecord = BillingRecord::where('student_id', $student->id)
                            ->where('status', 'active')
                            ->first();
                        
                        if (!$billingRecord) {
                            $errors[] = "Baris " . ($index + 2) . ": Tidak dapat membuat tagihan untuk siswa {$student->name} (struktur biaya tidak ditemukan)";
                            continue;
                        }
                    }
                    
                    // Create payment
                    $payment = Payment::create([
                        'student_id' => $student->id,
                        'billing_record_id' => $billingRecord->id,
                        'kasir_id' => auth()->id(), // Set kasir_id to current user
                        'payment_date' => $data['payment_date'],
                        'total_amount' => $data['total_amount'],
                        'payment_method' => $data['payment_method'],
                        'status' => $data['status'],
                        'notes' => $data['notes'] ?? null,
                        'receipt_number' => 'RCP-' . date('Ymd') . '-' . str_pad(Payment::count() + 1, 4, '0', STR_PAD_LEFT),
                    ]);
                    
                    // Update billing record only if verified/completed
                    if (in_array($payment->status, [Payment::STATUS_VERIFIED, Payment::STATUS_COMPLETED])) {
                        $billingRecord->update([
                            'remaining_balance' => max(0, $billingRecord->remaining_balance - $payment->total_amount)
                        ]);
                    }
                    
                    $imported++;
                    
                } catch (\Exception $e) {
                    $errors[] = "Baris " . ($index + 2) . ": " . $e->getMessage();
                }
            }
            
            $message = "Berhasil import {$imported} data pembayaran";
            if (!empty($errors)) {
                $message .= ". Beberapa data gagal diimport.";
                return redirect()->route('payments.import')->with('import_errors', $errors);
            }
            
            return redirect()->route('payments.index')->with('success', $message);
            
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

}
