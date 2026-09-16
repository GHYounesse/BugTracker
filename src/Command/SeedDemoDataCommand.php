<?php

namespace App\Command;

use App\Entity\Category;
use App\Entity\Comment;
use App\Entity\Issue;
use App\Entity\Project;
use App\Entity\ProjectMember;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Seeds a full demo dataset - users covering every RBAC role, categories,
 * two projects (one shared, one admin-only) and issues/comments on them -
 * so the access-control rules can be exercised locally without hand-editing
 * the database. Safe to re-run: anything that already exists (matched by
 * username/name/summary) is left as-is rather than duplicated.
 */
#[AsCommand(name: 'app:seed-demo-data', description: 'Seed demo users, projects, categories, issues and comments covering every RBAC role')]
class SeedDemoDataCommand extends Command
{
    private const PASSWORD = 'password123';

    /** username => [global roles, ['project name' => role, ...]] */
    private const SEED_USERS = [
        'seed_admin' => [['ROLE_ADMIN'], []],
        'seed_project_admin' => [[], ['RBAC Demo Project' => ProjectMember::ROLE_ADMIN, 'Marketing Website' => ProjectMember::ROLE_ADMIN]],
        'seed_project_manager' => [[], ['RBAC Demo Project' => ProjectMember::ROLE_MANAGER]],
        'seed_project_member' => [[], ['RBAC Demo Project' => ProjectMember::ROLE_MEMBER]],
        'seed_outsider' => [[], []],
    ];

    private const CATEGORIES = [
        'Bug' => '#ef4444',
        'Feature Request' => '#6366f1',
        'Documentation' => '#22c55e',
    ];

    private const PROJECTS = ['RBAC Demo Project', 'Marketing Website'];

    /** [project, category, reporter, assigned, status, priority, severity, summary, description] */
    private const ISSUES = [
        [
            'RBAC Demo Project', 'Bug', 'seed_project_member', 'seed_project_manager',
            'new', 'high', 'major',
            'Login button unresponsive on mobile',
            "Tapping the login button on small screens does nothing the first time; a second tap works.\nSeen on iOS Safari and Android Chrome.",
            'On iOS Safari and Android Chrome, tap "Sign in" on the login page.',
            "Comment:seed_project_manager:Reproduced on iOS Safari, investigating.",
        ],
        [
            'RBAC Demo Project', 'Feature Request', 'seed_project_manager', 'seed_project_member',
            'accepted', 'normal', 'minor',
            'Add dark mode toggle to settings',
            "Several users asked for a manual dark mode toggle instead of following the OS preference only.",
            null,
            "Comment:seed_project_admin:Approved, please proceed with implementation.",
        ],
        [
            'RBAC Demo Project', 'Documentation', 'seed_project_admin', null,
            'processed', 'low', 'trivial',
            'Update onboarding docs',
            "The onboarding guide still references the old project creation flow.",
            null,
            null,
        ],
        [
            'Marketing Website', 'Bug', 'seed_project_admin', 'seed_project_admin',
            'new', 'urgent', 'critical',
            'Homepage hero image broken on Safari',
            "The homepage hero image fails to load on Safari 17, leaving a broken-image icon above the fold.",
            'Open the homepage in Safari 17 on macOS.',
            null,
        ],
    ];

    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $projects = $this->seedProjects($io);
        $categories = $this->seedCategories($io);
        $users = $this->seedUsers($io, $projects);
        $this->seedIssues($io, $projects, $categories, $users);

        $this->em->flush();

        $io->success('Demo data is ready.');

        return Command::SUCCESS;
    }

    /** @return array<string, Project> */
    private function seedProjects(SymfonyStyle $io): array
    {
        $projects = [];
        foreach (self::PROJECTS as $name) {
            $project = $this->em->getRepository(Project::class)->findOneBy(['name' => $name]);
            if (!$project) {
                $project = (new Project())->setName($name);
                $this->em->persist($project);
                $io->writeln(sprintf('Created project "%s".', $name));
            }
            $projects[$name] = $project;
        }

        return $projects;
    }

    /** @return array<string, Category> */
    private function seedCategories(SymfonyStyle $io): array
    {
        $categories = [];
        foreach (self::CATEGORIES as $name => $color) {
            $category = $this->em->getRepository(Category::class)->findOneBy(['name' => $name]);
            if (!$category) {
                $category = (new Category())->setName($name)->setColor($color);
                $this->em->persist($category);
                $io->writeln(sprintf('Created category "%s".', $name));
            }
            $categories[$name] = $category;
        }

        return $categories;
    }

    /**
     * @param array<string, Project> $projects
     *
     * @return array<string, User>
     */
    private function seedUsers(SymfonyStyle $io, array $projects): array
    {
        $users = [];
        $rows = [];
        foreach (self::SEED_USERS as $username => [$globalRoles, $projectRoles]) {
            $user = $this->em->getRepository(User::class)->findOneBy(['username' => $username]);
            $created = false;
            if (!$user) {
                $user = (new User())->setUsername($username)->setRoles($globalRoles);
                $user->setPassword($this->passwordHasher->hashPassword($user, self::PASSWORD));
                $this->em->persist($user);
                $created = true;
            }
            $users[$username] = $user;

            foreach ($projectRoles as $projectName => $role) {
                $project = $projects[$projectName];
                $membership = $this->em->getRepository(ProjectMember::class)->findOneBy(['project' => $project, 'user' => $user]);
                if (!$membership) {
                    $membership = (new ProjectMember())->setProject($project)->setUser($user)->setRole($role);
                    $this->em->persist($membership);
                } else {
                    $membership->setRole($role);
                }
            }

            $rows[] = [
                $username,
                $created ? self::PASSWORD : '(already existed - unchanged)',
                $globalRoles ? implode(', ', $globalRoles) : '-',
                $projectRoles ? implode(', ', array_map(static fn ($p, $r) => "$p: $r", array_keys($projectRoles), $projectRoles)) : '- (no membership)',
            ];
        }

        $io->table(['Username', 'Password', 'Global role', 'Project roles'], $rows);

        return $users;
    }

    /**
     * @param array<string, Project>  $projects
     * @param array<string, Category> $categories
     * @param array<string, User>     $users
     */
    private function seedIssues(SymfonyStyle $io, array $projects, array $categories, array $users): void
    {
        foreach (self::ISSUES as [$projectName, $categoryName, $reporterName, $assignedName, $status, $priority, $severity, $summary, $description, $stepsToReproduce, $commentSpec]) {
            $project = $projects[$projectName];

            $existing = $this->em->getRepository(Issue::class)->findOneBy(['project' => $project, 'summary' => $summary]);
            if ($existing) {
                continue;
            }

            $issue = (new Issue())
                ->setProject($project)
                ->setCategory($categories[$categoryName])
                ->setReporter($users[$reporterName])
                ->setAssigned($assignedName ? $users[$assignedName] : null)
                ->setVisibility('public')
                ->setStatus($status)
                ->setPriority($priority)
                ->setSeverity($severity)
                ->setSummary($summary)
                ->setDescription($description)
                ->setStepsToReproduce($stepsToReproduce)
                ->setSubmittedAt(new \DateTime())
                ->setUpdatedAt(new \DateTime());
            $this->em->persist($issue);
            $io->writeln(sprintf('Created issue "%s" in "%s".', $summary, $projectName));

            if ($commentSpec) {
                [, $authorName, $content] = explode(':', $commentSpec, 3);
                $comment = (new Comment())
                    ->setIssue($issue)
                    ->setAuthor($users[$authorName])
                    ->setContent($content)
                    ->setCreatedAt(new \DateTime());
                $this->em->persist($comment);
            }
        }
    }
}
