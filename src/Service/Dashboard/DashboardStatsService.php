<?php

namespace App\Service\Dashboard;

use App\Entity\Site;
use App\Repository\AgentRepository;
use App\Repository\ShiftRepository;
use Doctrine\ORM\EntityManagerInterface;

class DashboardStatsService
{
    public function __construct(
        private AgentRepository $agentRepo,
        private ShiftRepository $shiftRepo,
        private EntityManagerInterface $em
    ) {}

    public function getDashboardData(): array
    {
        // ====== STATS PRINCIPALES ======
        $totalAgents = $this->agentRepo->count([]);
        $activeAgents = $this->agentRepo->count(['status' => 'ACTIF']);

        $startOfMonth = new \DateTime('first day of this month 00:00:00');
        $endOfMonth   = new \DateTime('last day of this month 23:59:59');

        $totalShifts = $this->shiftRepo->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->where('s.shiftDate BETWEEN :start AND :end')
            ->setParameter('start', $startOfMonth)
            ->setParameter('end', $endOfMonth)
            ->getQuery()
            ->getSingleScalarResult();

        $totalHours = $totalShifts * 8;

        $totalSalary = $this->shiftRepo->createQueryBuilder('s')
            ->select('SUM(a.hourlyRate * 8)')
            ->join('s.agent', 'a')
            ->where('s.shiftDate BETWEEN :start AND :end')
            ->setParameter('start', $startOfMonth)
            ->setParameter('end', $endOfMonth)
            ->getQuery()
            ->getSingleScalarResult() ?? 0;

        // ====== ALERTES ======
        $tomorrow = new \DateTime('+1 day');

        $unassignedShifts = $this->shiftRepo->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->where('s.shiftDate = :tomorrow')
            ->andWhere('s.agent IS NULL')
            ->setParameter('tomorrow', $tomorrow)
            ->getQuery()
            ->getSingleScalarResult();

        $lateAgents = $this->shiftRepo->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->where('s.startTime < :now')
            ->andWhere('s.status != :done')
            ->setParameter('now', new \DateTime())
            ->setParameter('done', 'EFFECTUE')
            ->getQuery()
            ->getSingleScalarResult();

        // ====== APERÇU DES SITES ======
        $sites = $this->em->getRepository(Site::class)->findAll();
        $siteOverview = [];

        foreach ($sites as $site) {
            $agentCount = count(
                $this->shiftRepo->createQueryBuilder('s')
                    ->select('DISTINCT a.id')
                    ->join('s.agent', 'a')
                    ->where('s.site = :site')
                    ->setParameter('site', $site)
                    ->getQuery()
                    ->getResult()
            );

            $siteOverview[] = [
                'name'   => $site->getName(),
                'agents' => $agentCount,
                'hours'  => '24h/24 - 7j/7',
            ];
        }

        return [
            'stats' => [
                'total_agents'  => $totalAgents,
                'active_agents' => $activeAgents,
                'total_shifts'  => $totalShifts,
                'total_hours'   => $totalHours,
                'total_salary'  => $totalSalary,
            ],
            'alerts' => [
                'unassigned'   => $unassignedShifts,
                'late_agents'  => $lateAgents,
                'monthly_report' => true,
            ],
            'sites' => $siteOverview,
        ];
    }
}
