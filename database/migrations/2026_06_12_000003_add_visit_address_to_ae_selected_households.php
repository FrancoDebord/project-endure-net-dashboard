<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ae_selected_households', function (Blueprint $table) {
            $table->string('moment_visite', 200)->nullable()
                  ->comment('Meilleur moment pour visiter (b_hh_moment_visite)')
                  ->after('contact_phone');

            $table->string('adresse', 300)->nullable()
                  ->comment('Adresse du ménage (b_hh_address)')
                  ->after('moment_visite');
        });
    }

    public function down(): void
    {
        Schema::table('ae_selected_households', function (Blueprint $table) {
            $table->dropColumn(['moment_visite', 'adresse']);
        });
    }
};
