<?php

namespace App\Controller;

use App\Repository\AgentRepository;
use App\Repository\ShiftRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;


class AdminController extends AbstractController
{
    #[Route('/admin', name: 'admin_dashboard')]
    public function index(AgentRepository $agentRepo, ShiftRepository $shiftRepo): Response
    {
        // 1️⃣ Nombre total d'agents
        $totalAgents = $agentRepo->count([]);

        // 2️⃣ Agents actifs
        $activeAgents = $agentRepo->count(['status' => 'ACTIF']);

        // 3️⃣ Déterminer la période du mois actuel
        $startOfMonth = new \DateTime('first day of this month 00:00:00');
        $endOfMonth = new \DateTime('last day of this month 23:59:59');

        // 4️⃣ Nombre total de shifts effectués ce mois
        $totalShifts = $shiftRepo->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->where('s.shiftDate BETWEEN :start AND :end')
            ->setParameters([
                'start' => $startOfMonth,
                'end' => $endOfMonth,
            ])
            ->getQuery()
            ->getSingleScalarResult();

        // 5️⃣ Heures totales (8h par shift)
        $totalHours = $totalShifts * 8;

        // 6️⃣ Calcul du salaire total du mois (chaque shift = 8h * taux horaire de l’agent)
        $salaries = $shiftRepo->createQueryBuilder('s')
            ->select('SUM(a.hourlyRate * 8)') // chaque shift = 8h
            ->join('s.agent', 'a')
            ->where('s.shiftDate BETWEEN :start AND :end')
            ->setParameters([
                'start' => $startOfMonth,
                'end' => $endOfMonth,
            ])
            ->getQuery()
            ->getSingleScalarResult();

        $totalSalary = round($salaries ?? 0, 2);

        return $this->render('admin/index.html.twig', [
            'user' => $this->getUser(),
            'stats' => [
                'total_agents' => $totalAgents,
                'active_agents' => $activeAgents,
                'total_shifts' => $totalShifts,
                'total_hours' => $totalHours,
                'total_salary' => $totalSalary,
            ],
        ]);
    }
    #[Route('/admin/agents', name: 'admin_agents')]
public function agents(
    AgentRepository $agentRepo,
    ShiftRepository $shiftRepo
): Response {
    $agents = $agentRepo->findAll();

    // Stats rapides
    $totalAgents = count($agents);
    $activeAgents = $agentRepo->count(['status' => 'ACTIF']);

    $totalHours = $shiftRepo->createQueryBuilder('s')
        ->select('COUNT(s.id) * 8')
        ->getQuery()
        ->getSingleScalarResult();

    $totalSalary = $agentRepo->createQueryBuilder('a')
        ->select('SUM(a.hourlyRate * 160)') // estimation 160h par agent
        ->getQuery()
        ->getSingleScalarResult();

    return $this->render('admin/agents.html.twig', [
        'agents' => $agents,
        'stats' => [
            'total_agents' => $totalAgents,
            'active_agents' => $activeAgents,
            'total_hours' => $totalHours,
            'total_salary' => round($totalSalary, 2),
        ],
    ]);
}
#[Route('/admin/agents/new', name: 'admin_agent_new')]
public function newAgent(
    Request $request,
    EntityManagerInterface $em
): Response {
    $agent = new Agent();

    // petit form rapide sans make:form
    $form = $this->createFormBuilder($agent)
        ->add('firstName', TextType::class, [
            'label' => 'Prénom'
        ])
        ->add('lastName', TextType::class, [
            'label' => 'Nom'
        ])
        ->add('phone', TextType::class, [
            'required' => false,
            'label' => 'Téléphone'
        ])
        ->add('hourlyRate', MoneyType::class, [
            'label' => 'Salaire horaire',
            'currency' => 'EUR'
        ])
        ->add('site', EntityType::class, [
            'class' => Site::class,
            'choice_label' => 'name',
            'label' => 'Site'
        ])
        ->getForm()
    ;

    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        // statut par défaut
        $agent->setStatus('ACTIF');
        $agent->setHireDate(new \DateTime());

        // ⚠️ si ta table agents a user_id NOT NULL, il faut lui mettre un user
        // ici on ne va pas faire un vrai compte user, on laisse comme ça
        // ou tu peux récupérer un user "générique" si tu veux

        $em->persist($agent);
        $em->flush();

        return $this->redirectToRoute('admin_agents');
    }

    return $this->render('admin/agent_new.html.twig', [
        'form' => $form->createView(),
    ]);
}
#[Route('/admin/agents/{id}/edit', name: 'admin_agent_edit')]
public function editAgent(Request $request, AgentRepository $agentRepo, EntityManagerInterface $em, int $id): Response
{
    $agent = $agentRepo->find($id);

    if (!$agent) {
        throw $this->createNotFoundException("Agent non trouvé");
    }

    $form = $this->createFormBuilder($agent)
        ->add('firstName')
        ->add('lastName')
        ->add('phone')
        ->add('hourlyRate')
        ->add('status', ChoiceType::class, [
            'choices' => [
                'Actif' => 'ACTIF',
                'Inactif' => 'INACTIF',
            ],
        ])
        ->getForm();

    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $em->flush();
        return $this->redirectToRoute('admin_agents');
    }

    return $this->render('admin/agent_edit.html.twig', [
        'form' => $form->createView(),
        'agent' => $agent,
    ]);
}

#[Route('/admin/agents/{id}/delete', name: 'admin_agent_delete', methods: ['POST'])]
public function deleteAgent(Request $request, AgentRepository $agentRepo, EntityManagerInterface $em, int $id): Response
{
    $agent = $agentRepo->find($id);
    if (!$agent) {
        throw $this->createNotFoundException("Agent non trouvé");
    }

    if ($this->isCsrfTokenValid('delete' . $agent->getId(), $request->request->get('_token'))) {
        $em->remove($agent);
        $em->flush();
    }

    return $this->redirectToRoute('admin_agents');
}
}
