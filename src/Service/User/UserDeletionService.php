<?php

namespace App\Service\User;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class UserDeletionService
{
    public function __construct(
        private EntityManagerInterface $em
    ) {
    }

    /**
     * @throws \LogicException si l'utilisateur est admin
     */
    public function delete(User $user): void
    {
        if ($user->getRole() === 'ADMIN') {
            throw new \LogicException('Impossible de supprimer un administrateur.');
        }

        $this->em->remove($user);
        $this->em->flush();
    }
}
