<?php

declare(strict_types=1);

namespace Metin2Website\Service;

use Metin2Website\Repository\NotificationRepository;

class NotificationService
{
    public const TYPE_PAYMENT_CREDITED = 'payment_credited';
    public const TYPE_PAYMENT_FAILED = 'payment_failed';
    public const TYPE_PAYMENT_EXPIRED = 'payment_expired';
    public const TYPE_PAYMENT_CANCELLED = 'payment_cancelled';
    public const TYPE_ACCOUNT_BANNED = 'account_banned';
    public const TYPE_ITEM_SENT = 'item_sent';
    public const TYPE_NEWS_COMMENT_APPROVED = 'news_comment_approved';
    public const TYPE_NEWS_COMMENT_REJECTED = 'news_comment_rejected';
    public const TYPE_TICKET_REPLIED = 'ticket_replied';

    public function __construct(private NotificationRepository $notifications)
    {
    }

    public function paymentCredited(int $accountId, int $cashAmount, int $paymentId): void
    {
        $this->push(
            $accountId,
            self::TYPE_PAYMENT_CREDITED,
            'payment:' . $paymentId,
            ['amount' => max(0, $cashAmount)],
        );
    }

    public function paymentFailed(int $accountId, int $cashAmount, int $paymentId): void
    {
        $this->push(
            $accountId,
            self::TYPE_PAYMENT_FAILED,
            'payment-failed:' . $paymentId,
            ['amount' => max(0, $cashAmount)],
        );
    }

    public function paymentExpired(int $accountId, int $cashAmount, int $paymentId): void
    {
        $this->push(
            $accountId,
            self::TYPE_PAYMENT_EXPIRED,
            'payment-expired:' . $paymentId,
            ['amount' => max(0, $cashAmount)],
        );
    }

    public function paymentCancelled(int $accountId, int $cashAmount, int $paymentId): void
    {
        $this->push(
            $accountId,
            self::TYPE_PAYMENT_CANCELLED,
            'payment-cancelled:' . $paymentId,
            ['amount' => max(0, $cashAmount)],
        );
    }

    public function accountBanned(int $accountId, string $reason, ?int $banId = null): void
    {
        $reason = trim($reason);
        $ref = $banId !== null && $banId > 0
            ? 'ban:' . $banId
            : 'block:' . $accountId . ':' . time();

        $this->push(
            $accountId,
            self::TYPE_ACCOUNT_BANNED,
            $ref,
            ['reason' => mb_substr($reason, 0, 200)],
        );
    }

    public function itemSent(int $accountId, int $vnum, int $count, string $itemName, string $ref): void
    {
        $item = trim($itemName);
        $label = $item !== '' ? $item : '#' . max(0, $vnum);

        $this->push(
            $accountId,
            self::TYPE_ITEM_SENT,
            $ref,
            [
                'item' => mb_substr($label, 0, 80),
                'count' => max(1, $count),
                'vnum' => max(0, $vnum),
            ],
        );
    }

    public function newsCommentApproved(int $accountId, int $commentId, string $newsTitle): void
    {
        $this->push(
            $accountId,
            self::TYPE_NEWS_COMMENT_APPROVED,
            'news-comment-approved:' . $commentId . ':' . time(),
            ['title' => mb_substr(trim($newsTitle), 0, 120)],
        );
    }

    public function newsCommentRejected(int $accountId, int $commentId, string $newsTitle): void
    {
        $this->push(
            $accountId,
            self::TYPE_NEWS_COMMENT_REJECTED,
            'news-comment-rejected:' . $commentId . ':' . time(),
            ['title' => mb_substr(trim($newsTitle), 0, 120)],
        );
    }

    public function ticketReplied(int $accountId, int $ticketId, string $subject): void
    {
        $this->push(
            $accountId,
            self::TYPE_TICKET_REPLIED,
            'ticket-replied:' . $ticketId . ':' . time(),
            ['subject' => mb_substr(trim($subject), 0, 120), 'id' => max(0, $ticketId)],
        );
    }

    /**
     * @param array<string, scalar|null> $payload
     */
    private function push(int $accountId, string $type, string $ref, array $payload): void
    {
        if ($accountId < 1) {
            return;
        }

        $this->notifications->create($accountId, $type, $ref, $payload);
    }
}
