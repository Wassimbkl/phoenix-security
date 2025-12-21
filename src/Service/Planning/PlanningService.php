<?php

namespace App\Service\Planning;

use App\Repository\ShiftRepository;
use App\Repository\AgentRepository;
use App\Repository\SiteRepository;

class PlanningService
{
    public function __construct(
        private ShiftRepository $shiftRepo,
        private AgentRepository $agentRepo,
        private SiteRepository $siteRepo
    ) {
    }

    /**
     * Planning mois / semaine
     */
    public function getPlanningData(string $view, \DateTime $currentDate): array
    {
        if ($view === 'week') {
            $startDate = (clone $currentDate)->modify('monday this week');
            $endDate = (clone $startDate)->modify('+6 days');
        } else {
            $startDate = (clone $currentDate)->modify('first day of this month');
            $endDate = (clone $currentDate)->modify('last day of this month');
        }

        $shifts = $this->shiftRepo->createQueryBuilder('s')
            ->where('s.shiftDate BETWEEN :start AND :end')
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->orderBy('s.shiftDate', 'ASC')
            ->addOrderBy('s.startTime', 'ASC')
            ->getQuery()
            ->getResult();

        $shiftsByDate = [];
        foreach ($shifts as $shift) {
            $dateKey = $shift->getShiftDate()->format('Y-m-d');
            $shiftsByDate[$dateKey][] = $shift;
        }

        return [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'shiftsByDate' => $shiftsByDate,
            'agents' => $this->agentRepo->findBy(['status' => 'ACTIF']),
            'sites' => $this->siteRepo->findAll(),
        ];
    }

    /**
     * Planning d’un jour
     */
    public function getDayPlanning(\DateTime $date): array
    {
        $shifts = $this->shiftRepo->createQueryBuilder('s')
            ->where('s.shiftDate = :date')
            ->setParameter('date', $date)
            ->orderBy('s.startTime', 'ASC')
            ->getQuery()
            ->getResult();

        return [
            'date' => $date,
            'shifts' => $shifts,
            'agents' => $this->agentRepo->findBy(['status' => 'ACTIF']),
            'sites' => $this->siteRepo->findAll(),
        ];
    }
}
