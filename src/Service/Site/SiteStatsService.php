<?php

namespace App\Service\Site;

use App\Repository\SiteRepository;
use App\Repository\ShiftRepository;

class SiteStatsService
{
    public function __construct(
        private SiteRepository $siteRepo,
        private ShiftRepository $shiftRepo
    ) {
    }

    /**
     * Retourne les sites avec le nombre d'agents distincts ayant travaillé
     */
    public function getSitesWithStats(): array
    {
        $sites = $this->siteRepo->findAll();
        $result = [];

        foreach ($sites as $site) {
            $agentCount = count(
                $this->shiftRepo->createQueryBuilder('s')
                    ->select('DISTINCT a.id')
                    ->join('s.agent', 'a')
                    ->where('s.site = :site')
                    ->setParameter('site', $site)
                    ->getQuery()
                    ->getResult()
            );

            $result[] = [
                'site' => $site,
                'agentCount' => $agentCount,
            ];
        }

        return $result;
    }
}
