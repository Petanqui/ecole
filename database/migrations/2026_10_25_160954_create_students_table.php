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
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')->constrained()->restrictOnDelete();
            $table->string('code', 20)->unique();
            $table->string('mena_number', 20)->nullable()->unique();
            $table->string('last_name');
            $table->string('first_name');
            $table->string('gender', 1);
            $table->date('birth_date');
            $table->string('birth_place')->nullable();
            $table->timestamps();

            $table->index(['last_name', 'first_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
