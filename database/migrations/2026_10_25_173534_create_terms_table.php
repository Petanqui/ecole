<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Every school year has three terms; existing years get theirs here.
     */
    public function up(): void
    {
        Schema::create('terms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_year_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('number');
            $table->boolean('is_closed')->default(false);
            $table->timestamps();

            $table->unique(['school_year_id', 'number']);
        });

        DB::table('school_years')->pluck('id')->each(function (int $schoolYearId): void {
            foreach ([1, 2, 3] as $number) {
                DB::table('terms')->insert([
                    'school_year_id' => $schoolYearId,
                    'number' => $number,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('terms');
    }
};
