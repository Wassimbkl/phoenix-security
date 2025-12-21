<?php

namespace App\Service\Report;

use App\Repository\PaymentRepository;

class PaymentsReportService
{
    public function __construct(
        private PaymentRepository $paymentRepo
    ) {
    }

    public function getPaymentsBetween(string $from, string $to): array
    {
        $payments = $this->paymentRepo->createQueryBuilder('p')
            ->leftJoin('p.agent', 'a')
            ->addSelect('a')
            ->where('p.period >= :from')
            ->andWhere('p.period <= :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->orderBy('p.period', 'ASC')
            ->addOrderBy('a.lastName', 'ASC')
            ->getQuery()
            ->getResult();

        $data = [];

        foreach ($payments as $payment) {
            $hoursDay = (float) $payment->getTotalHoursDay();
            $hoursNight = (float) $payment->getTotalHoursNight();

            $data[] = [
                'period' => $payment->getPeriod(),
                'agent' => $payment->getAgent(),
                'hours_day' => $hoursDay,
                'hours_night' => $hoursNight,
                'total_hours' => $hoursDay + $hoursNight,
                'amount' => (float) $payment->getTotalAmount(),
                'payment_date' => $payment->getPaymentDate(),
                'is_paid' => $payment->getPaymentDate() !== null,
            ];
        }

        return $data;
    }
}
