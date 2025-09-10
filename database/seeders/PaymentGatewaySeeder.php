<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PaymentGatewaySeeder extends Seeder
{
    public function run()
    {
        $gateways = [
            [
                'name' => 'Midtrans',
                'api_key' => null,
                'api_secret' => null,
                'config' => json_encode([
                    'merchant_id' => '',
                    'client_key' => '',
                    'server_key' => '',
                    'is_production' => false,
                    'snap_url' => 'https://app.sandbox.midtrans.com/snap/v1/transactions',
                ]),
                'is_active' => false,
                'institution_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'BTN Bank',
                'api_key' => null,
                'api_secret' => null,
                'config' => json_encode([
                    'merchant_id' => '',
                    'terminal_id' => '',
                    'client_id' => '',
                    'client_secret' => '',
                    'is_production' => false,
                ]),
                'is_active' => false,
                'institution_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('payment_gateways')->insert($gateways);
    }
}
