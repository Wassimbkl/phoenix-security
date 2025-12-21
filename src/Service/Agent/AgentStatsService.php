<?php

namespace App\Service\Agent;

use App\Repository\AgentRepository;
use App\Repository\ShiftRepository;

class AgentStatsService
{
    public function __construct(
        private AgentRepository $agentRepo,
        private ShiftRepository $shiftRepo
    ) {}

    public function getAgentsWithStats(): array
    {
        $agents = $this->agentRepo->findAll();

        $totalAgents = count($agents);
        $activeAgents = $this->agentRepo->count(['status' => 'ACTIF']);

        $totalHours = $this->shiftRepo->createQueryBuilder('s')
            ->select('COUNT(s.id) * 8')
            ->getQuery()
            ->getSingleScalarResult();

        $totalSalary = $this->agentRepo->createQueryBuilder('a')
            ->select('SUM(a.hourlyRate * 160)')
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'agents' => $agents,
            'stats' => [
                'total_agents' => $totalAgents,
                'active_agents' => $activeAgents,
                'total_hours' => $totalHours,
                'total_salary' => round($totalSalary ?? 0, 2),
            ],
        ];
    }
}
