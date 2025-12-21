<?php

namespace App\Service\User;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserUpdaterService
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public function update(
        User $user,
        ?string $email,
        ?string $role,
        ?string $plainPassword
    ): void {
        if ($email) {
            $user->setEmail($email);
        }

        if ($role) {
            $user->setRole($role);
        }

        if ($plainPassword && trim($plainPassword) !== '') {
            $hashedPassword = $this->passwordHasher->hashPassword(
                $user,
                $plainPassword
            );
            $user->setPassword($hashedPassword);
        }

        $this->em->flush();
    }
}
