<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 *
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function findUserReadingUniqueKey(int $userId) : array
    {
        $entityManager = $this->getEntityManager();

        $query = $entityManager->createQuery(
            'SELECT CONCAT(r.year,r.month,b.externalId)
            FROM App\Entity\User u
            INNER JOIN u.reading r
            INNER JOIN r.book b
            WHERE u.id = :id'
        )->setParameter('id', $userId);

        return $this->_transformQueryResultToSimpleArray($query);
    }

    public function getCountDifferentAuthor(int $userId) {
        $entityManager = $this->getEntityManager();

        $query = $entityManager->createQuery(
            'SELECT COUNT(DISTINCT a.id)
            FROM App\Entity\User u
            INNER JOIN u.reading r
            INNER JOIN r.book b
            INNER JOIN b.author a
            WHERE u.id = :id'
        )->setParameter('id', $userId);

        return $query->getSingleScalarResult();
    }

    public function getCountBooks(int $userId, bool $manga = false) {
        $entityManager = $this->getEntityManager();

        $query = $entityManager->createQuery(
            'SELECT COUNT(b.id)
            FROM App\Entity\User u
            INNER JOIN u.reading r
            INNER JOIN r.book b
            WHERE u.id = :id
            AND b.isManga <> :ismanga'
        )->setParameter('id', $userId)
        ->setParameter('ismanga', $manga);

        return $query->getSingleScalarResult();
    }

    public function getAverageReading(int $userId) : int
    {
        $entityManager = $this->getEntityManager();

        $sinceYear = date("Y", strtotime(date("Y"). "- 2 years"));

        // why avg count not working ?
        $query = $entityManager->createQuery(
            'SELECT COUNT(r.id)
            FROM App\Entity\User u
            INNER JOIN u.reading r
            WHERE u.id = :id
            AND r.year >= :sinceyear
            GROUP BY r.month, r.year'
        )->setParameter('id', $userId)
            ->setParameter('sinceyear', $sinceYear);

        $countByMonth = $this->_transformQueryResultToSimpleArray($query);
        if (!empty($countByMonth)) {
            return array_sum($countByMonth) / count($countByMonth);
        }

        return 0;
    }

    //    /**
    //     * @return User[] Returns an array of User objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('u.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?User
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    protected function _transformQueryResultToSimpleArray($query)
    {
        $arrayResult = $query->getArrayResult();

        if (!empty($arrayResult)) {
            return array_column($arrayResult, "1");
        }

        return $arrayResult;
    }
}
