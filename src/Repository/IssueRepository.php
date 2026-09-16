<?php

namespace App\Repository;

use App\Entity\Issue;
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
     * All issues across every status, for the dashboard's admin view.
     */
    public function findAllForDashboard(): array
    {
        return $this->createQueryBuilder('i')
            ->orderBy('i.submittedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * All issues across every status, scoped to projects the given user is a member of.
     */
    public function findAllForDashboardForUser(User $user): array
    {
        return $this->createQueryBuilder('i')
            ->join('i.project', 'p')
            ->join('p.members', 'pm')
            ->andWhere('pm.user = :user')
            ->setParameter('user', $user)
            ->orderBy('i.submittedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
