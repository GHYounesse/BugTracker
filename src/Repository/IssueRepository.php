<?php

namespace App\Repository;

use App\Entity\Category;
use App\Entity\Issue;
use App\Entity\Project;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Issue>
 *
 * @method Issue|null find($id, $lockMode = null, $lockVersion = null)
 * @method Issue|null findOneBy(array $criteria, array $orderBy = null)
 * @method Issue[]    findAll()
 * @method Issue[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class IssueRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Issue::class);
    }

    public function save(Issue $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Issue $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public const SORT_NEWEST = 'newest';
    public const SORT_PRIORITY = 'priority';
    public const SORT_DUE_DATE = 'dueDate';
    public const SORTS = [self::SORT_NEWEST, self::SORT_PRIORITY, self::SORT_DUE_DATE];

    /**
     * Issues for the dashboard, across every status.
     *
     * @param User|null     $membershipUser scope to projects this user is a member of (omit for the admin view, which sees everything)
     * @param Project|null  $project        restrict to a single project
     * @param User|null     $assignedTo     restrict to issues assigned to this user ("assigned to me")
     * @param string        $sort           one of self::SORTS
     * @param Category|null $category       restrict to a single category
     * @param string|null   $severity       restrict to a single severity, one of Issue::SEVERITIES
     */
    public function findForDashboard(
        ?User $membershipUser,
        ?Project $project,
        ?User $assignedTo,
        string $sort = self::SORT_NEWEST,
        ?Category $category = null,
        ?string $severity = null,
    ): array {
        $qb = $this->createQueryBuilder('i');

        if ($membershipUser) {
            $qb->join('i.project', 'p')
                ->join('p.members', 'pm')
                ->andWhere('pm.user = :membershipUser')
                ->setParameter('membershipUser', $membershipUser);
        }

        if ($project) {
            $qb->andWhere('i.project = :project')->setParameter('project', $project);
        }

        if ($assignedTo) {
            $qb->andWhere('i.assigned = :assignedTo')->setParameter('assignedTo', $assignedTo);
        }

        if ($category) {
            $qb->andWhere('i.category = :category')->setParameter('category', $category);
        }

        if ($severity) {
            $qb->andWhere('i.severity = :severity')->setParameter('severity', $severity);
        }

        match ($sort) {
            // priority is a free-text enum, not a naturally sortable column, so rank it explicitly
            self::SORT_PRIORITY => $qb->orderBy(
                "CASE i.priority WHEN 'immediate' THEN 0 WHEN 'urgent' THEN 1 WHEN 'high' THEN 2 WHEN 'normal' THEN 3 WHEN 'low' THEN 4 ELSE 5 END",
                'ASC'
            )->addOrderBy('i.submittedAt', 'DESC'),
            // plain ASC already puts NULLs (no due date) last on Postgres
            self::SORT_DUE_DATE => $qb->orderBy('i.dueDate', 'ASC')->addOrderBy('i.submittedAt', 'DESC'),
            default => $qb->orderBy('i.submittedAt', 'DESC'),
        };

        return $qb->getQuery()->getResult();
    }
}
