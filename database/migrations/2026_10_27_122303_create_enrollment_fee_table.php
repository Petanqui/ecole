<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * The optional fees (canteen, transport…) a student has subscribed to for the year.
     */
    public function up(): void
    {
        Schema::create('enrollment_fee', function (Blueprint $table) {
            $table->foreignId('enrollment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fee_id')->constrained()->restrictOnDelete();

            $table->primary(['enrollment_id', 'fee_id']);
            $table->index('fee_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enrollment_fee');
    }
};
