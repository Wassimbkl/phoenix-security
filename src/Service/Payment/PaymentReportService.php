<?php

namespace App\Service\Payment;

use App\Repository\PaymentRepository;

class PaymentReportService
{
    public function __construct(
        private PaymentRepository $paymentRepo
    ) {
    }

    public function getPaymentsReport(?string $period, ?int $agentId): array
    {
        $qb = $this->paymentRepo->createQueryBuilder('p')
            ->leftJoin('p.agent', 'a');

        if ($period) {
            $qb->andWhere('p.period = :period')
               ->setParameter('period', $period);
        }

        if ($agentId) {
            $qb->andWhere('a.id = :agentId')
               ->setParameter('agentId', $agentId);
        }

        $payments = $qb
            ->orderBy('p.period', 'DESC')
            ->getQuery()
            ->getResult();

        // Stats
        $totalAmount = array_sum(
            array_map(fn($p) => (float) $p->getTotalAmount(), $payments)
        );

        $totalHours = array_sum(
            array_map(
                fn($p) =>
                    (float) $p->getTotalHoursDay() +
                    (float) $p->getTotalHoursNight(),
                $payments
            )
        );

        return [
            'payments' => $payments,
            'stats' => [
                'total_amount' => $totalAmount,
                'total_hours' => $totalHours,
                'count' => count($payments),
            ],
        ];
    }
}
