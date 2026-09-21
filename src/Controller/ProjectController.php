<?php

namespace App\Controller;

use App\Entity\Project;
use App\Entity\ProjectMember;
use App\Entity\User;
use App\Form\ProjectType;
use App\Security\Voter\ProjectVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class ProjectController extends AbstractController
{
    #[Route('/projects', name: 'project_index', methods: ['GET'])]
    public function index(EntityManagerInterface $em): Response
    {
        return $this->renderIndex($em);
    }

    #[Route('/project/new', name: 'project_new', methods: ['POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $project = new Project();
        $form = $this->createForm(ProjectType::class, $project, [
            'action' => $this->generateUrl('project_new'),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->saveNewProject($project, $em);

            $this->addFlash('success', sprintf('Project "%s" created.', $project->getName()));

            return $this->redirectToRoute('project_index');
        }

        return $this->renderIndex($em, 'new', null, $form);
    }

    /**
     * Used by the "New project" modal on the new-issue page, which stays on the
     * page so a half-written issue isn't lost. Same rules as project_new.
     */
    #[Route('/project/quick', name: 'project_quick_create', methods: ['POST'])]
    public function quickCreate(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $project = new Project();
        $form = $this->createForm(ProjectType::class, $project);
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            $errors = [];
            foreach ($form->getErrors(true) as $error) {
                $errors[] = $error->getMessage();
            }

            return $this->json(['errors' => $errors ?: ['Enter a project name.']], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->saveNewProject($project, $em);

        return $this->json(['id' => $project->getId(), 'name' => $project->getName()], Response::HTTP_CREATED);
    }

    /** A new project always starts with its creator as the project admin. */
    private function saveNewProject(Project $project, EntityManagerInterface $em): void
    {
        $em->persist($project);

        $membership = (new ProjectMember())
            ->setProject($project)
            ->setUser($this->getUser())
            ->setRole(ProjectMember::ROLE_ADMIN);
        $em->persist($membership);

        $em->flush();
    }

    #[Route('/project/{id}/edit', name: 'project_edit', methods: ['POST'])]
    public function edit(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $project = $em->getRepository(Project::class)->find($id);
        if (!$project) {
            throw new NotFoundHttpException('Project not found.');
        }

        $this->denyAccessUnlessGranted(ProjectVoter::EDIT, $project);

        $form = $this->createForm(ProjectType::class, $project, [
            'action' => $this->generateUrl('project_edit', ['id' => $id]),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', sprintf('Project "%s" updated.', $project->getName()));

            return $this->redirectToRoute('project_index');
        }

        return $this->renderIndex($em, 'edit-'.$id, $id, $form);
    }

    #[Route('/project/{id}/delete', name: 'project_delete', methods: ['POST'])]
    public function delete(int $id, Request $request, EntityManagerInterface $em, CsrfTokenManagerInterface $csrfTokenManager): RedirectResponse
    {
        $project = $em->getRepository(Project::class)->find($id);
        if (!$project) {
            throw new NotFoundHttpException('Project not found.');
        }

        $this->denyAccessUnlessGranted(ProjectVoter::DELETE, $project);

        if (!$csrfTokenManager->isTokenValid(new CsrfToken('delete-project-'.$id, $request->request->get('_token')))) {
            throw new AccessDeniedException('Invalid CSRF token.');
        }

        if (!$project->getIssues()->isEmpty()) {
            $this->addFlash('error', sprintf(
                'Cannot delete "%s" - %d issue(s) still use this project.',
                $project->getName(),
                $project->getIssues()->count()
            ));

            return $this->redirectToRoute('project_index');
        }

        $em->remove($project);
        $em->flush();

        $this->addFlash('success', 'Project deleted.');

        return $this->redirectToRoute('project_index');
    }

    #[Route('/project/{id}/members/add', name: 'project_member_add', methods: ['POST'])]
    public function addMember(int $id, Request $request, EntityManagerInterface $em, CsrfTokenManagerInterface $csrfTokenManager): RedirectResponse
    {
        $project = $em->getRepository(Project::class)->find($id);
        if (!$project) {
            throw new NotFoundHttpException('Project not found.');
        }

        $this->denyAccessUnlessGranted(ProjectVoter::MANAGE_MEMBERS, $project);

        if (!$csrfTokenManager->isTokenValid(new CsrfToken('manage-members-'.$id, $request->request->get('_token')))) {
            throw new AccessDeniedException('Invalid CSRF token.');
        }

        $username = trim((string) $request->request->get('username'));
        $role = (string) $request->request->get('role');

        $user = $username !== '' ? $em->getRepository(User::class)->findOneBy(['username' => $username]) : null;
        if (!$user || !$user->isActive()) {
            $this->addFlash('error', sprintf('No active user found with username "%s".', $username));

            return $this->redirectToRoute('project_index');
        }

        if (!in_array($role, ProjectMember::ROLES, true)) {
            $this->addFlash('error', 'Invalid role.');

            return $this->redirectToRoute('project_index');
        }

        if ($em->getRepository(ProjectMember::class)->findOneBy(['project' => $project, 'user' => $user])) {
            $this->addFlash('error', sprintf('"%s" is already a member of this project.', $user->getUsername()));

            return $this->redirectToRoute('project_index');
        }

        $membership = (new ProjectMember())
            ->setProject($project)
            ->setUser($user)
            ->setRole($role);
        $em->persist($membership);
        $em->flush();

        $this->addFlash('success', sprintf('Added "%s" to "%s" as %s.', $user->getUsername(), $project->getName(), $role));

        return $this->redirectToRoute('project_index');
    }

    #[Route('/project/{id}/members/{memberId}/remove', name: 'project_member_remove', methods: ['POST'])]
    public function removeMember(int $id, int $memberId, Request $request, EntityManagerInterface $em, CsrfTokenManagerInterface $csrfTokenManager): RedirectResponse
    {
        $project = $em->getRepository(Project::class)->find($id);
        if (!$project) {
            throw new NotFoundHttpException('Project not found.');
        }

        $this->denyAccessUnlessGranted(ProjectVoter::MANAGE_MEMBERS, $project);

        if (!$csrfTokenManager->isTokenValid(new CsrfToken('manage-members-'.$id, $request->request->get('_token')))) {
            throw new AccessDeniedException('Invalid CSRF token.');
        }

        $member = $em->getRepository(ProjectMember::class)->find($memberId);
        if (!$member || $member->getProject() !== $project) {
            throw new NotFoundHttpException('Member not found.');
        }

        if ($member->getRole() === ProjectMember::ROLE_ADMIN
            && $em->getRepository(ProjectMember::class)->count(['project' => $project, 'role' => ProjectMember::ROLE_ADMIN]) <= 1) {
            $this->addFlash('error', 'Cannot remove the last admin of a project.');

            return $this->redirectToRoute('project_index');
        }

        $em->remove($member);
        $em->flush();

        $this->addFlash('success', 'Member removed.');

        return $this->redirectToRoute('project_index');
    }

    #[Route('/project/{id}/members/{memberId}/role', name: 'project_member_role', methods: ['POST'])]
    public function changeMemberRole(int $id, int $memberId, Request $request, EntityManagerInterface $em, CsrfTokenManagerInterface $csrfTokenManager): RedirectResponse
    {
        $project = $em->getRepository(Project::class)->find($id);
        if (!$project) {
            throw new NotFoundHttpException('Project not found.');
        }

        $this->denyAccessUnlessGranted(ProjectVoter::MANAGE_MEMBERS, $project);

        if (!$csrfTokenManager->isTokenValid(new CsrfToken('manage-members-'.$id, $request->request->get('_token')))) {
            throw new AccessDeniedException('Invalid CSRF token.');
        }

        $member = $em->getRepository(ProjectMember::class)->find($memberId);
        if (!$member || $member->getProject() !== $project) {
            throw new NotFoundHttpException('Member not found.');
        }

        $role = (string) $request->request->get('role');
        if (!in_array($role, ProjectMember::ROLES, true)) {
            $this->addFlash('error', 'Invalid role.');

            return $this->redirectToRoute('project_index');
        }

        if ($member->getRole() === ProjectMember::ROLE_ADMIN && $role !== ProjectMember::ROLE_ADMIN
            && $em->getRepository(ProjectMember::class)->count(['project' => $project, 'role' => ProjectMember::ROLE_ADMIN]) <= 1) {
            $this->addFlash('error', 'Cannot demote the last admin of a project.');

            return $this->redirectToRoute('project_index');
        }

        $member->setRole($role);
        $em->flush();

        $this->addFlash('success', 'Member role updated.');

        return $this->redirectToRoute('project_index');
    }

    /**
     * Renders the project list along with the "new" modal form and one "edit"
     * modal form per row. When a submission from new()/edit() fails validation,
     * that form (with its errors) is substituted in so the same modal can be
     * redisplayed open, instead of losing the user's input and errors.
     */
    private function renderIndex(EntityManagerInterface $em, ?string $openModal = null, ?int $failedProjectId = null, ?FormInterface $failedForm = null): Response
    {
        $projects = $this->isGranted('ROLE_ADMIN')
            ? $em->getRepository(Project::class)->findAll()
            : $em->getRepository(Project::class)->findAllForUser($this->getUser());

        $newForm = ($failedProjectId === null && $failedForm !== null)
            ? $failedForm
            : $this->createForm(ProjectType::class, new Project(), [
                'action' => $this->generateUrl('project_new'),
            ]);

        $editForms = [];
        foreach ($projects as $project) {
            $form = ($failedProjectId === $project->getId() && $failedForm !== null)
                ? $failedForm
                : $this->createForm(ProjectType::class, $project, [
                    'action' => $this->generateUrl('project_edit', ['id' => $project->getId()]),
                ]);
            $editForms[$project->getId()] = $form->createView();
        }

        return $this->render('project/index.html.twig', [
            'projects' => $projects,
            'newForm' => $newForm->createView(),
            'editForms' => $editForms,
            'openModal' => $openModal,
        ]);
    }
}
