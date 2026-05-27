<?php

declare(strict_types=1);

namespace Src\Companies\Infrastructure\Persistence;

use Database\Factories\Companies\ProlaboreConfigModelFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ProlaboreConfigModel extends Model
{
    use HasFactory;

    protected $table = 'prolabore_configs';

    protected $guarded = [];

    protected static function newFactory(): ProlaboreConfigModelFactory
    {
        return ProlaboreConfigModelFactory::new();
    }
}
