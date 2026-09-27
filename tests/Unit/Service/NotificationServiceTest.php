<?php

declare(strict_types=1);

namespace Metin2Website\Tests\Unit\Service;

use Metin2Website\Repository\NotificationRepository;
use Metin2Website\Service\NotificationService;
use PHPUnit\Framework\TestCase;

final class NotificationServiceTest extends TestCase
{
    public function testNewsCommentApprovedPushesTypedRef(): void
    {
        $repo = $this->createMock(NotificationRepository::class);
        $repo->expects(self::once())->method('create')->with(
            9,
            NotificationService::TYPE_NEWS_COMMENT_APPROVED,
            self::callback(static fn (string $ref): bool => str_starts_with($ref, 'news-comment-approved:42:')),
            ['title' => 'Welcome'],
        )->willReturn(true);

        (new NotificationService($repo))->newsCommentApproved(9, 42, 'Welcome');
    }

    public function testNewsCommentRejectedPushesTypedRef(): void
    {
        $repo = $this->createMock(NotificationRepository::class);
        $repo->expects(self::once())->method('create')->with(
            9,
            NotificationService::TYPE_NEWS_COMMENT_REJECTED,
            self::callback(static fn (string $ref): bool => str_starts_with($ref, 'news-comment-rejected:42:')),
            ['title' => 'Welcome'],
        )->willReturn(true);

        (new NotificationService($repo))->newsCommentRejected(9, 42, 'Welcome');
    }

    public function testTicketRepliedIncludesSubjectAndTicketId(): void
    {
        $repo = $this->createMock(NotificationRepository::class);
        $repo->expects(self::once())->method('create')->with(
            3,
            NotificationService::TYPE_TICKET_REPLIED,
            self::callback(static fn (string $ref): bool => str_starts_with($ref, 'ticket-replied:7:')),
            ['subject' => 'Help please', 'id' => 7],
        )->willReturn(true);

        (new NotificationService($repo))->ticketReplied(3, 7, 'Help please');
    }

    public function testPushSkipsInvalidAccountId(): void
    {
        $repo = $this->createMock(NotificationRepository::class);
        $repo->expects(self::never())->method('create');

        (new NotificationService($repo))->newsCommentApproved(0, 1, 'x');
    }
}
