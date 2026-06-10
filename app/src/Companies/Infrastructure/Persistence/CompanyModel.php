<?php

declare(strict_types=1);

namespace Src\Companies\Infrastructure\Persistence;

use Database\Factories\Companies\CompanyModelFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CompanyModel extends Model
{
    use HasFactory;

    protected $table = 'companies';

    protected $guarded = [];

    protected static function newFactory(): CompanyModelFactory
    {
        return CompanyModelFactory::new();
    }
}
