<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ae_selection_runs', function (Blueprint $table) {
            $table->id();

            // null = les deux bras ; 'PD' = PERMANET DUAL ; 'G2' = INTERCEPTOR G2
            $table->string('arm', 10)->nullable()->comment('PD, G2, ou null pour les deux bras');

            $table->unsignedInteger('target_count')->default(331)->comment('Nombre de ménages cibles par bras');
            $table->unsignedInteger('selected_count')->default(0)->comment('Nombre réellement sélectionné');

            $table->text('notes')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ae_selection_runs');
    }
};
