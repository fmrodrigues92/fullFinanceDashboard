<?php

declare(strict_types=1);

namespace Src\Invoicing\Infrastructure\Persistence;

use Database\Factories\Invoicing\SimulationBatchModelFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class SimulationBatchModel extends Model
{
    use HasFactory;

    protected $table = 'simulation_batches';

    protected $guarded = [];

    protected static function newFactory(): SimulationBatchModelFactory
    {
        return SimulationBatchModelFactory::new();
    }
}
