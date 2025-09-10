<?php

return [
    /*
    |--------------------------------------------------------------------------
    | SPP Application Configuration
    |--------------------------------------------------------------------------
    |
    | Konfigurasi untuk aplikasi pembayaran SPP
    |
    */

    'app_name' => 'SPP YASMU',
    'app_version' => '1.0.0',
    
    /*
    |--------------------------------------------------------------------------
    | Payment Gateway Configuration
    |--------------------------------------------------------------------------
    */
    
    'midtrans' => [
        'merchant_id' => env('MIDTRANS_MERCHANT_ID'),
        'client_key' => env('MIDTRANS_CLIENT_KEY'),
        'server_key' => env('MIDTRANS_SERVER_KEY'),
        'is_production' => env('MIDTRANS_IS_PRODUCTION', false),
        'snap_url' => env('MIDTRANS_IS_PRODUCTION') 
            ? 'https://app.midtrans.com/snap/v1/transactions'
            : 'https://app.sandbox.midtrans.com/snap/v1/transactions',
    ],
    
    'btn_bank' => [
        'merchant_id' => env('BTN_MERCHANT_ID'),
        'terminal_id' => env('BTN_TERMINAL_ID'),
        'client_id' => env('BTN_CLIENT_ID'),
        'client_secret' => env('BTN_CLIENT_SECRET'),
        'is_production' => env('BTN_IS_PRODUCTION', false),
        'api_url' => env('BTN_IS_PRODUCTION')
            ? 'https://api.btn.co.id'
            : 'https://api-sandbox.btn.co.id',
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Receipt Configuration
    |--------------------------------------------------------------------------
    */
    
    'receipt' => [
        'prefix' => 'RCP',
        'auto_print' => true,
        'dual_column' => true,
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Billing Configuration
    |--------------------------------------------------------------------------
    */
    
    'billing' => [
        'auto_carry_over' => true,
        'default_due_date' => 10, // Tanggal jatuh tempo default
        'overdue_grace_period' => 7, // Grace period dalam hari
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Report Configuration
    |--------------------------------------------------------------------------
    */
    
    'reports' => [
        'default_format' => 'pdf',
        'available_formats' => ['pdf', 'excel'],
        'auto_backup' => true,
        'backup_frequency' => 'daily',
        'backup_time' => '02:00',
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Security Configuration
    |--------------------------------------------------------------------------
    */
    
    'security' => [
        'require_2fa_admin' => true,
        'session_timeout' => 120, // dalam menit
        'max_login_attempts' => 5,
        'lockout_duration' => 15, // dalam menit
    ],
];
