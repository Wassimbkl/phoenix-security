<?php

namespace App\Service\Agent;

use App\Repository\AgentRepository;

class AgentSearchService
{
    public function __construct(
        private AgentRepository $agentRepo
    ) {}

    public function search(?string $q, ?string $status, ?int $siteId): array
    {
        $qb = $this->agentRepo->createQueryBuilder('a')
            ->leftJoin('a.site', 's');

        if ($q) {
            $qb->andWhere(
                'a.firstName LIKE :q 
                 OR a.lastName LIKE :q 
                 OR a.phone LIKE :q'
            )
            ->setParameter('q', '%' . $q . '%');
        }

        if ($status) {
            $qb->andWhere('a.status = :status')
               ->setParameter('status', $status);
        }

        if ($siteId) {
            $qb->andWhere('s.id = :siteId')
               ->setParameter('siteId', $siteId);
        }

        return $qb->getQuery()->getResult();
    }
}
