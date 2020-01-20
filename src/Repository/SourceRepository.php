<?php

namespace App\Repository;

use App\Entity\Source;
use App\Entity\Vendor;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class SourceRepository extends ServiceEntityRepository
{
    /**
     * SourceRepository constructor.
     *
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Source::class);
    }

    /**
     * Find sources from list of match IDs and vendor.
     *
     * @param string $matchType
     * @param array $matchIdList
     * @param Vendor $vendor
     *
     * @return mixed
     *  Array of sources indexed by match id
     *
     * @throws \Doctrine\ORM\Query\QueryException
     */
    public function findByMatchIdList(string $matchType, array &$matchIdList, Vendor $vendor)
    {
        if (key($matchIdList)) {
            $idList = array_keys($matchIdList);
        } else {
            $idList = $matchIdList;
        }

        return $this->createQueryBuilder('s')
            ->andWhere('s.matchType = (:type)')
            ->andWhere('s.matchId IN (:ids)')
            ->andWhere('s.vendor = (:vendor)')
            ->setParameter('type', $matchType)
            ->setParameter('ids', $idList)
            ->setParameter('vendor', $vendor)
            ->orderBy('s.matchId', 'ASC')
            ->indexBy('s', 's.matchId')
            ->getQuery()
            ->getResult();
    }

    /**
     * Delete all sources not found in given list match IDs.
     *
     * @param array $matchIdList
     * @param Vendor $vendor
     *
     * @return mixed
     */
    public function removeIdsNotInList(array &$matchIdList, Vendor $vendor)
    {
        return $this->createQueryBuilder('s')
            ->delete('App:Source', 's')
            ->andWhere('s.matchId NOT IN (:ids)')
            ->andWhere('s.vendor = (:vendor)')
            ->setParameter('ids', $matchIdList)
            ->setParameter('vendor', $vendor)
            ->getQuery()
            ->getResult();
    }
}
