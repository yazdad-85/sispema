<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Student;
use App\Models\Payment;
use App\Models\BillingRecord;
use App\Models\Institution;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        // Default values
        $totalStudents = 0;
        $totalPaymentsToday = 0;
        $activeArrears = 0;
        $totalInstitutions = 0;
        $recentPayments = collect();
        
        if ($user) {
            if ($user->isSuperAdmin()) {
                // Super Admin: Lihat semua data
                $totalStudents = Student::count();
                $totalPaymentsToday = Payment::whereDate('created_at', today())->sum('total_amount');
                $activeArrears = BillingRecord::where('remaining_balance', '>', 0)->count();
                $totalInstitutions = Institution::count();
                $recentPayments = Payment::with(['billingRecord.student.institution'])
                                        ->latest()
                                        ->take(5)
                                        ->get();
            } elseif ($user->isStaff()) {
                // Staff: Hanya lihat data lembaga yang diizinkan
                $allowedInstitutionIds = $user->institutions()->pluck('institutions.id');
                
                $totalStudents = Student::whereIn('institution_id', $allowedInstitutionIds)->count();
                $totalPaymentsToday = Payment::whereHas('billingRecord.student', function($query) use ($allowedInstitutionIds) {
                    $query->whereIn('institution_id', $allowedInstitutionIds);
                })->whereDate('created_at', today())->sum('total_amount');
                $activeArrears = BillingRecord::whereHas('student', function($query) use ($allowedInstitutionIds) {
                    $query->whereIn('institution_id', $allowedInstitutionIds);
                })->where('remaining_balance', '>', 0)->count();
                $totalInstitutions = $user->institutions()->count();
                $recentPayments = Payment::whereHas('billingRecord.student', function($query) use ($allowedInstitutionIds) {
                    $query->whereIn('institution_id', $allowedInstitutionIds);
                })->with(['billingRecord.student.institution'])
                  ->latest()
                  ->take(5)
                  ->get();
            } else {
                // Role lain (admin_pusat, kasir): Default behavior
                $totalStudents = Student::count();
                $totalPaymentsToday = Payment::whereDate('created_at', today())->sum('total_amount');
                $activeArrears = BillingRecord::where('remaining_balance', '>', 0)->count();
                $totalInstitutions = Institution::count();
                $recentPayments = Payment::with(['billingRecord.student.institution'])
                                        ->latest()
                                        ->take(5)
                                        ->get();
            }
        }
        
        return view('home', compact(
            'totalStudents', 
            'totalPaymentsToday', 
            'activeArrears', 
            'totalInstitutions',
            'recentPayments'
        ));
    }
}
