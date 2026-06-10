<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prolabore_records', function (Blueprint $table): void {
            $table->string('origem', 20)->default('manual')->after('observacao');
            $table->index(['company_id', 'competencia'], 'prolabore_records_company_competencia');
        });
    }

    public function down(): void
    {
        Schema::table('prolabore_records', function (Blueprint $table): void {
            $table->dropIndex('prolabore_records_company_competencia');
            $table->dropColumn('origem');
        });
    }
};
