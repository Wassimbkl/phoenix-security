<?php

namespace App\Service\Report;

use App\Repository\AgentRepository;
use App\Repository\ShiftRepository;

class HoursReportService
{
    public function __construct(
        private AgentRepository $agentRepo,
        private ShiftRepository $shiftRepo
    ) {
    }

    public function getMonthlyHours(string $period): array
    {
        $startDate = new \DateTime($period . '-01');
        $endDate = (clone $startDate)->modify('last day of this month');

        $agents = $this->agentRepo->findBy(['status' => 'ACTIF']);
        $data = [];

        foreach ($agents as $agent) {
            $shifts = $this->shiftRepo->createQueryBuilder('s')
                ->where('s.agent = :agent')
                ->andWhere('s.shiftDate BETWEEN :start AND :end')
                ->andWhere('s.status = :status')
                ->setParameter('agent', $agent)
                ->setParameter('start', $startDate)
                ->setParameter('end', $endDate)
                ->setParameter('status', 'EFFECTUE')
                ->getQuery()
                ->getResult();

            $hoursDay = 0;
            $hoursNight = 0;

            foreach ($shifts as $shift) {
                $start = $shift->getStartTime();
                $end = $shift->getEndTime();
                $hours = ($end->getTimestamp() - $start->getTimestamp()) / 3600;
                if ($hours < 0) {
                    $hours += 24;
                }

                if ($shift->getType() === 'NUIT') {
                    $hoursNight += $hours;
                } else {
                    $hoursDay += $hours;
                }
            }

            $hourlyRate = (float) $agent->getHourlyRate();

            $data[] = [
                'agent' => $agent,
                'hours_day' => $hoursDay,
                'hours_night' => $hoursNight,
                'total_hours' => $hoursDay + $hoursNight,
                'hourly_rate' => $hourlyRate,
                'amount' => ($hoursDay * $hourlyRate) + ($hoursNight * $hourlyRate * 1.25),
            ];
        }

        return $data;
    }
}
