<?php

namespace App\Controller;

use App\Entity\Attachment;
use App\Entity\Category;
use App\Entity\Comment;
use App\Entity\Issue;
use App\Entity\IssueActivity;
use App\Entity\Notification;
use App\Entity\Project;
use App\Entity\User;
use App\Form\CommentType;
use App\Form\IssueType;
use App\Form\ProjectType;
use App\Repository\IssueActivityRepository;
use App\Repository\IssueRepository;
use App\Repository\ProjectMemberRepository;
use App\Repository\UserRepository;
use App\Security\Voter\IssueVoter;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

class IssueController extends AbstractController
{
    private const DASHBOARD_PAGE_SIZE = 10;
    private const UPLOAD_DIR = '/public/uploads';

    #[Route('/issue', name: 'app_issue')]
    public function new(Request $request, EntityManagerInterface $em, SluggerInterface $slugger, NotificationService $notifier)
    {
        if ($this->getUser()) {
            $issue = new Issue();
            // set before validation runs: reporter has a NotNull constraint, so
            // an unpopulated Issue would always fail validation otherwise
            $issue->setReporter($this->getUser());
            // sensible starting point so the priority/severity scales open on a real choice
            $issue->setVisibility('public')->setPriority('normal')->setSeverity('minor')->setStatus('new');
            $form = $this->createForm(IssueType::class, $issue, ['user' => $this->getUser()]);
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $issue->setSubmittedAt(new \DateTime());
                $issue->setUpdatedAt(new \DateTime());
                $em->persist($issue);
                $this->storeAttachments($form->get('attachments')->getData() ?? [], $issue, null, $slugger, $em);
                $this->notifyIfAssigned($issue, null, $notifier);
                $em->flush();
                $this->addFlash('success', 'Issue created.');

                return $this->redirectToRoute('app_home');
            }
            // "New project" modal: submitted in the background to project_quick_create so
            // the page (and whatever has been typed into the issue form) is left alone
            $form3 = $this->createForm(ProjectType::class, new Project(), [
                'action' => $this->generateUrl('project_quick_create'),
            ]);

            return $this->render('issue/new.html.twig', ['form' => $form->createView(), 'form3' => $form3->createView()]);
        } else {
            return $this->redirectToRoute('app_login');
        }
    }

    #[Route('/', name: 'app_home')]
    public function index(Request $request, EntityManagerInterface $entityManager)
    {
        if ($this->getUser()) {
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

            $search = trim((string) $request->query->get('q', ''));

            $issueRepository = $entityManager->getRepository(Issue::class);
            $issues = $issueRepository->findForDashboard(
                $isAdmin ? null : $user,
                $selectedProject,
                $onlyMine ? $user : null,
                $sort,
                $selectedCategory,
                $selectedSeverity,
                $search
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
                'search' => $search,
                'searchResultCount' => $search !== '' ? count($issues) : null,
                'openCount' => $openCount,
                'overdueCount' => $overdueCount,
                'unassignedCount' => $unassignedCount,
            ]);
        } else {
            return $this->redirectToRoute('app_login');
        }
    }

    #[Route('/issue/{id}', name: 'issue_show', methods: ['GET'])]
    public function show(int $id, EntityManagerInterface $em, IssueActivityRepository $activityRepository): Response
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
            'timeline' => $this->buildTimeline($issue, $activityRepository),
        ]);
    }

    /**
     * Comments and status/priority/assignee changes in one feed, oldest first.
     *
     * @return array<int, array{at: \DateTimeInterface, type: string, item: mixed}>
     */
    private function buildTimeline(Issue $issue, IssueActivityRepository $activityRepository): array
    {
        $timeline = [];
        foreach ($issue->getComments() as $comment) {
            $timeline[] = ['at' => $comment->getCreatedAt(), 'type' => 'comment', 'item' => $comment];
        }
        foreach ($activityRepository->findForIssue($issue) as $activity) {
            $timeline[] = ['at' => $activity->getCreatedAt(), 'type' => 'activity', 'item' => $activity];
        }
        usort($timeline, fn (array $a, array $b) => $a['at'] <=> $b['at']);

        return $timeline;
    }

    /**
     * Compares $before (a snapshot taken right after loading the issue, before the
     * form bound to it) against the issue's current values, and persists one
     * IssueActivity row per field that actually changed. Only status, priority and
     * assigned are tracked; call this after validating the form but before flush.
     *
     * @param array<string, ?string> $before keyed by IssueActivity::FIELD_*, as captured pre-submit
     */
    private function recordActivity(Issue $issue, array $before, EntityManagerInterface $em): void
    {
        /** @var User $actor */
        $actor = $this->getUser();

        $after = [
            IssueActivity::FIELD_STATUS => $issue->getStatus(),
            IssueActivity::FIELD_PRIORITY => $issue->getPriority(),
            IssueActivity::FIELD_ASSIGNED => $issue->getAssigned()?->getUsername(),
        ];

        foreach (IssueActivity::FIELDS as $field) {
            if ($before[$field] === $after[$field]) {
                continue;
            }

            $activity = (new IssueActivity())
                ->setIssue($issue)
                ->setActor($actor)
                ->setActorUsername($actor->getUsername())
                ->setField($field)
                ->setOldValue($before[$field])
                ->setNewValue($after[$field])
                ->setCreatedAt(new \DateTime());
            $em->persist($activity);
        }
    }

    /**
     * Notifies the assignee when they're newly assigned (set for the first time, or
     * changed to a different person) by someone other than themselves. $beforeUsername
     * is the pre-submit assignee, or null for a brand new issue.
     */
    private function notifyIfAssigned(Issue $issue, ?string $beforeUsername, NotificationService $notifier): void
    {
        $assigned = $issue->getAssigned();
        if (!$assigned || $assigned->getUsername() === $beforeUsername) {
            return;
        }

        /** @var User $actor */
        $actor = $this->getUser();
        if ($assigned->getId() === $actor->getId()) {
            return;
        }

        $notifier->notify($assigned, Notification::TYPE_ASSIGNED, $issue, $actor);
    }

    /**
     * Notifies people about a new comment: anyone @mentioned in it (who can actually
     * view the issue) gets a "mentioned" notification; the issue's reporter, its
     * assignee, and everyone who has commented before get a "commented" notification.
     * The comment's author is never notified about their own comment, and a mention
     * takes priority over the generic "commented" notification for the same person.
     */
    private function notifyComment(Issue $issue, Comment $comment, NotificationService $notifier, UserRepository $userRepository, ProjectMemberRepository $projectMembers): void
    {
        $author = $comment->getAuthor();
        $content = $comment->getContent();

        preg_match_all('/@([a-zA-Z0-9_.\-]{3,180})/', $content, $matches);

        /** @var array<int, User> $mentioned keyed by user id */
        $mentioned = [];
        if ($matches[1]) {
            foreach ($userRepository->findActiveByUsernames($matches[1]) as $candidate) {
                if ($candidate->getId() === $author->getId()) {
                    continue;
                }
                // only notify a mention if that person could actually open the issue
                $canView = $candidate->isAdmin() || $projectMembers->findOneForProjectAndUser($issue->getProject(), $candidate);
                if ($canView) {
                    $mentioned[$candidate->getId()] = $candidate;
                }
            }
        }
        foreach ($mentioned as $user) {
            $notifier->notify($user, Notification::TYPE_MENTIONED, $issue, $author, $comment, $content);
        }

        /** @var array<int, User> $participants keyed by user id: reporter, assignee, prior commenters */
        $participants = [];
        if ($issue->getReporter()) {
            $participants[$issue->getReporter()->getId()] = $issue->getReporter();
        }
        if ($issue->getAssigned()) {
            $participants[$issue->getAssigned()->getId()] = $issue->getAssigned();
        }
        foreach ($issue->getComments() as $existing) {
            if ($existing->getAuthor()) {
                $participants[$existing->getAuthor()->getId()] = $existing->getAuthor();
            }
        }
        unset($participants[$author->getId()]);
        foreach (array_keys($mentioned) as $id) {
            unset($participants[$id]);
        }

        foreach ($participants as $user) {
            $notifier->notify($user, Notification::TYPE_COMMENTED, $issue, $author, $comment, $content);
        }
    }

    /**
     * Moves each uploaded file into public/uploads and creates an Attachment row for
     * it, owned by the current user. A file that fails to move is silently skipped
     * (matches the previous single-attachment behaviour) rather than failing the
     * whole request over one bad upload.
     *
     * @param UploadedFile[] $files
     */
    private function storeAttachments(array $files, Issue $issue, ?Comment $comment, SluggerInterface $slugger, EntityManagerInterface $em): void
    {
        /** @var User $user */
        $user = $this->getUser();

        foreach ($files as $file) {
            if (!$file instanceof UploadedFile) {
                continue;
            }

            // captured before move(): afterwards the file no longer exists at its temp path
            $size = $file->getSize();
            $mimeType = $file->getMimeType();
            $originalFilename = $file->getClientOriginalName();

            $safeFilename = $slugger->slug(pathinfo($originalFilename, PATHINFO_FILENAME));
            $extension = $file->guessExtension() ?: $file->getClientOriginalExtension();
            $newFilename = $safeFilename.'-'.uniqid().'.'.$extension;

            try {
                $file->move($this->getParameter('kernel.project_dir').self::UPLOAD_DIR, $newFilename);
            } catch (FileException) {
                continue;
            }

            $attachment = (new Attachment())
                ->setIssue($issue)
                ->setComment($comment)
                ->setUploadedBy($user)
                ->setUploadedByUsername($user->getUsername())
                ->setFilename($newFilename)
                ->setOriginalFilename($originalFilename)
                ->setMimeType($mimeType)
                ->setSize($size)
                ->setCreatedAt(new \DateTime());
            $em->persist($attachment);
        }
    }

    #[Route('/issue/{id}/edit', name: 'issue_edit', methods: ['GET', 'POST'])]
    public function edit(int $id, Request $request, EntityManagerInterface $em, SluggerInterface $slugger, NotificationService $notifier): Response
    {
        $issue = $em->getRepository(Issue::class)->find($id);
        if (!$issue) {
            throw new NotFoundHttpException('Issue not found.');
        }

        $this->denyAccessUnlessGranted(IssueVoter::EDIT, $issue);

        // snapshot before the form binds: for a data_class form, handleRequest() writes
        // straight onto $issue via the setters, so this has to run before that call
        $before = [
            IssueActivity::FIELD_STATUS => $issue->getStatus(),
            IssueActivity::FIELD_PRIORITY => $issue->getPriority(),
            IssueActivity::FIELD_ASSIGNED => $issue->getAssigned()?->getUsername(),
        ];
        $beforeDueDate = $issue->getDueDate();

        $form = $this->createForm(IssueType::class, $issue, [
            'user' => $this->getUser(),
            'canManage' => $this->isGranted(IssueVoter::MANAGE, $issue),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $issue->setUpdatedAt(new \DateTime());
            $this->recordActivity($issue, $before, $em);
            $this->notifyIfAssigned($issue, $before[IssueActivity::FIELD_ASSIGNED], $notifier);
            // rescheduling clears the "already reminded" flag so a new due date can be flagged again
            if ($issue->getDueDate() != $beforeDueDate) {
                $issue->setDueSoonNotifiedAt(null);
            }
            $this->storeAttachments($form->get('attachments')->getData() ?? [], $issue, null, $slugger, $em);
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
    public function addComment(int $id, Request $request, EntityManagerInterface $em, SluggerInterface $slugger, IssueActivityRepository $activityRepository, NotificationService $notifier, UserRepository $userRepository, ProjectMemberRepository $projectMembers): Response
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
            $this->notifyComment($issue, $comment, $notifier, $userRepository, $projectMembers);
            $em->persist($comment);
            $this->storeAttachments($form->get('attachments')->getData() ?? [], $issue, $comment, $slugger, $em);
            $em->flush();

            return $this->redirectToRoute('issue_show', ['id' => $id]);
        }

        return $this->render('issue/show.html.twig', [
            'issue' => $issue,
            'commentForm' => $form->createView(),
            'timeline' => $this->buildTimeline($issue, $activityRepository),
        ]);
    }

    #[Route('/attachment/{id}/delete', name: 'attachment_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function deleteAttachment(int $id, Request $request, EntityManagerInterface $em, CsrfTokenManagerInterface $csrf): RedirectResponse
    {
        $attachment = $em->getRepository(Attachment::class)->find($id);
        if (!$attachment) {
            throw new NotFoundHttpException('Attachment not found.');
        }
        $issue = $attachment->getIssue();

        if (!$csrf->isTokenValid(new CsrfToken('delete-attachment-'.$id, $request->request->get('_token')))) {
            throw new AccessDeniedException('Invalid CSRF token.');
        }

        // whoever can manage the issue, or whoever uploaded this particular file, can remove it
        if (!$this->isGranted(IssueVoter::EDIT, $issue) && $attachment->getUploadedBy() !== $this->getUser()) {
            throw new AccessDeniedException('You cannot delete this attachment.');
        }

        $filename = $attachment->getFilename();
        $em->remove($attachment);
        $em->flush();

        $path = $this->getParameter('kernel.project_dir').self::UPLOAD_DIR.'/'.basename($filename);
        if (is_file($path)) {
            @unlink($path);
        }

        $this->addFlash('success', 'Attachment removed.');

        return $this->redirectToRoute('issue_show', ['id' => $issue->getId()]);
    }
}
