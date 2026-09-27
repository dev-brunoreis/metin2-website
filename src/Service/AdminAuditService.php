<?php

declare(strict_types=1);

namespace Metin2Website\Service;

use Metin2Website\Admin\AdminAuditMeta;
use Metin2Website\Auth\AdminAuth;
use Metin2Website\Http\Request;
use Metin2Website\Repository\AdminAuditRepository;

class AdminAuditService
{
    public function __construct(
        private AdminAuditRepository $repository,
        private AdminAuth $adminAuth,
    ) {
    }

    /**
     * @param array<string, mixed>|null $meta
     */
    public function record(
        string $action,
        string $targetType,
        ?int $targetId = null,
        ?array $meta = null,
    ): void {
        $adminId = $this->adminAuth->id();

        if ($adminId === null) {
            return;
        }

        $login = $this->adminAuth->login() ?? '';

        try {
            $this->repository->insert(
                $adminId,
                $login,
                $action,
                $targetType,
                $targetId,
                AdminAuditMeta::sanitize($meta),
                $this->clientIp(),
            );
        } catch (\Throwable) {
            // Audit must not break admin actions.
        }
    }

    private function clientIp(): string
    {
        return Request::clientIp();
    }
}
