<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Student;
use App\Models\AcademicYear;
use App\Models\FeeStructure;
use App\Observers\StudentObserver;
use App\Observers\AcademicYearObserver;
use App\Observers\FeeStructureObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Daftarkan Observer untuk Student
        Student::observe(StudentObserver::class);
        
        // Daftarkan Observer untuk AcademicYear
        AcademicYear::observe(AcademicYearObserver::class);
        
        // Daftarkan Observer untuk FeeStructure
        FeeStructure::observe(FeeStructureObserver::class);
    }
}
