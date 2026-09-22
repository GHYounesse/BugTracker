<?php

namespace App\Service;

use App\Entity\Comment;
use App\Entity\Issue;
use App\Entity\Notification;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Creates Notification rows. Only persists — callers flush, so a notification
 * lands in the same transaction as whatever triggered it (a comment, an
 * assignment, ...).
 */
class NotificationService
{
    /** Comment previews are cut to this length so the notification list stays scannable. */
    private const EXCERPT_LENGTH = 140;

    public function __construct(private EntityManagerInterface $em)
    {
    }

    public function notify(User $recipient, string $type, Issue $issue, ?User $actor = null, ?Comment $comment = null, ?string $excerpt = null): void
    {
        $notification = (new Notification())
            ->setRecipient($recipient)
            ->setType($type)
            ->setIssue($issue)
            ->setComment($comment)
            ->setActor($actor)
            ->setActorUsername($actor?->getUsername())
            ->setExcerpt($excerpt !== null ? self::excerpt($excerpt) : null)
            ->setCreatedAt(new \DateTime());
        $this->em->persist($notification);
    }

    public static function excerpt(string $text): string
    {
        $text = trim(preg_replace('/\s+/', ' ', $text));

        return mb_strlen($text) > self::EXCERPT_LENGTH
            ? mb_substr($text, 0, self::EXCERPT_LENGTH - 1).'…'
            : $text;
    }
}
