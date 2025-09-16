<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Payment;
use App\Models\BillingRecord;

class RestoreBillingFromPayments extends Command
{
    protected $signature = 'sispema:restore-billing {payment_id : ID pembayaran} {--force : Jalankan tanpa konfirmasi}';

    protected $description = 'Hitung ulang remaining_balance billing dari pembayaran yang verified/completed untuk billing terkait';

    public function handle()
    {
        $paymentId = (int) $this->argument('payment_id');
        /** @var Payment|null $payment */
        $payment = Payment::with('billingRecord')->find($paymentId);
        if (!$payment) {
            $this->error("Payment {$paymentId} tidak ditemukan.");
            return 1;
        }

        $billing = $payment->billingRecord;
        if (!$billing) {
            $this->error('Payment tidak memiliki billing record.');
            return 1;
        }

        if (!$this->option('force')) {
            if (!$this->confirm("Recompute remaining_balance untuk billing #{$billing->id}?")) {
                $this->info('Dibatalkan.');
                return 0;
            }
        }

        DB::beginTransaction();
        try {
            // Jumlahkan hanya pembayaran verified/completed untuk billing ini
            $paid = Payment::where('billing_record_id', $billing->id)
                ->whereIn('status', [Payment::STATUS_VERIFIED, Payment::STATUS_COMPLETED])
                ->sum('total_amount');

            $newRemaining = max(0, (float)$billing->amount - (float)$paid);
            $billing->remaining_balance = $newRemaining;
            $billing->save();

            DB::commit();
            $this->info("Remaining balance diperbarui: Rp " . number_format($newRemaining, 0, ',', '.'));
            return 0;
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Gagal memperbarui remaining balance: ' . $e->getMessage());
            return 1;
        }
    }
}


