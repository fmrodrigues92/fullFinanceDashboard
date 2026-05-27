<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_partners', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('nome');
            $table->string('cpf', 11);
            $table->decimal('participacao', 5, 2);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'cpf']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_partners');
    }
};
