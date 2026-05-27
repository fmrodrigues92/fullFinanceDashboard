<?php

declare(strict_types=1);

namespace Src\Invoicing\Domain\Exceptions;

use DomainException;

final class NationalInvoiceShouldNotHaveUsdFields extends DomainException {}
