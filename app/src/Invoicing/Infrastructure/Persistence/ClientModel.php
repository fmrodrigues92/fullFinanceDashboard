<?php

declare(strict_types=1);

namespace Src\Invoicing\Infrastructure\Persistence;

use Database\Factories\Invoicing\ClientModelFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

final class ClientModel extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'clients';

    protected $guarded = [];

    protected static function newFactory(): ClientModelFactory
    {
        return ClientModelFactory::new();
    }
}
