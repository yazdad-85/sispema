<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class UpdateUsersRoleEnum extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // First, add the phone field
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 20)->nullable()->after('email');
        });

        // Update existing role values to match new enum
        DB::statement("UPDATE users SET role = 'staff' WHERE role = 'kasir'");
        DB::statement("UPDATE users SET role = 'admin_pusat' WHERE role = 'admin_pusat'");
        
        // Change the enum to include all required roles
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('super_admin', 'staff', 'admin_pusat', 'kasir') NOT NULL DEFAULT 'staff'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Remove phone field
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('phone');
        });

        // Revert role enum
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin_pusat', 'kasir') NOT NULL DEFAULT 'kasir'");
        
        // Revert role values
        DB::statement("UPDATE users SET role = 'kasir' WHERE role = 'staff'");
    }
}
