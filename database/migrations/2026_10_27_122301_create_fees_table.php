<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * A fee of a school year (tuition, registration, canteen…), for one level or, with a null
     * level, for every level. Its amount is the sum of its installments, in FCFA.
     */
    public function up(): void
    {
        Schema::create('fees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_year_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('level', 10)->nullable();
            $table->boolean('is_optional')->default(false);
            $table->timestamps();

            $table->index(['school_year_id', 'level']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fees');
    }
};
