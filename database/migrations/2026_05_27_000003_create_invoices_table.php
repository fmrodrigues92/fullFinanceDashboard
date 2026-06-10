<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->nullable()->nullOnDelete();
            $table->foreignId('simulation_batch_id')->nullable()->constrained('simulation_batches')->cascadeOnDelete();
            $table->boolean('is_simulation')->default(false);
            $table->string('tipo');
            $table->tinyInteger('anexo_cnae');
            $table->date('data_emissao');
            $table->decimal('valor_brl', 10, 2);
            $table->decimal('valor_usd', 10, 2)->nullable();
            $table->decimal('cotacao', 10, 4)->nullable();
            $table->text('observacao')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'data_emissao']);
        });

        DB::statement(
            'CREATE UNIQUE INDEX invoices_simulation_unique ON invoices (company_id, tipo, data_emissao) WHERE is_simulation = true',
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
