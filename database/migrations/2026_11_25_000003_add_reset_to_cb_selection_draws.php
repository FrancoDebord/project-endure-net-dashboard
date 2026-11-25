<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Les tirages ne sont plus supprimés à la réinitialisation : ils sont marqués comme annulés (audit). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cb_selection_draws', function (Blueprint $table) {
            $table->timestamp('reset_at')->nullable()->after('created_by');
            $table->foreignId('reset_by')->nullable()->after('reset_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('cb_selection_draws', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reset_by');
            $table->dropColumn('reset_at');
        });
    }
};
