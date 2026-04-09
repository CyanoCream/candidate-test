<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('clt_layups', function (Blueprint $table) {
            $table->string('species_grade')->nullable()->default('Mixed');
            $table->string('revision')->nullable()->default('1.0');
            $table->string('status')->nullable()->default('active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clt_layups', function (Blueprint $table) {
            $table->dropColumn(['species_grade', 'revision', 'status']);
        });
    }
};
