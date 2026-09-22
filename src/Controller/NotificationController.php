<?php

namespace App\Controller;

use App\Entity\Notification;
use App\Entity\User;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class NotificationController extends AbstractController
{
    #[Route('/notifications', name: 'notification_index')]
    public function index(NotificationRepository $notifications): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->render('notification/index.html.twig', [
            'notifications' => $notifications->findForUser($user),
        ]);
    }

    /**
     * Marks one notification read and sends the person straight to what it's about.
     * A GET route with a side effect is unusual, but it's the only side effect here
     * (own-notification-only, idempotent), and it's what lets a single click on a
     * notification both open the issue and clear it.
     */
    #[Route('/notifications/{id}/open', name: 'notification_open', requirements: ['id' => '\d+'])]
    public function open(int $id, EntityManagerInterface $em): RedirectResponse
    {
        $notification = $em->getRepository(Notification::class)->find($id);
        if (!$notification) {
            throw new NotFoundHttpException('Notification not found.');
        }

        if ($notification->getRecipient()?->getId() !== $this->getUser()?->getId()) {
            throw new AccessDeniedException('This is not your notification.');
        }

        if (!$notification->isRead()) {
            $notification->setRead(true);
            $em->flush();
        }

        return $this->redirectToRoute('issue_show', ['id' => $notification->getIssue()->getId()]);
    }

    #[Route('/notifications/mark-all-read', name: 'notification_mark_all_read', methods: ['POST'])]
    public function markAllRead(Request $request, NotificationRepository $notifications, CsrfTokenManagerInterface $csrf): RedirectResponse
    {
        if (!$csrf->isTokenValid(new CsrfToken('mark-all-notifications-read', $request->request->get('_token')))) {
            throw new AccessDeniedException('Invalid CSRF token.');
        }

        /** @var User $user */
        $user = $this->getUser();
        $notifications->markAllRead($user);

        return $this->redirectToRoute('notification_index');
    }
}
