<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Payment;
use App\Services\SppFinancialService;

class ProcessSinglePayment extends Command
{
    protected $signature = 'spp:process-payment {payment_id : ID pembayaran} {--force : Jalankan tanpa konfirmasi}';

    protected $description = 'Process one verified/completed payment to create realization and cash-book entries';

    public function handle()
    {
        $paymentId = (int) $this->argument('payment_id');
        $payment = Payment::with(['student.scholarshipCategory', 'student.classRoom', 'student.institution'])->find($paymentId);
        if (!$payment) {
            $this->error("Payment {$paymentId} tidak ditemukan.");
            return 1;
        }

        if (!in_array($payment->status, [Payment::STATUS_VERIFIED, Payment::STATUS_COMPLETED])) {
            $this->warn('Status pembayaran bukan verified/completed. Tidak diproses.');
            return 0;
        }

        if (!$this->option('force')) {
            if (!$this->confirm("Proses pembayaran #{$payment->id} sebesar Rp " . number_format($payment->total_amount, 0, ',', '.') . "?")) {
                $this->info('Dibatalkan.');
                return 0;
            }
        }

        $service = new SppFinancialService();
        $realization = $service->processSppPayment($payment);
        if ($realization) {
            $this->info('Berhasil memproses pembayaran dan membuat entri keuangan.');
            return 0;
        }
        $this->warn('Tidak ada perubahan (mungkin sudah pernah diproses).');
        return 0;
    }
}


