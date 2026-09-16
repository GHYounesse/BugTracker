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

    private function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.status = :val')
            ->setParameter('val', $status)
            ->orderBy('i.submittedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findNew(): array
    {
        return $this->findByStatus('new');
    }

    public function findProcessed(): array
    {
        return $this->findByStatus('processed');
    }

    public function findAccepted(): array
    {
        return $this->findByStatus('accepted');
    }

    private function findByStatusForUser(string $status, User $user): array
    {
        return $this->createQueryBuilder('i')
            ->join('i.project', 'p')
            ->join('p.members', 'pm')
            ->andWhere('i.status = :val')
            ->andWhere('pm.user = :user')
            ->setParameter('val', $status)
            ->setParameter('user', $user)
            ->orderBy('i.submittedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findNewForUser(User $user): array
    {
        return $this->findByStatusForUser('new', $user);
    }

    public function findProcessedForUser(User $user): array
    {
        return $this->findByStatusForUser('processed', $user);
    }

    public function findAcceptedForUser(User $user): array
    {
        return $this->findByStatusForUser('accepted', $user);
    }
}
