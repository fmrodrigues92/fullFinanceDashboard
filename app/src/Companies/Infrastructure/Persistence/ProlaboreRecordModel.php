<?php

declare(strict_types=1);

namespace Src\Companies\Infrastructure\Persistence;

use Database\Factories\Companies\ProlaboreRecordModelFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ProlaboreRecordModel extends Model
{
    use HasFactory;

    protected $table = 'prolabore_records';

    protected $guarded = [];

    protected static function newFactory(): ProlaboreRecordModelFactory
    {
        return ProlaboreRecordModelFactory::new();
    }
}
