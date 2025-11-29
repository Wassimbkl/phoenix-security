<?php

namespace App\Controller;

use App\Repository\AgentRepository;
use App\Repository\ShiftRepository;
use App\Repository\PaymentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/espace-agent')]
class AgentDashboardController extends AbstractController
{
    #[Route('', name: 'agent_dashboard')]
    public function index(AgentRepository $agentRepo, ShiftRepository $shiftRepo): Response
    {
        $user = $this->getUser();
        $agent = $agentRepo->findOneBy(['user' => $user]);

        if (!$agent) {
            throw $this->createNotFoundException("Profil agent non trouvé");
        }

        // Shifts du mois en cours
        $startOfMonth = new \DateTime('first day of this month');
        $endOfMonth = new \DateTime('last day of this month');

        $shifts = $shiftRepo->createQueryBuilder('s')
            ->where('s.agent = :agent')
            ->andWhere('s.shiftDate BETWEEN :start AND :end')
            ->setParameter('agent', $agent)
            ->setParameter('start', $startOfMonth)
            ->setParameter('end', $endOfMonth)
            ->orderBy('s.shiftDate', 'ASC')
            ->getQuery()
            ->getResult();

        // Prochains shifts
        $upcomingShifts = $shiftRepo->createQueryBuilder('s')
            ->where('s.agent = :agent')
            ->andWhere('s.shiftDate >= :today')
            ->setParameter('agent', $agent)
            ->setParameter('today', new \DateTime('today'))
            ->orderBy('s.shiftDate', 'ASC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        // Calcul des heures du mois
        $totalHours = 0;
        $hoursDay = 0;
        $hoursNight = 0;
        foreach ($shifts as $shift) {
            if ($shift->getStatus() === 'EFFECTUE') {
                $start = $shift->getStartTime();
                $end = $shift->getEndTime();
                $duration = ($end->format('H') * 60 + $end->format('i')) - ($start->format('H') * 60 + $start->format('i'));
                if ($duration < 0) $duration += 24 * 60;
                $hours = $duration / 60;
                $totalHours += $hours;
                
                if ($shift->getType() === 'NUIT') {
                    $hoursNight += $hours;
                } else {
                    $hoursDay += $hours;
                }
            }
        }

        return $this->render('agent_dashboard/index.html.twig', [
            'agent' => $agent,
            'shifts' => $shifts,
            'upcomingShifts' => $upcomingShifts,
            'stats' => [
                'total_shifts' => count($shifts),
                'completed_shifts' => count(array_filter($shifts, fn($s) => $s->getStatus() === 'EFFECTUE')),
                'total_hours' => $totalHours,
                'hours_day' => $hoursDay,
                'hours_night' => $hoursNight,
            ],
        ]);
    }

    #[Route('/planning', name: 'agent_planning')]
    public function planning(Request $request, AgentRepository $agentRepo, ShiftRepository $shiftRepo): Response
    {
        $user = $this->getUser();
        $agent = $agentRepo->findOneBy(['user' => $user]);

        if (!$agent) {
            throw $this->createNotFoundException("Profil agent non trouvé");
        }

        $currentMonth = (int)$request->query->get('month', date('n'));
        $currentYear = (int)$request->query->get('year', date('Y'));

        $startDate = new \DateTime("$currentYear-$currentMonth-01");
        $endDate = (clone $startDate)->modify('last day of this month');

        $shifts = $shiftRepo->createQueryBuilder('s')
            ->where('s.agent = :agent')
            ->andWhere('s.shiftDate BETWEEN :start AND :end')
            ->setParameter('agent', $agent)
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->orderBy('s.shiftDate', 'ASC')
            ->getQuery()
            ->getResult();

        return $this->render('agent_dashboard/planning.html.twig', [
            'agent' => $agent,
            'shifts' => $shifts,
            'currentMonth' => $currentMonth,
            'currentYear' => $currentYear,
        ]);
    }

    #[Route('/paiements', name: 'agent_payments')]
    public function payments(AgentRepository $agentRepo, PaymentRepository $paymentRepo): Response
    {
        $user = $this->getUser();
        $agent = $agentRepo->findOneBy(['user' => $user]);

        if (!$agent) {
            throw $this->createNotFoundException("Profil agent non trouvé");
        }

        $payments = $paymentRepo->findBy(
            ['agent' => $agent],
            ['period' => 'DESC']
        );

        $totalAmount = 0;
        $totalHours = 0;
        foreach ($payments as $payment) {
            $totalAmount += (float)$payment->getTotalAmount();
            $totalHours += (float)$payment->getTotalHoursDay() + (float)$payment->getTotalHoursNight();
        }

        return $this->render('agent_dashboard/payments.html.twig', [
            'agent' => $agent,
            'payments' => $payments,
            'totalAmount' => $totalAmount,
            'totalHours' => $totalHours,
        ]);
    }

    #[Route('/profil', name: 'agent_profile')]
    public function profile(AgentRepository $agentRepo, ShiftRepository $shiftRepo): Response
    {
        $user = $this->getUser();
        $agent = $agentRepo->findOneBy(['user' => $user]);

        if (!$agent) {
            throw $this->createNotFoundException("Profil agent non trouvé");
        }

        // Stats globales
        $allShifts = $shiftRepo->findBy(['agent' => $agent]);
        $totalShifts = count(array_filter($allShifts, fn($s) => $s->getStatus() === 'EFFECTUE'));
        
        $totalHours = 0;
        foreach ($allShifts as $shift) {
            if ($shift->getStatus() === 'EFFECTUE') {
                $start = $shift->getStartTime();
                $end = $shift->getEndTime();
                $duration = ($end->format('H') * 60 + $end->format('i')) - ($start->format('H') * 60 + $start->format('i'));
                if ($duration < 0) $duration += 24 * 60;
                $totalHours += $duration / 60;
            }
        }

        // Mois d'ancienneté
        $monthsWorked = 0;
        if ($agent->getHireDate()) {
            $now = new \DateTime();
            $diff = $now->diff($agent->getHireDate());
            $monthsWorked = $diff->y * 12 + $diff->m;
        }

        return $this->render('agent_dashboard/profile.html.twig', [
            'agent' => $agent,
            'totalShifts' => $totalShifts,
            'totalHours' => $totalHours,
            'monthsWorked' => $monthsWorked,
        ]);
    }
}
