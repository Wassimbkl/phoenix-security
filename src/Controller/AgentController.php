<?php

namespace App\Controller;

use App\Entity\Agent;
use App\Entity\User;
use App\Form\AgentType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class AgentController extends AbstractController
{
    #[Route('/admin/agent/new', name: 'app_agent_new')]
    public function new(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $agent = new Agent();
        $form = $this->createForm(AgentType::class, $agent);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // 1️⃣ Créer un compte User pour l’agent
            $user = new User();
            $user->setEmail(strtolower($agent->getFirstName() . '.' . $agent->getLastName()) . '@phoenix.com');
            $user->setPassword($passwordHasher->hashPassword($user, '0000')); // mot de passe par défaut
            $user->setRole('AGENT');

            // 2️⃣ Sauvegarder le User d’abord
            $em->persist($user);
            $em->flush(); // le User obtient un ID

            // 3️⃣ Associer le User à l’Agent
            $agent->setUser($user);
            $em->persist($agent);
            $em->flush();

            $this->addFlash('success', 'Nouvel agent ajouté avec succès !');
            return $this->redirectToRoute('admin_agents');
        }

        return $this->render('agent/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
