<?php

namespace App\Service\Report;

use App\Entity\Site;
use App\Repository\ShiftRepository;

class SiteInvoiceReportService
{
    public function __construct(
        private ShiftRepository $shiftRepo
    ) {
    }

    public function buildInvoiceData(Site $site, string $period): array
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
            ->getQuery()
            ->getResult();

        $agentsData = [];
        $totalHoursDay = 0;
        $totalHoursNight = 0;
        $totalShifts = 0;

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
                    $totalHoursNight += $hours;
                } else {
                    $agentsData[$agentId]['hours_day'] += $hours;
                    $totalHoursDay += $hours;
                }

                $agentsData[$agentId]['total_hours'] += $hours;
            }

            $agentsData[$agentId]['shifts'][] = $shift;
            $totalShifts++;
        }

        return [
            'agentsData' => $agentsData,
            'totalShifts' => $totalShifts,
            'totalHoursDay' => $totalHoursDay,
            'totalHoursNight' => $totalHoursNight,
            'totalHours' => $totalHoursDay + $totalHoursNight,
        ];
    }

    public function buildPeriodLabel(string $period): string
    {
        $months = [
            '01' => 'Janvier', '02' => 'Février', '03' => 'Mars',
            '04' => 'Avril', '05' => 'Mai', '06' => 'Juin',
            '07' => 'Juillet', '08' => 'Août', '09' => 'Septembre',
            '10' => 'Octobre', '11' => 'Novembre', '12' => 'Décembre',
        ];

        [$year, $month] = explode('-', $period);

        return ($months[$month] ?? '') . ' ' . $year;
    }
}
