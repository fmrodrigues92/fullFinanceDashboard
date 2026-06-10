<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('razao_social');
            $table->string('nome_fantasia');
            $table->string('cnpj', 14);
            $table->string('regime_tributario');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['user_id', 'cnpj']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
