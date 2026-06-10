<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prolabore_configs', function (Blueprint $table): void {
            $table->string('tipo')->default('fixo')->after('valor');
        });
    }

    public function down(): void
    {
        Schema::table('prolabore_configs', function (Blueprint $table): void {
            $table->dropColumn('tipo');
        });
    }
};
