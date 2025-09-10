<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Institution;
use App\Models\AcademicYear;
use App\Models\ClassModel;
use App\Models\Student;
use App\Models\Payment;
use App\Models\BillingRecord;
use App\Models\FeeStructure;
use Illuminate\Support\Facades\Hash;

class SimpleDemoSeeder extends Seeder
{
    public function run()
    {
        // Buat institution
        $institution = Institution::firstOrCreate(
            ['name' => 'Yayasan Mu\'allimin Mu\'allimat YASMU'],
            [
                'address' => 'Jl. Contoh No. 123, Kota, Provinsi',
                'phone' => '08123456789',
                'email' => 'info@yasmu.ac.id',
                'is_active' => true,
            ]
        );

        // Buat academic year
        $academicYear = AcademicYear::firstOrCreate(
            [
                'year_start' => '2024',
                'year_end' => '2025'
            ],
            [
                'status' => 'active',
                'is_current' => true,
                'description' => 'Tahun Ajaran 2024/2025',
            ]
        );

        // Buat class
        $class = ClassModel::firstOrCreate(
            ['class_name' => 'Kelas X'],
            [
                'grade_level' => 'SMA',
                'institution_id' => $institution->id,
                'academic_year_id' => $academicYear->id,
                'capacity' => 30,
                'is_active' => true,
            ]
        );

        // Buat fee structure
        $feeStructure = FeeStructure::firstOrCreate(
            [
                'institution_id' => $institution->id,
                'academic_year_id' => $academicYear->id,
                'class_id' => $class->id,
            ],
            [
                'monthly_amount' => 500000,
                'yearly_amount' => 6000000,
                'scholarship_discount' => 0,
                'description' => 'Biaya SPP Kelas X',
                'is_active' => true,
            ]
        );

        // Buat student
        $student = Student::firstOrCreate(
            ['nis' => '2024001'],
            [
                'name' => 'Ahmad Fadillah',
                'institution_id' => $institution->id,
                'academic_year_id' => $academicYear->id,
                'class_id' => $class->id,
                'parent_name' => 'Bapak Fadillah',
                'parent_phone' => '08123456789',
                'address' => 'Jl. Siswa No. 1, Jakarta',
                'status' => 'active',
                'enrollment_date' => '2024-07-01',
            ]
        );

        // Buat billing record
        $billingRecord = BillingRecord::firstOrCreate(
            [
                'student_id' => $student->id,
                'fee_structure_id' => $feeStructure->id,
                'billing_month' => 'Agustus 2024',
            ],
            [
                'origin_year' => '2024-2025',
                'origin_class' => 'Kelas X',
                'amount' => 500000,
                'remaining_balance' => 500000,
                'status' => 'active',
                'due_date' => '2024-08-31',
            ]
        );

        // Buat payment
        $payment = Payment::firstOrCreate(
            ['receipt_number' => 'RCP-2024-001'],
            [
                'student_id' => $student->id,
                'payment_date' => today(),
                'total_amount' => 500000,
                'payment_method' => 'cash',
                'reference_number' => 'REF-001',
                'kasir_id' => 1, // User ID dari kasir
                'status' => 'completed',
            ]
        );

        $this->command->info('Demo data berhasil dibuat!');
        $this->command->info('Institution: ' . $institution->name);
        $this->command->info('Student: ' . $student->name);
        $this->command->info('Payment: Rp ' . number_format($payment->total_amount, 0, ',', '.'));
    }
}
