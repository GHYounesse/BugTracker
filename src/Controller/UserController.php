<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\AccountPasswordFormType;
use App\Form\ProfileFormType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class UserController extends AbstractController
{
    private const AVATAR_DIR = '/public/uploads/avatars';

    #[Route(path: '/list', name: 'list')]
    public function list(Request $request, UserRepository $users): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $term = trim((string) $request->query->get('q', ''));

        return $this->render('user/index.html.twig', [
            'users' => $users->search($term),
            'term' => $term,
        ]);
    }

    #[Route(path: '/user/{id}', name: 'user_show', requirements: ['id' => '\d+'])]
    public function show(int $id, EntityManagerInterface $entityManager): Response
    {
        $user = $this->findUser($id, $entityManager);
        $this->denyUnlessSelfOrAdmin($user);

        return $this->render('user/show.html.twig', ['user' => $user]);
    }

    #[Route(path: '/user/{id}/edit', name: 'user_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(int $id, Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->findUser($id, $entityManager);
        $this->denyUnlessSelfOrAdmin($user);

        $form = $this->createForm(ProfileFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $previousImage = $user->getImage();

            /** @var UploadedFile|null $avatar */
            $avatar = $form->get('avatar')->getData();
            if ($avatar) {
                $filename = bin2hex(random_bytes(8)).'.'.$avatar->guessExtension();
                $avatar->move($this->getParameter('kernel.project_dir').self::AVATAR_DIR, $filename);
                $user->setImage($filename);
            } elseif ($form->get('removeAvatar')->getData()) {
                $user->setImage(null);
            }

            $entityManager->flush();

            if ($previousImage && $previousImage !== $user->getImage()) {
                $this->deleteAvatarFile($previousImage);
            }

            $this->addFlash('success', 'Profile saved.');

            return $this->redirectToRoute('user_show', ['id' => $user->getId()]);
        }

        return $this->render('user/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route(path: '/user/password', name: 'user_password', methods: ['GET', 'POST'])]
    public function password(
        Request $request,
        UserPasswordHasherInterface $hasher,
        EntityManagerInterface $entityManager,
        TokenStorageInterface $tokenStorage,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        $form = $this->createForm(AccountPasswordFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setPassword($hasher->hashPassword($user, $form->get('plainPassword')->getData()));
            $entityManager->flush();

            // the stored password changed, so this session (and any remember-me
            // cookie) is no longer valid: end it and ask for a fresh sign-in
            $tokenStorage->setToken(null);
            $request->getSession()->invalidate();
            $request->getSession()->getFlashBag()->add('reset_password_success', 'Password changed. Sign in with your new password.');

            return $this->redirectToRoute('app_login');
        }

        return $this->render('user/password.html.twig', ['form' => $form]);
    }

    #[Route(path: '/user/{id}/role', name: 'user_role', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function role(int $id, Request $request, EntityManagerInterface $entityManager, CsrfTokenManagerInterface $csrf): RedirectResponse
    {
        $user = $this->guardedAdminAction($id, $request, $entityManager, $csrf);
        if ($user instanceof RedirectResponse) {
            return $user;
        }

        $makeAdmin = $request->request->get('role') === 'admin';
        $user->setRoles($makeAdmin ? ['ROLE_ADMIN'] : []);
        $entityManager->flush();

        $this->addFlash('success', sprintf('%s is now %s.', $user->getUsername(), $makeAdmin ? 'an admin' : 'a regular user'));

        return $this->redirectToRoute('list');
    }

    #[Route(path: '/user/{id}/active', name: 'user_active', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function active(int $id, Request $request, EntityManagerInterface $entityManager, CsrfTokenManagerInterface $csrf): RedirectResponse
    {
        $user = $this->guardedAdminAction($id, $request, $entityManager, $csrf);
        if ($user instanceof RedirectResponse) {
            return $user;
        }

        $activate = $request->request->get('active') === '1';
        $user->setActive($activate);
        $entityManager->flush();

        $this->addFlash('success', $activate
            ? sprintf('%s can sign in again.', $user->getUsername())
            : sprintf('%s is deactivated and can no longer sign in. Their issues and comments are kept.', $user->getUsername()));

        return $this->redirectToRoute('list');
    }

    #[Route(path: '/user/delete/{id}', name: 'user_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(int $id, EntityManagerInterface $entityManager, CsrfTokenManagerInterface $csrfTokenManager, Request $request): RedirectResponse
    {
        $user = $this->guardedAdminAction($id, $request, $entityManager, $csrfTokenManager, 'delete-user-');
        if ($user instanceof RedirectResponse) {
            return $user;
        }

        if ($user->hasActivity()) {
            $this->addFlash('error', sprintf('%s has issues, comments or projects, so the account cannot be deleted. Deactivate it instead.', $user->getUsername()));

            return $this->redirectToRoute('list');
        }

        $image = $user->getImage();
        $username = $user->getUsername();
        $entityManager->remove($user);
        $entityManager->flush();

        if ($image) {
            $this->deleteAvatarFile($image);
        }

        $this->addFlash('success', sprintf('Deleted %s.', $username));

        return $this->redirectToRoute('list');
    }

    /**
     * Shared checks for admin actions on another account: admin only, valid CSRF
     * token, target exists, and never the signed-in admin themselves (so the last
     * admin can't demote, deactivate or delete their own way out of the app).
     */
    private function guardedAdminAction(int $id, Request $request, EntityManagerInterface $entityManager, CsrfTokenManagerInterface $csrf, string $tokenPrefix = 'manage-user-'): User|RedirectResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if (!$csrf->isTokenValid(new CsrfToken($tokenPrefix.$id, $request->request->get('_token')))) {
            throw new AccessDeniedException('Invalid CSRF token.');
        }

        $user = $this->findUser($id, $entityManager);

        if ($user->getId() === $this->getUser()?->getId()) {
            $this->addFlash('error', 'You cannot change your own role, status or account here.');

            return $this->redirectToRoute('list');
        }

        return $user;
    }

    private function findUser(int $id, EntityManagerInterface $entityManager): User
    {
        $user = $entityManager->getRepository(User::class)->find($id);
        if (!$user) {
            throw new NotFoundHttpException('User not found.');
        }

        return $user;
    }

    private function denyUnlessSelfOrAdmin(User $user): void
    {
        if ($user !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException('You can only view your own profile.');
        }
    }

    private function deleteAvatarFile(string $filename): void
    {
        $path = $this->getParameter('kernel.project_dir').self::AVATAR_DIR.'/'.basename($filename);
        if (is_file($path)) {
            @unlink($path);
        }
    }
}
