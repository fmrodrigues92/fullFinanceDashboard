<?php

declare(strict_types=1);

namespace Src\Companies\Infrastructure\Persistence;

use Database\Factories\Companies\CompanyPartnerModelFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CompanyPartnerModel extends Model
{
    use HasFactory;

    protected $table = 'company_partners';

    protected $guarded = [];

    protected static function newFactory(): CompanyPartnerModelFactory
    {
        return CompanyPartnerModelFactory::new();
    }
}
