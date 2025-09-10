<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsToUsersTable extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin_pusat', 'kasir'])->default('kasir')->after('email');
            $table->foreignId('institution_id')->nullable()->constrained()->onDelete('set null')->after('role');
            $table->boolean('is_active')->default(true)->after('institution_id');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['institution_id']);
            $table->dropColumn(['role', 'institution_id', 'is_active']);
        });
    }
}
