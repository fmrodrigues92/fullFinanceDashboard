<?php

declare(strict_types=1);

namespace Src\Invoicing\Infrastructure\Persistence;

use Database\Factories\Invoicing\InvoiceModelFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

final class InvoiceModel extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'invoices';

    protected $guarded = [];

    protected static function newFactory(): InvoiceModelFactory
    {
        return InvoiceModelFactory::new();
    }
}
