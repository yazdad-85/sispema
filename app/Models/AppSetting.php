<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'app_name',
        'app_city',
        'app_description',
        'primary_color',
        'secondary_color',
    ];

    /**
     * Get application setting value by key
     */
    public static function getValue($key, $default = null)
    {
        $setting = static::first();
        return $setting ? $setting->$key : $default;
    }

    /**
     * Get all application settings
     */
    public static function getAll()
    {
        return static::first() ?? static::create([
            'app_name' => 'SISPEMA YASMU',
            'app_city' => 'Kota',
            'app_description' => 'Sistem Pembayaran Akademik Yayasan Mu\'allimin Mu\'allimat YASMU',
            'primary_color' => '#2563eb',
            'secondary_color' => '#1e40af',
        ]);
    }
}
