<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AppSetting;
use App\Models\Institution;

class AppSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Create default institution if not exists
        $institution = Institution::firstOrCreate(
            ['name' => 'Yayasan Mu\'allimin Mu\'allimat YASMU'],
            [
                'address' => 'Jl. Manyar, Gresik, Jawa Timur',
                'phone' => '08123456789',
                'email' => 'info@yasmu.ac.id',
                'is_active' => true,
            ]
        );

        AppSetting::create([
            'app_name' => 'SISPEMA YASMU',
            'app_city' => 'Manyar',
            'app_description' => 'Sistem Pembayaran Akademik Yayasan Mu\'allimin Mu\'allimat YASMU',
            'primary_color' => '#2563eb',
            'secondary_color' => '#1e40af',
        ]);
    }
}
