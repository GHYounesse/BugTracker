<?php

namespace App\Controller;

use App\Entity\Project;
use App\Form\ProjectType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
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
            $em->persist($project);
            $em->flush();

            $this->addFlash('success', sprintf('Project "%s" created.', $project->getName()));

            return $this->redirectToRoute('project_index');
        }

        return $this->renderIndex($em, 'new', null, $form);
    }

    #[Route('/project/{id}/edit', name: 'project_edit', methods: ['POST'])]
    public function edit(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $project = $em->getRepository(Project::class)->find($id);
        if (!$project) {
            throw new NotFoundHttpException('Project not found.');
        }

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

    /**
     * Renders the project list along with the "new" modal form and one "edit"
     * modal form per row. When a submission from new()/edit() fails validation,
     * that form (with its errors) is substituted in so the same modal can be
     * redisplayed open, instead of losing the user's input and errors.
     */
    private function renderIndex(EntityManagerInterface $em, ?string $openModal = null, ?int $failedProjectId = null, ?FormInterface $failedForm = null): Response
    {
        $projects = $em->getRepository(Project::class)->findAll();

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
