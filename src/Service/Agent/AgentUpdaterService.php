<?php

namespace App\Service\Agent;

use App\Entity\Agent;
use Doctrine\ORM\EntityManagerInterface;

class AgentUpdaterService
{
    public function __construct(
        private EntityManagerInterface $em
    ) {
    }

    /**
     
     */
    public function update(Agent $agent): void
    {
        $this->em->flush();
    }
}
