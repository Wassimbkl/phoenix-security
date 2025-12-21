<?php

namespace App\Service\Agent;

use App\Entity\Agent;
use Doctrine\ORM\EntityManagerInterface;

class AgentCreatorService
{
    public function __construct(
        private EntityManagerInterface $em
    ) {}

    public function create(Agent $agent): void
    {
        // Règles métier centralisées
        $agent->setStatus('ACTIF');
        $agent->setHireDate(new \DateTime());

        $this->em->persist($agent);
        $this->em->flush();
    }
}
