<?php
// src/DataFixtures/UserFixtures.php
namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    public function __construct(private UserPasswordHasherInterface $passwordHasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        // Admin
        $admin = new User();
        $admin->setEmail('admin-test@example.com');
        // Si ton entité a setRoles([...]) utilise ça à la place :
        if (method_exists($admin, 'setRole')) {
            $admin->setRole('ADMIN'); // si tu utilises un champ role:string
        } else {
            $admin->setRoles(['ROLE_ADMIN']); // si tu as roles json
        }
        $admin->setPassword($this->passwordHasher->hashPassword($admin, '0000'));
        $manager->persist($admin);

        // Agent
        $agent = new User();
        $agent->setEmail('agent-test@example.com');
        if (method_exists($agent, 'setRole')) {
            $agent->setRole('AGENT');
        } else {
            $agent->setRoles(['ROLE_AGENT']);
        }
        $agent->setPassword($this->passwordHasher->hashPassword($agent, '0000'));
        $manager->persist($agent);

        $manager->flush();
    }
}
