<?php

namespace App\Service\Report;

use App\Entity\Agent;
use App\Repository\ShiftRepository;
use App\Repository\PaymentRepository;

class AgentReportService
{
    public function __construct(
        private ShiftRepository $shiftRepo,
        private PaymentRepository $paymentRepo
    ) {
    }

    public function getMonthlyReport(Agent $agent, string $period): array
    {
        $startDate = new \DateTime($period . '-01');
        $endDate = (clone $startDate)->modify('last day of this month');

        $shifts = $this->shiftRepo->createQueryBuilder('s')
            ->leftJoin('s.site', 'site')
            ->addSelect('site')
            ->where('s.agent = :agent')
            ->andWhere('s.shiftDate BETWEEN :start AND :end')
            ->setParameter('agent', $agent)
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->orderBy('s.shiftDate', 'ASC')
            ->addOrderBy('s.startTime', 'ASC')
            ->getQuery()
            ->getResult();

        $hoursDay = 0;
        $hoursNight = 0;
        $shiftsCompleted = 0;
        $shiftsAbsent = 0;

        foreach ($shifts as $shift) {
            $start = $shift->getStartTime();
            $end = $shift->getEndTime();

            $hours = ($end->getTimestamp() - $start->getTimestamp()) / 3600;
            if ($hours < 0) {
                $hours += 24;
            }

            if ($shift->getStatus() === 'EFFECTUE') {
                $shiftsCompleted++;

                if ($shift->getType() === 'NUIT') {
                    $hoursNight += $hours;
                } else {
                    $hoursDay += $hours;
                }
            }

            if ($shift->getStatus() === 'ABSENT') {
                $shiftsAbsent++;
            }
        }

        $hourlyRate = (float) $agent->getHourlyRate();
        $amountDay = $hoursDay * $hourlyRate;
        $amountNight = $hoursNight * $hourlyRate * 1.25;

        $payment = $this->paymentRepo->findOneBy([
            'agent' => $agent,
            'period' => $period,
        ]);

        return [
            'shifts' => $shifts,
            'stats' => [
                'hours_day' => $hoursDay,
                'hours_night' => $hoursNight,
                'total_hours' => $hoursDay + $hoursNight,
                'amount_day' => $amountDay,
                'amount_night' => $amountNight,
                'total_amount' => $amountDay + $amountNight,
                'shifts_total' => count($shifts),
                'shifts_completed' => $shiftsCompleted,
                'shifts_absent' => $shiftsAbsent,
            ],
            'payment' => $payment,
        ];
    }
}
