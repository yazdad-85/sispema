<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddInstitutionAndLevelToActivityPlans extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('activity_plans', function (Blueprint $table) {
            // Add nullable institution_id and level for grouping plans per institution and class level
            if (!Schema::hasColumn('activity_plans', 'institution_id')) {
                $table->unsignedBigInteger('institution_id')->nullable()->after('academic_year_id');
                $table->index('institution_id');
            }
            if (!Schema::hasColumn('activity_plans', 'level')) {
                $table->string('level', 10)->nullable()->after('institution_id');
                $table->index('level');
            }
            // Optional FK if institutions table exists
            if (Schema::hasTable('institutions')) {
                $table->foreign('institution_id')->references('id')->on('institutions')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('activity_plans', function (Blueprint $table) {
            if (Schema::hasColumn('activity_plans', 'institution_id')) {
                $table->dropForeign(['institution_id']);
                $table->dropIndex(['institution_id']);
                $table->dropColumn('institution_id');
            }
            if (Schema::hasColumn('activity_plans', 'level')) {
                $table->dropIndex(['level']);
                $table->dropColumn('level');
            }
        });
    }
}
