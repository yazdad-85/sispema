<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDigitalPaymentsTable extends Migration
{
    public function up()
    {
        Schema::create('digital_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 12, 2);
            $table->enum('payment_method', ['midtrans', 'btn_bank', 'other']);
            $table->foreignId('gateway_id')->nullable()->constrained('payment_gateways')->onDelete('set null');
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'expired'])->default('pending');
            $table->string('reference_id')->nullable();
            $table->json('callback_data')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('digital_payments');
    }
}
