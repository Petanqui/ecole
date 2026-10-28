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
        Schema::create('discount_fee', function (Blueprint $table) {
            $table->foreignId('discount_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fee_id')->constrained()->cascadeOnDelete();

            $table->primary(['discount_id', 'fee_id']);
        });

        Schema::create('discount_enrollment', function (Blueprint $table) {
            $table->foreignId('discount_id')->constrained()->cascadeOnDelete();
            $table->foreignId('enrollment_id')->constrained()->cascadeOnDelete();

            $table->primary(['discount_id', 'enrollment_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discount_enrollment');
        Schema::dropIfExists('discount_fee');
    }
};
