<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Institution;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Super Admin
        $superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@yasmu.ac.id',
            'password' => Hash::make('password'),
            'role' => 'super_admin',
        ]);

        // Staff 1 - Akses SMA dan SMP
        $staff1 = User::create([
            'name' => 'Staff 1',
            'email' => 'staff1@yasmu.ac.id',
            'password' => Hash::make('password'),
            'role' => 'staff',
        ]);

        // Staff 2 - Akses SMK
        $staff2 = User::create([
            'name' => 'Staff 2',
            'email' => 'staff2@yasmu.ac.id',
            'password' => Hash::make('password'),
            'role' => 'staff',
        ]);

        // Get institutions
        $sma = Institution::where('name', 'like', '%SMA%')->first();
        $smp = Institution::where('name', 'like', '%SMP%')->first();
        $smk = Institution::where('name', 'like', '%SMK%')->first();

        // Attach institutions to staff
        if ($sma && $smp) {
            $staff1->institutions()->attach([$sma->id, $smp->id]);
        }
        if ($smk) {
            $staff2->institutions()->attach($smk->id);
        }
    }
}
