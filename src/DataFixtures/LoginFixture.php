<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class LoginFixture extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // Admin
        $admin = new User();
        $admin->setEmail('admin@example.com');
        $admin->setRole('ADMIN');
        $admin->setPassword(
            $this->passwordHasher->hashPassword($admin, 'admin123') // mot de passe initial
        );
        $manager->persist($admin);

        // Agent
        $agent = new User();
        $agent->setEmail('agent@example.com');
        $agent->setRole('AGENT');
        $agent->setPassword(
            $this->passwordHasher->hashPassword($agent, 'agent123')
        );
        $manager->persist($agent);

        $manager->flush();
    }
}
