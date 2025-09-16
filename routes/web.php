<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\InstitutionController;
use App\Http\Controllers\ClassController;
use App\Http\Controllers\FeeStructureController;
use App\Http\Controllers\AcademicYearController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\ScholarshipCategoryController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ActivityPlanController;
use App\Http\Controllers\ActivityRealizationController;
use App\Http\Controllers\CashBookController;
use App\Http\Controllers\FinancialReportController;
use App\Http\Controllers\ImportLogController;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});



// Debug routes (temporary)
Route::get('/debug-info', function() {
    $debug = [
        'php_version' => PHP_VERSION,
        'laravel_version' => app()->version(),
        'user' => auth()->user() ? [
            'id' => auth()->user()->id,
            'name' => auth()->user()->name,
            'email' => auth()->user()->email,
            'role' => auth()->user()->role
        ] : null,
        'user_role' => auth()->user() ? auth()->user()->role : 'tidak ada user',
        'session_id' => session()->getId(),
        'is_authenticated' => auth()->check(),
        'phpspreadsheet_loaded' => class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet'),
        'memory_limit' => ini_get('memory_limit'),
        'max_execution_time' => ini_get('max_execution_time'),
        'route_info' => [
            'current_route' => request()->route() ? request()->route()->getName() : 'tidak ada route',
            'middleware' => request()->route() ? request()->route()->middleware() : 'tidak ada route',
            'url' => request()->url(),
            'method' => request()->method()
        ]
    ];
    return response()->json($debug, 200, [], JSON_PRETTY_PRINT);
});

Route::get('/debug-view', function() {
    $debug = [
        'php_version' => PHP_VERSION,
        'laravel_version' => app()->version(),
        'user' => auth()->user() ? [
            'id' => auth()->user()->id,
            'name' => auth()->user()->name,
            'email' => auth()->user()->email,
            'role' => auth()->user()->role
        ] : null,
        'user_role' => auth()->user() ? auth()->user()->role : 'tidak ada user',
        'session_id' => session()->getId(),
        'is_authenticated' => auth()->check(),
        'phpspreadsheet_loaded' => class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet'),
        'memory_limit' => ini_get('memory_limit'),
        'max_execution_time' => ini_get('max_execution_time'),
        'route_info' => [
            'current_route' => request()->route() ? request()->route()->getName() : 'tidak ada route',
            'middleware' => request()->route() ? request()->route()->middleware() : 'tidak ada route',
            'url' => request()->url(),
            'method' => request()->method()
        ]
    ];
    return view('debug', compact('debug'));
});

// Export template route (Super Admin only) - di luar group untuk menghindari masalah nested
Route::get('/classes/export-template', [\App\Http\Controllers\ClassController::class, 'exportTemplate'])
    ->name('classes.export-template')
    ->middleware(['auth', 'role:super_admin']);

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

// Protected routes
Route::middleware(['auth'])->group(function () {
    
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Students
    Route::get('/students/import-template', [StudentController::class, 'importTemplate'])->name('students.import-template');
    Route::get('/students/download-template', [StudentController::class, 'downloadTemplate'])->name('students.download-template');
    Route::post('/students/import', [StudentController::class, 'import'])->name('students.import');
    Route::resource('students', StudentController::class);
    Route::get('/students/{student}/print', [StudentController::class, 'print'])->name('students.print');
    
    // Student Promotions
    Route::get('/student-promotions', [App\Http\Controllers\StudentPromotionController::class, 'index'])->name('student-promotions.index');
    Route::post('/student-promotions', [App\Http\Controllers\StudentPromotionController::class, 'promote'])->name('student-promotions.promote');
    Route::post('/student-promotions/bulk', [App\Http\Controllers\StudentPromotionController::class, 'bulkPromote'])->name('student-promotions.bulk-promote');
    Route::get('/student-promotions/{student}/payment-history', [App\Http\Controllers\StudentPromotionController::class, 'showPaymentHistory'])->name('student-promotions.payment-history');
    Route::get('/api/student-payment-summary/{student}', [App\Http\Controllers\StudentPromotionController::class, 'getStudentPaymentSummary'])->name('student-promotions.payment-summary');
    
    // Staff/Kasir Student Data View
    Route::get('/staff/students', [StudentController::class, 'staffIndex'])->name('staff.students.index');
    Route::get('/staff/students/{student}', [StudentController::class, 'staffShow'])->name('staff.students.show');
    
    // Super Admin only
    Route::middleware(['role:super_admin'])->group(function () {
        Route::resource('institutions', InstitutionController::class);
        Route::resource('academic-years', AcademicYearController::class);
        Route::resource('classes', ClassController::class);
        Route::post('/classes/import', [ClassController::class, 'import'])->name('classes.import');
        Route::resource('scholarship-categories', ScholarshipCategoryController::class);
        Route::post('/academic-years/{academicYear}/set-current', [AcademicYearController::class, 'setCurrent'])->name('academic-years.set-current');

        // User management (CRUD)
        Route::get('/users', [\App\Http\Controllers\UserManagementController::class, 'index'])->name('users.index');
        Route::get('/users/create', [\App\Http\Controllers\UserManagementController::class, 'create'])->name('users.create');
        Route::post('/users', [\App\Http\Controllers\UserManagementController::class, 'store'])->name('users.store');
        Route::get('/users/{user}/edit', [\App\Http\Controllers\UserManagementController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [\App\Http\Controllers\UserManagementController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [\App\Http\Controllers\UserManagementController::class, 'destroy'])->name('users.destroy');
    });
    
    // Fee Structures (Admin Pusat & Admin Lembaga)
    Route::resource('fee-structures', FeeStructureController::class);
    Route::post('/fee-structures/find-by-level', [FeeStructureController::class, 'findByLevel'])->name('fee-structures.find-by-level');
    Route::post('/fee-structures/find-by-level-only', [FeeStructureController::class, 'findByLevelOnly'])->name('fee-structures.find-by-level-only');
    Route::post('/fee-structures/copy-from-previous', [FeeStructureController::class, 'copyFromPreviousYear'])->name('fee-structures.copy-from-previous');
    
    // Test route untuk verifikasi sistem level
    Route::get('/test-level-system', function() {
        $classes = \App\Models\ClassModel::with('institution')->get();
        $feeStructures = \App\Models\FeeStructure::with(['institution', 'class'])->get();
        
        return view('test.level-system', compact('classes', 'feeStructures'));
    })->name('test-level-system');
    
    // Route untuk membuat struktur biaya berdasarkan level
    Route::post('/fee-structures/create-for-all-levels', [FeeStructureController::class, 'createForAllLevels'])->name('fee-structures.create-for-all-levels');
    
    // Payment routes - HARUS SEBELUM resource route
    Route::get('/payments/import', [PaymentController::class, 'import'])->name('payments.import');
    Route::get('/payments/download-template', [PaymentController::class, 'downloadTemplate'])->name('payments.download-template');
    Route::post('/payments/import', [PaymentController::class, 'importData'])->name('payments.import');
    
    // Payments resource route - HARUS SETELAH route spesifik
    Route::resource('payments', PaymentController::class);
    
    // Additional payment routes
    Route::get('/payments/{payment}/receipt', [PaymentController::class, 'receipt'])->name('payments.receipt');
    Route::post('/payments/{payment}/verify', [PaymentController::class, 'verify'])->name('payments.verify');
    
    // API for dynamic class loading
    Route::get('/api/institutions/{institution}/classes', function($institution) {
        $classes = \App\Models\ClassModel::where('institution_id', $institution)->get(['id', 'class_name']);
        return response()->json(['classes' => $classes]);
    })->name('api.institutions.classes');
    
    // API: search students by NIS within institution
    Route::get('/api/institutions/{institution}/students', function(\Illuminate\Http\Request $request, $institution) {
        $query = trim($request->get('query', ''));
        $students = \App\Models\Student::where('institution_id', $institution)
            ->when($query !== '', function($q) use ($query) {
                $q->where('nis', 'like', $query.'%');
            })
            ->with('classRoom:id,class_name')
            ->limit(20)
            ->get(['id','nis','name','class_id']);
        $mapped = $students->map(function($s){
            return [
                'id' => $s->id,
                'nis' => $s->nis,
                'name' => $s->name,
                'class_name' => optional($s->classRoom)->class_name,
            ];
        });
        return response()->json(['students' => $mapped]);
    })->name('api.institutions.students');
    
    // API for student billing records
    Route::get('/api/students/{student}/billing-records', function($student) {
        $studentModel = \App\Models\Student::with(['classRoom', 'academicYear'])->findOrFail($student);

        // Create or use a single ANNUAL billing record for the current academic year
        $originYear = $studentModel->academicYear
            ? ($studentModel->academicYear->year_start.'-'.$studentModel->academicYear->year_end)
            : (string) now()->year;

        $annual = \App\Models\BillingRecord::where('student_id', $studentModel->id)
            ->where('origin_year', $originYear)
            ->where('notes', 'ANNUAL')
            ->first();

        if (!$annual && $studentModel->classRoom) {
            $feeStructure = \App\Models\BillingRecord::findFeeStructureByStudentLevel($studentModel);
            if ($feeStructure) {
                $dueDate = \Carbon\Carbon::create($studentModel->academicYear->year_end ?? now()->year, 12, 31)->toDateString();
                $annual = \App\Models\BillingRecord::create([
                    'student_id' => $studentModel->id,
                    'fee_structure_id' => $feeStructure->id,
                    'origin_year' => (string)$originYear,
                    'origin_class' => $studentModel->classRoom->class_name ?? '-',
                    'amount' => $feeStructure->yearly_amount,
                    'remaining_balance' => $feeStructure->yearly_amount,
                    'status' => 'active',
                    'due_date' => $dueDate,
                    'billing_month' => 'Tahunan '.$originYear,
                    'notes' => 'ANNUAL',
                ]);
            }
        }

        $result = [];
        if ($annual) {
            $result = [[
                'id' => $annual->id,
                'billing_month' => $annual->billing_month,
                'amount' => $annual->amount,
                'remaining_balance' => $annual->remaining_balance,
                'due_date' => $annual->due_date,
            ]];
        }

        return response()->json(['billing_records' => $result]);
    })->name('api.students.billing-records');

    // API: scholarship info for student
    Route::get('/api/students/{student}/scholarship', function($student){
        $s = \App\Models\Student::with('scholarshipCategory')->findOrFail($student);
        $sch = $s->scholarshipCategory;
        return response()->json([
            'name' => $sch->name ?? null,
            'discount_percentage' => $sch->discount_percentage ?? 0,
        ]);
    })->name('api.students.scholarship');
    
    // API: Outstanding summary per academic year (current year and carryover)
    Route::get('/api/students/{student}/outstanding-summary', function($student) {
        $studentModel = \App\Models\Student::with('academicYear')->findOrFail($student);
        $currentYearKey = $studentModel->academicYear
            ? ($studentModel->academicYear->year_start.'-'.$studentModel->academicYear->year_end)
            : null;
        
        // Get annual record for current year
        $currentYearRecord = \App\Models\BillingRecord::where('student_id', $studentModel->id)
            ->where('origin_year', $currentYearKey)
            ->where('notes', 'ANNUAL')
            ->first(['remaining_balance', 'amount']);
        
        $totalCurrentYear = $currentYearRecord ? (float)$currentYearRecord->remaining_balance : 0;
        $totalCurrentYearAmount = $currentYearRecord ? (float)$currentYearRecord->amount : 0;
        
        // Gunakan field previous_debt pada siswa sebagai sumber kebenaran sisa tahun sebelumnya
        $breakdown = [];
        $totalPreviousYears = (float) ($studentModel->previous_debt ?? 0);
        
        $grandTotal = $totalCurrentYear + $totalPreviousYears;
        
        return response()->json([
            'current_year' => $currentYearKey,
            'total_current_year' => $totalCurrentYear,
            'total_current_year_amount' => $totalCurrentYearAmount,
            'total_previous_years' => $totalPreviousYears,
            'grand_total' => $grandTotal,
            'per_year' => $breakdown,
        ]);
    })->name('api.students.outstanding-summary');
    
    // Reports
    Route::get('/reports/outstanding', [ReportController::class, 'outstanding'])->name('reports.outstanding');
    Route::get('/reports/payments', [ReportController::class, 'payments'])->name('reports.payments');
    Route::get('/reports/students', [ReportController::class, 'students'])->name('reports.students');
    
    // Settings
    Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
    Route::post('/settings/update', [SettingController::class, 'update'])->name('settings.update');
    Route::post('/settings/change-password', [SettingController::class, 'changePassword'])->name('settings.change-password');
    Route::post('/settings/app', [SettingController::class, 'updateAppSettings'])->name('settings.app');
    
    // Export reports
    Route::get('/reports/outstanding/export/{format}', [ReportController::class, 'exportOutstanding'])->name('reports.outstanding.export');
    Route::get('/reports/payments/export', [ReportController::class, 'exportPayments'])->name('reports.payments.export');
    
    // API for app settings - requires authentication
    Route::get('/api/app-settings/colors', function() {
        $settings = \App\Models\AppSetting::first();
        return response()->json([
            'primary_color' => $settings->primary_color ?? '#2563eb',
            'secondary_color' => $settings->secondary_color ?? '#1e40af'
        ]);
    });
});

// Financial Management Routes
Route::middleware(['auth'])->group(function () {
    // Categories
    Route::resource('categories', CategoryController::class);
    
    // Activity Plans
    Route::resource('activity-plans', ActivityPlanController::class);
    
    // Activity Realizations
    Route::resource('activity-realizations', ActivityRealizationController::class);
    
                // Financial Reports
            Route::prefix('financial-reports')->name('financial-reports.')->group(function () {
                Route::get('/', [FinancialReportController::class, 'index'])->name('index');
                Route::get('/activity-plans', [FinancialReportController::class, 'activityPlans'])->name('activity-plans');
                Route::get('/realizations', [FinancialReportController::class, 'realizations'])->name('realizations');
                Route::get('/cash-book', [FinancialReportController::class, 'cashBook'])->name('cash-book');
                Route::get('/balance-sheet', [FinancialReportController::class, 'balanceSheet'])->name('balance-sheet');
                
                // Cash Book CRUD (inside financial reports)
                Route::get('/cash-book/create', [CashBookController::class, 'create'])->name('cash-book.create');
                Route::post('/cash-book', [CashBookController::class, 'store'])->name('cash-book.store');
                Route::delete('/cash-book/{cashBook}', [CashBookController::class, 'destroy'])->name('cash-book.destroy');
                
                // Import Logs
                Route::get('/import-logs', [ImportLogController::class, 'index'])->name('import-logs.index');
                Route::get('/import-logs/{logId}', [ImportLogController::class, 'show'])->name('import-logs.show');
                Route::get('/import-logs/{logId}/download', [ImportLogController::class, 'download'])->name('import-logs.download');
                Route::delete('/import-logs/clear', [ImportLogController::class, 'clear'])->name('import-logs.clear');
            });
});

// Payment gateway callbacks
Route::post('/payment/midtrans/callback', [PaymentController::class, 'midtransCallback'])->name('payment.midtrans.callback');
Route::post('/payment/btn/callback', [PaymentController::class, 'btnCallback'])->name('payment.btn.callback');
