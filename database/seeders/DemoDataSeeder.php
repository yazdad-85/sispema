<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    public function run()
    {
        // Create demo institution
        $institutionId = DB::table('institutions')->insertGetId([
            'name' => 'Yayasan Muhammadiyah Demo',
            'address' => 'Jl. Demo No. 123, Jakarta',
            'phone' => '021-1234567',
            'email' => 'demo@yasmu.org',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create demo academic year
        $academicYearId = DB::table('academic_years')->insertGetId([
            'year_start' => '2024',
            'year_end' => '2025',
            'status' => 'active',
            'is_current' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create demo classes
        $classIds = [];
        $classes = [
            ['class_name' => 'X IPA 1', 'grade_level' => 'X'],
            ['class_name' => 'X IPA 2', 'grade_level' => 'X'],
            ['class_name' => 'XI IPA 1', 'grade_level' => 'XI'],
            ['class_name' => 'XI IPA 2', 'grade_level' => 'XI'],
            ['class_name' => 'XII IPA 1', 'grade_level' => 'XII'],
            ['class_name' => 'XII IPA 2', 'grade_level' => 'XII'],
        ];

        foreach ($classes as $class) {
            $classIds[] = DB::table('classes')->insertGetId([
                'institution_id' => $institutionId,
                'class_name' => $class['class_name'],
                'grade_level' => $class['grade_level'],
                'academic_year_id' => $academicYearId,
                'capacity' => 40,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Create demo fee structures
        $feeStructureId = DB::table('fee_structures')->insertGetId([
            'institution_id' => $institutionId,
            'class_id' => $classIds[0],
            'academic_year_id' => $academicYearId,
            'monthly_amount' => 500000,
            'yearly_amount' => 6000000,
            'scholarship_discount' => 0,
            'description' => 'Biaya SPP Kelas X',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create demo students
        $studentId = DB::table('students')->insertGetId([
            'institution_id' => $institutionId,
            'nis' => '2024001',
            'name' => 'Ahmad Fadillah',
            'email' => 'ahmad@demo.com',
            'phone' => '08123456789',
            'address' => 'Jl. Siswa No. 1, Jakarta',
            'class_id' => $classIds[0],
            'academic_year_id' => $academicYearId,
            'scholarship_category_id' => null,
            'status' => 'active',
            'enrollment_date' => '2024-07-01',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create demo billing records
        $months = [
            'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
        ];

        foreach ($months as $index => $month) {
            DB::table('billing_records')->insert([
                'student_id' => $studentId,
                'fee_structure_id' => $feeStructureId,
                'origin_year' => '2024-2025',
                'origin_class' => 'X IPA 1',
                'amount' => 500000,
                'remaining_balance' => 500000,
                'status' => 'active',
                'due_date' => now()->addDays(10),
                'billing_month' => $month,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Create demo users
        DB::table('users')->insert([
            'name' => 'Admin Pusat',
            'email' => 'admin@yasmu.org',
            'password' => Hash::make('password'),
            'role' => 'admin_pusat',
            'institution_id' => null,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('users')->insert([
            'name' => 'Kasir Demo',
            'email' => 'kasir@yasmu.org',
            'password' => Hash::make('password'),
            'role' => 'kasir',
            'institution_id' => $institutionId,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
