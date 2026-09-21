<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Comment;
use App\Entity\Issue;
use App\Entity\Project;
use App\Form\CommentType;
use App\Form\IssueType;
use App\Form\ProjectType;
use App\Repository\IssueRepository;
use App\Security\Voter\IssueVoter;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class IssueController extends AbstractController
{
    private const DASHBOARD_PAGE_SIZE = 10;

    #[Route('/issue', name: 'app_issue')]
    public function new(Request $request,EntityManagerInterface $em, SluggerInterface $slugger)
    {
        if($this->getUser()){
        
        $issue= new Issue();
        // set before validation runs: reporter has a NotNull constraint, so
        // an unpopulated Issue would always fail validation otherwise
        $issue->setReporter($this->getUser());
        // sensible starting point so the priority/severity scales open on a real choice
        $issue->setVisibility('public')->setPriority('normal')->setSeverity('minor')->setStatus('new');
        $form=$this->createForm(IssueType::class, $issue, ['user' => $this->getUser()]);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid())
        {

             /** @var UploadedFile $attachmentFile */
             $attachmentFile = $form->get('attachment')->getData();
             if ($attachmentFile)
            {
                 $originalFilename = pathinfo($attachmentFile->getClientOriginalName(), PATHINFO_FILENAME);
                 $safeFilename = $slugger->slug($originalFilename);
                 $newFilename = $safeFilename.'-'.uniqid().'.'.$attachmentFile->guessExtension();
                 try {
                    $attachmentFile->move(
                         $this->getParameter('kernel.project_dir').'/public/uploads',
                         $newFilename
                     );
                 } catch (FileException $e) {
                 }
                $issue->setAttachment($newFilename);

            }
            $issue->setSubmittedAt(new \DateTime());
                $issue->setUpdatedAt(new \DateTime());
                $em->persist ($issue);
                $em->flush();
                $this->addFlash('success', 'Issue created.');
                return $this->redirectToRoute('app_home');
        }
            // "New project" modal: submitted in the background to project_quick_create so
            // the page (and whatever has been typed into the issue form) is left alone
            $form3=$this->createForm(ProjectType::class, new Project(), [
                'action' => $this->generateUrl('project_quick_create'),
            ]);
        return $this->render('issue/new.html.twig',['form'=>$form->createView(),'form3'=>$form3->createView()]);
    }
    else{
        return $this->redirectToRoute('app_login');
    }
    }


    #[Route('/', name: 'app_home')]
    public function index(Request $request,EntityManagerInterface $entityManager)
    {
        if($this->getUser()){
         $user = $this->getUser();
         $isAdmin = $this->isGranted('ROLE_ADMIN');

         $projectRepository = $entityManager->getRepository(Project::class);
         $availableProjects = $isAdmin
             ? $projectRepository->findBy([], ['name' => 'ASC'])
             : $projectRepository->findAllForUser($user);

         $selectedProjectId = $request->query->get('project');
         $selectedProject = $selectedProjectId ? $entityManager->getRepository(Project::class)->find($selectedProjectId) : null;

         $onlyMine = $request->query->getBoolean('mine');

         $sort = $request->query->get('sort', IssueRepository::SORT_NEWEST);
         if (!in_array($sort, IssueRepository::SORTS, true)) {
             $sort = IssueRepository::SORT_NEWEST;
         }

         $availableCategories = $entityManager->getRepository(Category::class)->findBy([], ['name' => 'ASC']);
         $selectedCategoryId = $request->query->get('category');
         $selectedCategory = $selectedCategoryId ? $entityManager->getRepository(Category::class)->find($selectedCategoryId) : null;

         $selectedSeverity = $request->query->get('severity');
         if (!in_array($selectedSeverity, Issue::SEVERITIES, true)) {
             $selectedSeverity = null;
         }

         $issueRepository = $entityManager->getRepository(Issue::class);
         $issues = $issueRepository->findForDashboard(
             $isAdmin ? null : $user,
             $selectedProject,
             $onlyMine ? $user : null,
             $sort,
             $selectedCategory,
             $selectedSeverity
         );

         $allByStatus = array_fill_keys(Issue::STATUSES, []);
         $openCount = 0;
         $overdueCount = 0;
         $unassignedCount = 0;
         $now = new \DateTime();
         foreach ($issues as $issue) {
             $allByStatus[$issue->getStatus()][] = $issue;

             if ($issue->getStatus() === 'closed') {
                 continue;
             }
             ++$openCount;
             if ($issue->getDueDate() && $issue->getDueDate() < $now) {
                 ++$overdueCount;
             }
             if (!$issue->getAssigned()) {
                 ++$unassignedCount;
             }
         }

         // each status column is paginated independently, via its own page_<status> query param
         $issuesByStatus = [];
         $pagination = [];
         foreach (Issue::STATUSES as $status) {
             $all = $allByStatus[$status];
             $totalPages = max(1, (int) ceil(count($all) / self::DASHBOARD_PAGE_SIZE));
             $page = max(1, min($totalPages, (int) $request->query->get('page_'.$status, 1)));

             $issuesByStatus[$status] = array_slice($all, ($page - 1) * self::DASHBOARD_PAGE_SIZE, self::DASHBOARD_PAGE_SIZE);
             $pagination[$status] = ['current' => $page, 'total' => $totalPages, 'count' => count($all)];
         }

         return $this->render('issue/index.html.twig', [
             'issuesByStatus' => $issuesByStatus,
             'pagination' => $pagination,
             'availableProjects' => $availableProjects,
             'selectedProjectId' => $selectedProjectId,
             'onlyMine' => $onlyMine,
             'sort' => $sort,
             'availableCategories' => $availableCategories,
             'selectedCategoryId' => $selectedCategoryId,
             'severities' => Issue::SEVERITIES,
             'selectedSeverity' => $selectedSeverity,
             'openCount' => $openCount,
             'overdueCount' => $overdueCount,
             'unassignedCount' => $unassignedCount,
         ]);
        }
        else{
        return $this->redirectToRoute('app_login');
        }
    }

    #[Route('/issue/{id}', name: 'issue_show', methods: ['GET'])]
    public function show(int $id, EntityManagerInterface $em): Response
    {
        $issue = $em->getRepository(Issue::class)->find($id);
        if (!$issue) {
            throw new NotFoundHttpException('Issue not found.');
        }

        $this->denyAccessUnlessGranted(IssueVoter::VIEW, $issue);

        $commentForm = $this->createForm(CommentType::class, new Comment());

        return $this->render('issue/show.html.twig', [
            'issue' => $issue,
            'commentForm' => $commentForm->createView(),
        ]);
    }

    #[Route('/issue/{id}/edit', name: 'issue_edit', methods: ['GET', 'POST'])]
    public function edit(int $id, Request $request, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        $issue = $em->getRepository(Issue::class)->find($id);
        if (!$issue) {
            throw new NotFoundHttpException('Issue not found.');
        }

        $this->denyAccessUnlessGranted(IssueVoter::EDIT, $issue);

        $form = $this->createForm(IssueType::class, $issue, [
            'user' => $this->getUser(),
            'canManage' => $this->isGranted(IssueVoter::MANAGE, $issue),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $attachmentFile */
            $attachmentFile = $form->get('attachment')->getData();
            if ($attachmentFile) {
                $originalFilename = pathinfo($attachmentFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$attachmentFile->guessExtension();
                try {
                    $attachmentFile->move(
                        $this->getParameter('kernel.project_dir').'/public/uploads',
                        $newFilename
                    );
                } catch (FileException $e) {
                }
                $issue->setAttachment($newFilename);
            }

            $issue->setUpdatedAt(new \DateTime());
            $em->flush();

            return $this->redirectToRoute('issue_show', ['id' => $issue->getId()]);
        }

        // same "New project" modal as the new-issue page (see project_quick_create)
        $form3 = $this->createForm(ProjectType::class, new Project(), [
            'action' => $this->generateUrl('project_quick_create'),
        ]);

        return $this->render('issue/edit.html.twig', [
            'issue' => $issue,
            'form' => $form->createView(),
            'form3' => $form3->createView(),
        ]);
    }

    #[Route('/issue/{id}/comment', name: 'issue_comment', methods: ['POST'])]
    public function addComment(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $issue = $em->getRepository(Issue::class)->find($id);
        if (!$issue) {
            throw new NotFoundHttpException('Issue not found.');
        }

        $this->denyAccessUnlessGranted(IssueVoter::VIEW, $issue);

        $comment = new Comment();
        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $comment->setAuthor($this->getUser());
            $comment->setIssue($issue);
            $comment->setCreatedAt(new \DateTime());
            $em->persist($comment);
            $em->flush();

            return $this->redirectToRoute('issue_show', ['id' => $id]);
        }

        return $this->render('issue/show.html.twig', [
            'issue' => $issue,
            'commentForm' => $form->createView(),
        ]);
    }
}