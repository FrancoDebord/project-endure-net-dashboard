<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Verrouillage définitif d'un tirage Cohorte B (suivi × bras) : plus de réinitialisation ni de libération. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cb_selection_plans', function (Blueprint $table) {
            $table->timestamp('locked_at')->nullable()->after('created_by');
            $table->foreignId('locked_by')->nullable()->after('locked_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('cb_selection_plans', function (Blueprint $table) {
            $table->dropConstrainedForeignId('locked_by');
            $table->dropColumn('locked_at');
        });
    }
};
