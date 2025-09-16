<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Payment;
use App\Models\CashBook;
use App\Models\ActivityRealization;

class InspectPayment extends Command
{
    protected $signature = 'spp:inspect-payment {payment_id}';

    protected $description = 'Show payment status and related cash book and realization info';

    public function handle()
    {
        $id = (int) $this->argument('payment_id');
        $payment = Payment::with(['student', 'billingRecord'])->find($id);
        if (!$payment) {
            $this->error("Payment {$id} not found");
            return 1;
        }

        $this->info('Payment info:');
        $this->line('ID: ' . $payment->id);
        $this->line('Student: ' . ($payment->student->name ?? '-'));
        $this->line('Amount: ' . number_format($payment->total_amount, 0, ',', '.'));
        $this->line('Method: ' . $payment->payment_method);
        $this->line('Status: ' . $payment->status);
        $this->line('Receipt: ' . $payment->receipt_number);

        $cash = CashBook::where('reference_type', 'payment')->where('reference_id', $payment->id)->get();
        $this->line('CashBook entry exists: ' . ($cash->isNotEmpty() ? 'yes' : 'no'));
        foreach ($cash as $e) {
            $this->line('  - CashBook #' . $e->id . ' | ' . $e->date->format('Y-m-d') . ' | credit: ' . number_format($e->credit, 0, ',', '.') . ' | desc: ' . $e->description);
        }

        $real = ActivityRealization::where('proof', $payment->receipt_number)->get();
        $this->line('Realization exists by proof: ' . ($real->isNotEmpty() ? 'yes' : 'no'));
        foreach ($real as $r) {
            $this->line('  - Realization #' . $r->id . ' | ' . $r->date->format('Y-m-d') . ' | amount: ' . number_format($r->total_amount, 0, ',', '.'));
        }

        return 0;
    }
}
