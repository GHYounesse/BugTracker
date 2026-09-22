<?php

namespace App\Twig;

use App\Entity\User;
use App\Repository\NotificationRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Backs the navbar's unread-notification badge, which every page that extends
 * base.html.twig needs - a Twig function avoids having to inject
 * NotificationRepository and pass a count into every single controller action.
 */
class NotificationExtension extends AbstractExtension
{
    public function __construct(private NotificationRepository $notifications)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('unread_notification_count', $this->unreadCount(...)),
        ];
    }

    public function unreadCount(?User $user): int
    {
        return $user ? $this->notifications->countUnread($user) : 0;
    }
}
