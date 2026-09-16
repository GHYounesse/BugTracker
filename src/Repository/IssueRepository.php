<?php

namespace App\Repository;

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

    /**
     * Issues for the dashboard, across every status.
     *
     * @param User|null    $membershipUser scope to projects this user is a member of (omit for the admin view, which sees everything)
     * @param Project|null $project        restrict to a single project
     * @param User|null    $assignedTo     restrict to issues assigned to this user ("assigned to me")
     */
    public function findForDashboard(?User $membershipUser, ?Project $project, ?User $assignedTo): array
    {
        $qb = $this->createQueryBuilder('i')->orderBy('i.submittedAt', 'DESC');

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

        return $qb->getQuery()->getResult();
    }
}
