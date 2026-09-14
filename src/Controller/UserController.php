<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class UserController extends AbstractController
{
    #[Route(path: '/list', name: 'list')]
    public function list(EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $users = $entityManager->getRepository(User::class)->findAll();

        return $this->render('user/index.html.twig', ['users' => $users]);
    }

    #[Route(path: '/user/{id}', name: 'user_show')]
    public function show(int $id, EntityManagerInterface $entityManager): Response
    {
        $user = $entityManager->getRepository(User::class)->find($id);
        if (!$user) {
            throw new NotFoundHttpException('User not found.');
        }
        if ($user !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException('You can only view your own profile.');
        }

        return $this->render('user/show.html.twig', ['user' => $user]);
    }

    #[Route(path: '/user/delete/{id}', name: 'user_delete', methods: ['POST'])]
    public function delete(int $id, EntityManagerInterface $entityManager, CsrfTokenManagerInterface $csrfTokenManager, Request $request): RedirectResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if (!$csrfTokenManager->isTokenValid(new CsrfToken('delete-user-'.$id, $request->request->get('_token')))) {
            throw new AccessDeniedException('Invalid CSRF token.');
        }

        $user = $entityManager->getRepository(User::class)->find($id);
        if (!$user) {
            throw new NotFoundHttpException('User not found.');
        }
        $entityManager->remove($user);
        $entityManager->flush();

        return $this->redirectToRoute('app_home');
    }
}
