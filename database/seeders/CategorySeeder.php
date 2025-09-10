<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $categories = [
            // Pemasukan
            ['name' => 'Pembayaran SPP', 'type' => 'pemasukan', 'is_active' => true],
            ['name' => 'Donasi', 'type' => 'pemasukan', 'is_active' => true],
            ['name' => 'Bantuan Pemerintah', 'type' => 'pemasukan', 'is_active' => true],
            ['name' => 'Lain-lain Pemasukan', 'type' => 'pemasukan', 'is_active' => true],
            
            // Pengeluaran
            ['name' => 'Gaji Guru', 'type' => 'pengeluaran', 'is_active' => true],
            ['name' => 'Operasional Sekolah', 'type' => 'pengeluaran', 'is_active' => true],
            ['name' => 'Pemeliharaan Gedung', 'type' => 'pengeluaran', 'is_active' => true],
            ['name' => 'Kegiatan Siswa', 'type' => 'pengeluaran', 'is_active' => true],
            ['name' => 'Pembelian Alat/Bahan', 'type' => 'pengeluaran', 'is_active' => true],
            ['name' => 'Lain-lain Pengeluaran', 'type' => 'pengeluaran', 'is_active' => true],
        ];

        foreach ($categories as $category) {
            \App\Models\Category::create($category);
        }
    }
}
