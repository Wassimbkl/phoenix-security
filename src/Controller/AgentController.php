<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AgentController extends AbstractController
{
    #[Route('/agent', name: 'agent_dashboard')]
    public function index(): Response
    {
        return $this->render('agent/index.html.twig', [
            'user' => $this->getUser(),
        ]);
    }
}
