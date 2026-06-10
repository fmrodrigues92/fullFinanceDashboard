<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prolabore_configs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('partner_id')->constrained('company_partners')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('valor', 10, 2);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'partner_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prolabore_configs');
    }
};
