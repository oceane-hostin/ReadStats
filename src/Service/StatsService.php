<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;

class StatsService
{
    const STATS_FROM_LAST_X_YEAR = 2;

    protected int $_sinceYear;

    public function __construct(
        protected EntityManagerInterface $_entityManager
    ){}

    public function getUserCountDifferentAuthor(int $userId) {
        $query = $this->_entityManager->createQuery(
            'SELECT COUNT(DISTINCT a.id)
            FROM App\Entity\User u
            INNER JOIN u.reading r
            INNER JOIN r.book b
            INNER JOIN b.author a
            WHERE u.id = :id'
        )->setParameter('id', $userId);

        return $query->getSingleScalarResult();
    }

    public function getUserBookReadCount(int $userId, bool $manga = false) {
        $query = $this->_entityManager->createQuery(
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

    public function getUserAverageReadCountByMonth(int $userId) : int
    {
        // why avg count not working ?
        $query = $this->_entityManager->createQuery(
            'SELECT COUNT(r.id)
            FROM App\Entity\User u
            INNER JOIN u.reading r
            WHERE u.id = :id
            AND r.year >= :sinceyear
            GROUP BY r.month, r.year'
        )->setParameter('id', $userId)
            ->setParameter('sinceyear', $this->getSinceYearStatPeriod());

        $countByMonth = $this->_transformQueryResultToSimpleArray($query);
        if (!empty($countByMonth)) {
            return array_sum($countByMonth) / count($countByMonth);
        }

        return 0;
    }

    public function getUserReadingDetailsByMonth(int $userId)
    {
        $query = $this->_entityManager->createQuery(
            'SELECT r.year, r.month,
                    SUM(case when b.isManga = 1 then 1 else 0 end) as manga_count,
                    SUM(case when b.isManga = 0 then 1 else 0 end) as book_count
            FROM App\Entity\User u
            INNER JOIN u.reading r
            INNER JOIN r.book b
            WHERE u.id = :id
            AND r.year >= :sinceyear
            GROUP BY r.month, r.year
            ORDER BY r.year, r.month'
        )->setParameter('id', $userId)
            ->setParameter('sinceyear', $this->getSinceYearStatPeriod());

        return $query->getArrayResult();
    }

    public function getUserAuthorsTop(int $userId, int $count = 10)
    {
        $query = $this->_entityManager->createQuery(
            'SELECT COUNT(b.id) as book_count, a.firstname, a.lastname
            FROM App\Entity\User u
            INNER JOIN u.reading r
            INNER JOIN r.book b
            INNER JOIN b.author a
            WHERE u.id = :id
            AND b.isManga = false
            GROUP BY a.id
            ORDER BY book_count DESC'
        )->setParameter('id', $userId)
        ->setMaxResults($count);

        return $query->getArrayResult();
    }

    public function getUserSupportBookNumbers(int $userId)
    {
        $query = $this->_entityManager->createQuery(
            'SELECT COUNT(r.id),
                    SUM(case when r.isBorrowed = 1 then 1 else 0 end) as borrowed_count,
                    SUM(case when r.isOwned = 1 then 1 else 0 end) as owned_count,
                    SUM(case when r.isEbook = 1 then 1 else 0 end) as ebook_count
            FROM App\Entity\User u
            INNER JOIN u.reading r
            WHERE u.id = :id'
        )->setParameter('id', $userId);

        return $query->getArrayResult();
    }

    public function getUserSeriesBookNumbers(int $userId)
    {
        $query = $this->_entityManager->createQuery(
            "SELECT SUM(case when b.title LIKE '%tome%' then 1 else 0 end) as serie_count,
                    SUM(case when b.title LIKE '%tome%' then 0 else 1 end) as out_serie_count
            FROM App\Entity\User u
            INNER JOIN u.reading r
            INNER JOIN r.book b
            WHERE u.id = :id
            AND b.isManga = 0"
        )->setParameter('id', $userId);

        return $query->getArrayResult();
    }


    public function getUserTagsTop(int $userId, int $count = 10)
    {
        $query = $this->_entityManager->createQuery(
            'SELECT SUM(case when b.isManga = 1 then 1 else 0 end) as manga_count,
                    SUM(case when b.isManga = 0 then 1 else 0 end) as book_count,
                    t.name
            FROM App\Entity\User u
            INNER JOIN u.reading r
            INNER JOIN r.book b
            INNER JOIN b.tags t
            WHERE u.id = :id
            GROUP BY t.id
            ORDER BY COUNT(b.id) DESC'
        )->setParameter('id', $userId)
            ->setMaxResults($count);

        return $query->getArrayResult();
    }

    protected function getSinceYearStatPeriod() : int
    {
        if(!isset($this->_sinceYear)) {
            $this->_sinceYear = date("Y",
                strtotime(date("Y"). "- ".self::STATS_FROM_LAST_X_YEAR." years")
            );
        }

        return $this->_sinceYear;
    }

    protected function _transformQueryResultToSimpleArray($query)
    {
        $arrayResult = $query->getArrayResult();

        if (!empty($arrayResult)) {
            return array_column($arrayResult, "1");
        }

        return $arrayResult;
    }
}
