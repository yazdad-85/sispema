<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ScholarshipCategorySeeder extends Seeder
{
    public function run()
    {
        $categories = [
            [
                'name' => 'Yatim-Piatu',
                'discount_percentage' => 100.00,
                'description' => 'Siswa yatim-piatu (tidak memiliki ayah dan ibu)',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Yatim',
                'discount_percentage' => 75.00,
                'description' => 'Siswa yatim (tidak memiliki ayah)',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Piatu',
                'discount_percentage' => 75.00,
                'description' => 'Siswa piatu (tidak memiliki ibu)',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Anak Guru/Tendik/Karyawan',
                'discount_percentage' => 50.00,
                'description' => 'Siswa anak guru, tenaga kependidikan, atau karyawan',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Tidak Mampu',
                'discount_percentage' => 25.00,
                'description' => 'Siswa dari keluarga tidak mampu',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('scholarship_categories')->insert($categories);
    }
}
