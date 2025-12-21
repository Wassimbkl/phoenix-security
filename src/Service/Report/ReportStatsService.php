<?php

namespace App\Service\Report;

use App\Repository\ShiftRepository;

class ReportStatsService
{
    public function __construct(
        private ShiftRepository $shiftRepo
    ) {
    }

    public function getMonthlyStats(\DateTime $start, \DateTime $end): array
    {
        $totalShifts = $this->shiftRepo->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->where('s.shiftDate BETWEEN :start AND :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getSingleScalarResult();

        $completedShifts = $this->shiftRepo->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->where('s.shiftDate BETWEEN :start AND :end')
            ->andWhere('s.status = :status')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->setParameter('status', 'EFFECTUE')
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'total_shifts' => (int) $totalShifts,
            'completed_shifts' => (int) $completedShifts,
            'completion_rate' => $totalShifts > 0
                ? round(($completedShifts / $totalShifts) * 100, 1)
                : 0,
        ];
    }
}
