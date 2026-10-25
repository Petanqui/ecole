<?php

use App\Enums\Core\GradeLevel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Classes are now entered as a level plus a section ("6e" + "1"); the name ("6e1")
     * is derived from both. Existing names are kept as the section and the name is rebuilt.
     */
    public function up(): void
    {
        Schema::table('school_classes', function (Blueprint $table) {
            $table->string('section', 20)->nullable()->after('level');
        });

        DB::table('school_classes')->orderBy('id')->each(function (object $class): void {
            DB::table('school_classes')->where('id', $class->id)->update([
                'section' => $class->name,
                'name' => GradeLevel::from($class->level)->className($class->name),
            ]);
        });

        Schema::table('school_classes', function (Blueprint $table) {
            $table->string('section', 20)->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('school_classes')->update(['name' => DB::raw('section')]);

        Schema::table('school_classes', function (Blueprint $table) {
            $table->dropColumn('section');
        });
    }
};
