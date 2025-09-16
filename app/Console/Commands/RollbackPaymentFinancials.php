<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Payment;
use App\Models\ActivityRealization;
use App\Models\CashBook;

class RollbackPaymentFinancials extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sispema:rollback-payment {payment_id : ID pembayaran} {--force : Jalankan tanpa konfirmasi}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rollback realisasi dan buku kas yang terkait pembayaran non-verified/completed';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $paymentId = (int) $this->argument('payment_id');

        /** @var Payment|null $payment */
        $payment = Payment::find($paymentId);
        if (!$payment) {
            $this->error("Payment {$paymentId} tidak ditemukan.");
            return 1;
        }

        if (in_array($payment->status, [Payment::STATUS_VERIFIED, Payment::STATUS_COMPLETED])) {
            $this->warn('Pembayaran berstatus verified/completed. Tidak dilakukan rollback.');
            return 0;
        }

        if (!$this->option('force')) {
            if (!$this->confirm("Lanjut rollback untuk payment #{$payment->id} (status: {$payment->status})?")) {
                $this->info('Dibatalkan.');
                return 0;
            }
        }

        DB::beginTransaction();
        try {
            $deletedRealizations = 0;
            $deletedCashbook = 0;

            // Hapus realization auto-generated yang cocok dengan bukti (receipt_number)
            if (!empty($payment->receipt_number)) {
                $deletedRealizations = ActivityRealization::where('is_auto_generated', true)
                    ->where('proof', $payment->receipt_number)
                    ->delete();
            }

            // Hapus entri buku kas yang referensinya payment ini
            $deletedCashbook = CashBook::where('reference_type', 'payment')
                ->where('reference_id', $payment->id)
                ->delete();

            // Recalculate running balance for cash book
            $this->recalculateCashBookBalances();

            DB::commit();

            $this->info("Rollback selesai. Realization dihapus: {$deletedRealizations}, CashBook dihapus: {$deletedCashbook}.");
            return 0;
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Gagal rollback: ' . $e->getMessage());
            return 1;
        }
    }

    private function recalculateCashBookBalances(): void
    {
        $balance = 0;
        $entries = CashBook::orderBy('date')->orderBy('id')->get();
        foreach ($entries as $entry) {
            $balance = $balance + (float) $entry->credit - (float) $entry->debit;
            $entry->balance = $balance;
            $entry->save();
        }
    }
}


