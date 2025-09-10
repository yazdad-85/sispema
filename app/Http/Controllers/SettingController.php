<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Institution;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\AppSetting;

class SettingController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        // Check if user has permission to access settings
        if (!in_array($user->role, ['admin_pusat', 'super_admin', 'staff', 'kasir'])) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini');
        }
        
        $institution = null;
        $appSettings = AppSetting::getAll();
        
        if ($user->role === 'admin_pusat' || $user->role === 'super_admin') {
            $institution = Institution::first();
        } else {
            $institution = $user->institution;
        }
        
        return view('settings.index', compact('institution', 'appSettings'));
    }

    public function update(Request $request)
    {
        $user = Auth::user();
        
        // Handle logo upload if present
        if ($request->hasFile('logo')) {
            $institution = null;
            
            if ($user->role === 'admin_pusat' || $user->role === 'super_admin') {
                $institution = Institution::first();
            } else {
                $institution = $user->institution;
            }

            if (!$institution) {
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
            }

            // Validate logo file
            $request->validate([
                'logo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            // Delete old logo if exists
            if ($institution->logo && Storage::disk('public')->exists($institution->logo)) {
                Storage::disk('public')->delete($institution->logo);
            }

            // Store new logo
            $logoPath = $request->file('logo')->store('logos', 'public');
            $institution->update(['logo' => $logoPath]);
            
            return back()->with('success', 'Logo berhasil diperbarui');
        }
        
        // Handle staff personal info update
        if ($request->has('staff_name')) {
            $request->validate([
                'staff_name' => 'required|string|max:255',
                'staff_email' => 'required|email|max:255',
                'staff_phone' => 'nullable|string|max:20',
            ]);
            
            // Update user information
            $user->update([
                'name' => $request->staff_name,
                'email' => $request->staff_email,
                'phone' => $request->staff_phone,
            ]);
            
            return back()->with('success', 'Data diri berhasil diperbarui');
        }
        
        // Handle institution info update (legacy support)
        if ($request->has('name')) {
            $request->validate([
                'name' => 'required|string|max:255',
                'address' => 'nullable|string',
                'phone' => 'nullable|string|max:20',
                'email' => 'nullable|email|max:255',
            ]);

            $institution = null;
            
            if ($user->role === 'admin_pusat') {
                $institution = Institution::first();
            } else {
                $institution = $user->institution;
            }

            if (!$institution) {
                return back()->with('error', 'Lembaga tidak ditemukan');
            }

            // Update basic info
            $institution->update([
                'name' => $request->name,
                'address' => $request->address,
                'phone' => $request->phone,
                'email' => $request->email,
            ]);
            
            return back()->with('success', 'Informasi lembaga berhasil diperbarui');
        }

        return back()->with('error', 'Tidak ada data yang diperbarui');
    }

    public function changePassword(Request $request)
    {
        $user = Auth::user();
        
        // Validate request
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:8|confirmed',
            'new_password_confirmation' => 'required',
        ], [
            'current_password.required' => 'Password lama harus diisi',
            'new_password.required' => 'Password baru harus diisi',
            'new_password.min' => 'Password baru minimal 8 karakter',
            'new_password.confirmed' => 'Konfirmasi password tidak cocok',
            'new_password_confirmation.required' => 'Konfirmasi password harus diisi',
        ]);

        // Check current password
        if (!Hash::check($request->current_password, $user->password)) {
            return back()->with('error', 'Password lama tidak sesuai');
        }

        // Update password
        $user->update([
            'password' => Hash::make($request->new_password)
        ]);

        return back()->with('success', 'Password berhasil diubah');
    }

    public function updateAppSettings(Request $request)
    {
        $user = Auth::user();
        
        // Check if user has permission to update app settings
        if (!in_array($user->role, ['admin_pusat', 'super_admin'])) {
            abort(403, 'Anda tidak memiliki akses untuk mengubah pengaturan aplikasi');
        }
        
        $request->validate([
            'app_name' => 'required|string|max:255',
            'app_city' => 'required|string|max:255',
            'app_description' => 'nullable|string',
            'primary_color' => 'nullable|string|max:7',
            'secondary_color' => 'nullable|string|max:7',
        ]);

        // Get or create app settings
        $appSettings = AppSetting::getAll();
        
        // Update app settings
        $appSettings->update([
            'app_name' => $request->app_name,
            'app_city' => $request->app_city,
            'app_description' => $request->app_description,
            'primary_color' => $request->primary_color,
            'secondary_color' => $request->secondary_color,
        ]);
        
        return back()->with('success', 'Pengaturan aplikasi berhasil diperbarui');
    }
}
