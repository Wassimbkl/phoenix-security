<?php

namespace App\Service\Payment;

use App\Entity\Payment;
use App\Repository\AgentRepository;
use App\Repository\ShiftRepository;
use Doctrine\ORM\EntityManagerInterface;

class PaymentGeneratorService
{
    public function __construct(
        private AgentRepository $agentRepo,
        private ShiftRepository $shiftRepo,
        private EntityManagerInterface $em
    ) {
    }

    public function generateForPeriod(string $period): void
    {
        $startDate = new \DateTime($period . '-01');
        $endDate = (clone $startDate)->modify('last day of this month');

        $agents = $this->agentRepo->findBy(['status' => 'ACTIF']);

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

            if (!$shifts) {
                continue;
            }

            $hoursDay = 0;
            $hoursNight = 0;

            foreach ($shifts as $shift) {
                $start = $shift->getStartTime();
                $end = $shift->getEndTime();

                $minutes =
                    ($end->format('H') * 60 + $end->format('i')) -
                    ($start->format('H') * 60 + $start->format('i'));

                if ($minutes < 0) {
                    $minutes += 1440;
                }

                $hours = $minutes / 60;

                if ($shift->getType() === 'NUIT') {
                    $hoursNight += $hours;
                } else {
                    $hoursDay += $hours;
                }
            }

            $hourlyRate = (float) $agent->getHourlyRate();
            $totalAmount =
                ($hoursDay * $hourlyRate) +
                ($hoursNight * $hourlyRate * 1.25);

            $payment = $this->em->getRepository(Payment::class)->findOneBy([
                'agent' => $agent,
                'period' => $period,
            ]) ?? new Payment();

            $payment
                ->setAgent($agent)
                ->setPeriod($period)
                ->setTotalHoursDay((string) $hoursDay)
                ->setTotalHoursNight((string) $hoursNight)
                ->setTotalAmount((string) round($totalAmount, 2));

            $this->em->persist($payment);
        }

        $this->em->flush();
    }
}
