<?php

namespace App\Service\Report;

use App\Repository\SiteRepository;
use App\Repository\ShiftRepository;

class SiteReportService
{
    public function __construct(
        private SiteRepository $siteRepo,
        private ShiftRepository $shiftRepo
    ) {
    }

    public function getMonthlyReport(string $period, ?int $siteId = null): array
    {
        $startDate = new \DateTime($period . '-01');
        $endDate = (clone $startDate)->modify('last day of this month');

        // Sélection des sites
        if ($siteId) {
            $site = $this->siteRepo->find($siteId);
            $sites = $site ? [$site] : [];
        } else {
            $sites = $this->siteRepo->findAll();
        }

        $sitesData = [];

        foreach ($sites as $site) {
            $shifts = $this->shiftRepo->createQueryBuilder('s')
                ->leftJoin('s.agent', 'a')
                ->addSelect('a')
                ->where('s.site = :site')
                ->andWhere('s.shiftDate BETWEEN :start AND :end')
                ->setParameter('site', $site)
                ->setParameter('start', $startDate)
                ->setParameter('end', $endDate)
                ->getQuery()
                ->getResult();

            $hoursDay = 0;
            $hoursNight = 0;
            $totalCost = 0;
            $shiftsCompleted = 0;
            $agents = [];

            foreach ($shifts as $shift) {
                if (!$shift->getAgent()) {
                    continue;
                }

                $start = $shift->getStartTime();
                $end = $shift->getEndTime();

                $hours = ($end->getTimestamp() - $start->getTimestamp()) / 3600;
                if ($hours < 0) {
                    $hours += 24;
                }

                if ($shift->getStatus() === 'EFFECTUE') {
                    $shiftsCompleted++;

                    $hourlyRate = (float) $shift->getAgent()->getHourlyRate();

                    if ($shift->getType() === 'NUIT') {
                        $hoursNight += $hours;
                        $totalCost += $hours * $hourlyRate * 1.25;
                    } else {
                        $hoursDay += $hours;
                        $totalCost += $hours * $hourlyRate;
                    }
                }

                $agents[$shift->getAgent()->getId()] = true;
            }

            $sitesData[] = [
                'site' => $site,
                'shifts_total' => count($shifts),
                'shifts_completed' => $shiftsCompleted,
                'hours_day' => $hoursDay,
                'hours_night' => $hoursNight,
                'total_hours' => $hoursDay + $hoursNight,
                'total_cost' => $totalCost,
                'agents_count' => count($agents),
            ];
        }

        return $sitesData;
    }
}
