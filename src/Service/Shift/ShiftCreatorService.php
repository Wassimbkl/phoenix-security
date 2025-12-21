<?php

namespace App\Service\Shift;

use App\Entity\Shift;
use Doctrine\ORM\EntityManagerInterface;

class ShiftCreatorService
{
    public function __construct(
        private EntityManagerInterface $em
    ) {
    }

    public function create(Shift $shift): void
    {
        $this->em->persist($shift);
        $this->em->flush();
    }
}
