<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
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
    public function __construct(
        ManagerRegistry $registry,
        protected EntityManagerInterface $_entityManager
    )
    {
        parent::__construct($registry, User::class);
    }

    public function findUserReadingUniqueKey(int $userId) : array
    {
        $query = $this->_entityManager->createQuery(
            'SELECT CONCAT(r.year,r.month,b.externalId)
            FROM App\Entity\User u
            INNER JOIN u.reading r
            INNER JOIN r.book b
            WHERE u.id = :id'
        )->setParameter('id', $userId);

        $arrayResult = $query->getArrayResult();

        if (!empty($arrayResult)) {
            return array_column($arrayResult, "1");
        }

        return $arrayResult;
    }
}
