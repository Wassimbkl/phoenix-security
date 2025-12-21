<?php

namespace App\Service\Agent;

use App\Entity\Agent;
use Doctrine\ORM\EntityManagerInterface;

class AgentDeleterService
{
    public function __construct(
        private EntityManagerInterface $em
    ) {
    }

    /**
     * Supprime un agent
     */
    public function delete(Agent $agent): void
    {
        $this->em->remove($agent);
        $this->em->flush();
    }
}
