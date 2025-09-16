<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ActivityPlan;
use App\Models\ActivityRealization;
use App\Models\CashBook;
use App\Models\Category;
use App\Models\AcademicYear;
use App\Models\Student;
use Barryvdh\DomPDF\Facade\Pdf;

class FinancialReportController extends Controller
{
    public function index()
    {
        return view('financial.reports.index');
    }

    public function activityPlans(Request $request)
    {
        $query = ActivityPlan::with(['academicYear', 'category']);

        if ($request->filled('academic_year_id')) {
            $query->where('academic_year_id', $request->academic_year_id);
        }

        $activityPlans = $query->orderBy('start_date', 'desc')->get();
        $academicYears = AcademicYear::where('status', 'active')->get();

        if ($request->has('export_pdf')) {
            $pdf = Pdf::loadView('financial.reports.activity-plans-pdf', compact('activityPlans'));
            return $pdf->download('rencana-kegiatan-' . date('Y-m-d') . '.pdf');
        }

        return view('financial.reports.activity-plans', compact('activityPlans', 'academicYears'));
    }

    public function realizations(Request $request)
    {
        $query = ActivityPlan::with(['academicYear', 'category', 'realizations']);

        if ($request->filled('academic_year_id')) {
            $query->where('academic_year_id', $request->academic_year_id);
        }

        $activityPlans = $query->orderBy('start_date', 'desc')->get();
        $academicYears = AcademicYear::where('status', 'active')->get();

        if ($request->has('export_pdf')) {
            $pdf = Pdf::loadView('financial.reports.realizations-pdf', compact('activityPlans'));
            return $pdf->download('laporan-realisasi-' . date('Y-m-d') . '.pdf');
        }

        return view('financial.reports.realizations', compact('activityPlans', 'academicYears'));
    }

    public function cashBook(Request $request)
    {
        $query = CashBook::query();

        if ($request->filled('start_date')) {
            $query->where('date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->where('date', '<=', $request->end_date);
        }

        $entries = $query->orderBy('date')->orderBy('id')->get();
        $currentBalance = CashBook::getCurrentBalance();

        if ($request->has('export_pdf')) {
            $pdf = Pdf::loadView('financial.reports.cash-book-pdf', compact('entries', 'currentBalance'));
            return $pdf->download('buku-kas-umum-' . date('Y-m-d') . '.pdf');
        }

        return view('financial.reports.cash-book', compact('entries', 'currentBalance'));
    }

    public function balanceSheet(Request $request)
    {
        $query = CashBook::query();

        if ($request->filled('start_date')) {
            $query->where('date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->where('date', '<=', $request->end_date);
        }

        $entries = $query->with(['payment.student.classRoom.feeStructures', 'realization.plan'])
            ->orderBy('date')->orderBy('id')->get();
        
        // Calculate totals
        $totalDebit = $entries->sum('debit');
        $totalCredit = $entries->sum('credit');
        $currentBalance = CashBook::getCurrentBalance();

        // Get category summaries
        $pemasukanCategories = Category::pemasukan()->active()->get();
        $pengeluaranCategories = Category::pengeluaran()->active()->get();

        // Perhitungan otomatis untuk neraca
        $totalPiutang = 0; // Total tagihan SPP yang belum dibayar
        $totalHutang = 0; // Total hutang yang belum dibayar
        $totalModal = $currentBalance + ($totalCredit - $totalDebit); // Modal = Kas + Laba Bersih
        
        // Hitung hutang jangka pendek dari realisasi yang belum dibayar
        $unpaidRealizations = ActivityRealization::where('status', 'draft')
            ->where('transaction_type', 'debit')
            ->get();
        
        foreach($unpaidRealizations as $realization) {
            $totalHutang += $realization->total_amount;
        }
        
        // Hitung modal dari akumulasi laba bersih
        $totalModal = $currentBalance + ($totalCredit - $totalDebit);
        
        // Hitung piutang SPP dari tagihan yang belum dibayar
        $students = Student::with(['billingRecords'])->get();
        
        foreach($students as $student) {
            foreach($student->billingRecords as $billing) {
                $totalObligation = $billing->amount; // Total kewajiban tahunan
                $totalPaid = $student->payments()
                    ->whereIn('status', ['verified', 'completed'])
                    ->sum('total_amount');
                $remaining = $totalObligation - $totalPaid;
                if($remaining > 0) {
                    $totalPiutang += $remaining;
                }
            }
        }

        if ($request->has('export_pdf')) {
            $pdf = Pdf::loadView('financial.reports.balance-sheet-pdf', compact(
                'entries', 'totalDebit', 'totalCredit', 'currentBalance',
                'pemasukanCategories', 'pengeluaranCategories',
                'totalPiutang', 'totalHutang', 'totalModal'
            ));
            return $pdf->download('neraca-keuangan-' . date('Y-m-d') . '.pdf');
        }

        return view('financial.reports.balance-sheet', compact(
            'entries', 'totalDebit', 'totalCredit', 'currentBalance',
            'pemasukanCategories', 'pengeluaranCategories',
            'totalPiutang', 'totalHutang', 'totalModal'
        ));
    }
}
