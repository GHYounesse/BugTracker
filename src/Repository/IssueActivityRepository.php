<?php

namespace App\Repository;

use App\Entity\Issue;
use App\Entity\IssueActivity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<IssueActivity>
 *
 * @method IssueActivity|null find($id, $lockMode = null, $lockVersion = null)
 * @method IssueActivity|null findOneBy(array $criteria, array $orderBy = null)
 * @method IssueActivity[]    findAll()
 * @method IssueActivity[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class IssueActivityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, IssueActivity::class);
    }

    public function save(IssueActivity $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return IssueActivity[]
     */
    public function findForIssue(Issue $issue): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.issue = :issue')
            ->setParameter('issue', $issue)
            ->orderBy('a.createdAt', 'ASC')
            ->addOrderBy('a.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
