<?php

declare(strict_types=1);

namespace Metin2Website\Tests\Unit\Service;

use Metin2Website\Payment\GatewayRegistry;
use Metin2Website\Payment\PaymentGateway;
use Metin2Website\Repository\PaymentRepository;
use Metin2Website\Service\NotificationService;
use Metin2Website\Service\PaymentExpiryService;
use PHPUnit\Framework\TestCase;

final class PaymentExpiryServiceTest extends TestCase
{
    public function testExpireDueMarksPendingPaymentsAndNotifies(): void
    {
        $payments = $this->createMock(PaymentRepository::class);
        $notifications = $this->createMock(NotificationService::class);

        $gateway = $this->createMock(PaymentGateway::class);
        $gateway->method('id')->willReturn('paypal');
        $gateway->method('configured')->willReturn(true);
        $gateway->method('enabled')->willReturn(true);
        $gateway->method('pendingMinutes')->willReturn(30);

        $registry = new GatewayRegistry();
        $registry->register($gateway);

        $payments->method('listExpiredPending')->with(30)->willReturn([
            ['id' => 4, 'account_id' => 8, 'cash_amount' => 25],
            ['id' => 5, 'account_id' => 9, 'cash_amount' => 40],
        ]);
        $payments->method('markExpired')->willReturnCallback(static fn (int $id): bool => $id === 4);
        $notifications->expects(self::once())->method('paymentExpired')->with(8, 25, 4);

        $service = new PaymentExpiryService($payments, $registry, $notifications);

        self::assertSame(1, $service->expireDue());
    }
}
