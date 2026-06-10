<?php

declare(strict_types=1);

namespace Src\Invoicing\Domain\Enums;

enum InvoiceTipo: string
{
    case Nacional = 'nacional';
    case Internacional = 'internacional';
}
