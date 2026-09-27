<?php

declare(strict_types=1);

namespace Metin2Website\Payment;

final class CheckoutRedirect
{
    public function __construct(
        public readonly string $providerRef,
        public readonly string $approvalUrl,
    ) {
    }
}
