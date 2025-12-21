<?php

namespace App\Service\Report;

use App\Entity\Site;
use App\Repository\ShiftRepository;

class SiteDetailsReportService
{
    public function __construct(
        private ShiftRepository $shiftRepo
    ) {
    }

    public function getSiteDetails(Site $site, string $period): array
    {
        $startDate = new \DateTime($period . '-01');
        $endDate = (clone $startDate)->modify('last day of this month');

        $shifts = $this->shiftRepo->createQueryBuilder('s')
            ->leftJoin('s.agent', 'a')
            ->addSelect('a')
            ->where('s.site = :site')
            ->andWhere('s.shiftDate BETWEEN :start AND :end')
            ->setParameter('site', $site)
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->orderBy('a.lastName', 'ASC')
            ->addOrderBy('s.shiftDate', 'ASC')
            ->addOrderBy('s.startTime', 'ASC')
            ->getQuery()
            ->getResult();

        $agentsData = [];

        foreach ($shifts as $shift) {
            $agent = $shift->getAgent();
            if (!$agent) {
                continue;
            }

            $agentId = $agent->getId();

            if (!isset($agentsData[$agentId])) {
                $agentsData[$agentId] = [
                    'agent' => $agent,
                    'shifts' => [],
                    'hours_day' => 0,
                    'hours_night' => 0,
                    'total_hours' => 0,
                ];
            }

            $start = $shift->getStartTime();
            $end = $shift->getEndTime();

            $hours = ($end->getTimestamp() - $start->getTimestamp()) / 3600;
            if ($hours < 0) {
                $hours += 24;
            }

            if ($shift->getStatus() === 'EFFECTUE') {
                if ($shift->getType() === 'NUIT') {
                    $agentsData[$agentId]['hours_night'] += $hours;
                } else {
                    $agentsData[$agentId]['hours_day'] += $hours;
                }

                $agentsData[$agentId]['total_hours'] += $hours;
            }

            $agentsData[$agentId]['shifts'][] = $shift;
        }

        return $agentsData;
    }
}
