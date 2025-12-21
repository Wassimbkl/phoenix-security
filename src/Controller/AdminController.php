<?php

namespace App\Controller;

use App\Entity\Agent;
use App\Entity\Site;
use App\Entity\Shift;
use App\Entity\Payment;
use App\Entity\User;
use App\Repository\AgentRepository;
use App\Repository\SiteRepository;
use App\Repository\PaymentRepository;
use App\Repository\UserRepository;
use App\Service\PdfService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Repository\ShiftRepository;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Service\Dashboard\DashboardStatsService;
use App\Service\Agent\AgentStatsService;
use App\Service\Agent\AgentSearchService;
use App\Service\Agent\AgentCreatorService;
use App\Service\Agent\AgentUpdaterService;
use App\Service\Agent\AgentDeleterService;
use App\Service\Planning\PlanningService;
use App\Service\Shift\ShiftCreatorService;
use App\Service\Shift\ShiftUpdaterService;
use App\Service\Shift\ShiftDeleterService;
use App\Service\Site\SiteStatsService;
use App\Service\Site\SiteCreatorService;
use App\Service\Site\SiteUpdaterService;
use App\Service\Site\SiteDeleterService;
use App\Service\Payment\PaymentReportService;
use App\Service\Payment\PaymentGeneratorService;
use App\Service\Payment\PaymentStatusService;
use App\Service\User\UserUpdaterService;
use App\Service\User\UserDeletionService;
use App\Service\Report\ReportStatsService;
use App\Service\Report\HoursReportService;
use App\Service\Report\PaymentsReportService;
use App\Service\Report\AgentReportService;
use App\Service\Report\SiteReportService;
use App\Service\Report\SiteDetailsReportService;
use App\Service\Report\SiteInvoiceReportService;







#[Route('/admin')]
class AdminController extends AbstractController
{
   #[Route('', name: 'admin_dashboard')]
public function index(
    DashboardStatsService $dashboardStatsService,
    ShiftRepository $shiftRepo
): Response {

    $dashboardData = $dashboardStatsService->getDashboardData();

    // Activités récentes 
    $recentActivities = $shiftRepo->createQueryBuilder('s')
        ->join('s.agent', 'a')
        ->orderBy('s.shiftDate', 'DESC')
        ->addOrderBy('s.startTime', 'DESC')
        ->setMaxResults(5)
        ->getQuery()
        ->getResult();

    return $this->render('admin/index.html.twig', [
        'stats' => $dashboardData['stats'],
        'alerts' => $dashboardData['alerts'],
        'sites' => $dashboardData['sites'],
        'recentActivities' => $recentActivities,
    ]);
}
      
// AGENTS 
   #[Route('/agents', name: 'admin_agents')]
public function agents(
    AgentStatsService $agentStatsService
): Response {
    $data = $agentStatsService->getAgentsWithStats();

    return $this->render('admin/agents/agents.html.twig', [
        'agents' => $data['agents'],
        'stats' => $data['stats'],
    ]);
}
  #[Route('/agents/search', name: 'admin_agents_search')]
public function agentsSearch(
    Request $request,
    AgentSearchService $agentSearchService
): Response {
    $q = $request->query->get('q');
    $status = $request->query->get('status');
    $siteId = $request->query->getInt('site');

    $agents = $agentSearchService->search($q, $status, $siteId);

    return $this->render('admin/agents/_agents_table.html.twig', [
        'agents' => $agents,
    ]);
}

   #[Route('/agents/new', name: 'admin_agent_new')]
public function newAgent(
    Request $request,
    AgentCreatorService $agentCreatorService
): Response {
    $agent = new Agent();

    $form = $this->createFormBuilder($agent)
        ->add('firstName', TextType::class, ['label' => 'Prénom'])
        ->add('lastName', TextType::class, ['label' => 'Nom'])
        ->add('phone', TextType::class, [
            'required' => false,
            'label' => 'Téléphone'
        ])
        ->add('hourlyRate', MoneyType::class, [
            'label' => 'Salaire horaire',
            'currency' => 'EUR'
        ])
        ->getForm();

    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $agentCreatorService->create($agent);

        $this->addFlash('success', 'Agent créé avec succès !');
        return $this->redirectToRoute('admin_agents');
    }

    return $this->render('admin/agents/agent_new.html.twig', [
        'form' => $form->createView(),
    ]);
}


   #[Route('/agents/{id}/edit', name: 'admin_agent_edit')]
public function editAgent(
    Request $request,
    AgentRepository $agentRepo,
    AgentUpdaterService $agentUpdaterService,
    int $id
): Response {
    $agent = $agentRepo->find($id);

    if (!$agent) {
        throw $this->createNotFoundException("Agent non trouvé");
    }

    $form = $this->createFormBuilder($agent)
        ->add('firstName', TextType::class, [
            'label' => 'Prénom'
        ])
        ->add('lastName', TextType::class, [
            'label' => 'Nom'
        ])
        ->add('phone', TextType::class, [
            'label' => 'Téléphone',
            'required' => false
        ])
        ->add('hourlyRate', MoneyType::class, [
            'label' => 'Taux horaire',
            'currency' => 'EUR'
        ])
        ->add('site', EntityType::class, [
            'class' => Site::class,
            'choice_label' => 'name',
            'label' => 'Site'
        ])
        ->add('status', ChoiceType::class, [
            'label' => 'Statut',
            'choices' => [
                'Actif' => 'ACTIF',
                'Inactif' => 'INACTIF',
            ],
        ])
        ->getForm();

    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $agentUpdaterService->update($agent);

        $this->addFlash('success', 'Agent modifié avec succès !');
        return $this->redirectToRoute('admin_agents');
    }

    return $this->render('admin/agents/agent_edit.html.twig', [
        'form' => $form->createView(),
        'agent' => $agent,
    ]);
}


  #[Route('/agents/{id}/delete', name: 'admin_agent_delete', methods: ['POST'])]
public function deleteAgent(
    Request $request,
    AgentRepository $agentRepo,
    AgentDeleterService $agentDeleterService,
    int $id
): Response {
    $agent = $agentRepo->find($id);

    if (!$agent) {
        throw $this->createNotFoundException("Agent non trouvé");
    }

    if ($this->isCsrfTokenValid('delete' . $agent->getId(), $request->request->get('_token'))) {
        $agentDeleterService->delete($agent);
        $this->addFlash('success', 'Agent supprimé avec succès !');
    }

    return $this->redirectToRoute('admin_agents');
}


    // ==================== PLANNING ====================

   #[Route('/planning', name: 'admin_planning')]
public function planning(
    Request $request,
    PlanningService $planningService
): Response {
    $view = $request->query->get('view', 'month');
    $dateStr = $request->query->get('date', date('Y-m-d'));
    $currentDate = new \DateTime($dateStr);

    $data = $planningService->getPlanningData($view, $currentDate);

    return $this->render('admin/shifts/planning.html.twig', [
        'view' => $view,
        'currentDate' => $currentDate,
        'startDate' => $data['startDate'],
        'endDate' => $data['endDate'],
        'shiftsByDate' => $data['shiftsByDate'],
        'agents' => $data['agents'],
        'sites' => $data['sites'],
    ]);
}


   #[Route('/planning/calendar', name: 'admin_planning_calendar')]
public function planningCalendar(
    Request $request,
    PlanningService $planningService
): Response {
    $view = $request->query->get('view', 'month');
    $dateStr = $request->query->get('date', date('Y-m-d'));
    $currentDate = new \DateTime($dateStr);

    $data = $planningService->getPlanningData($view, $currentDate);

    return $this->render('admin/shifts/_planning_calendar.html.twig', [
        'view' => $view,
        'currentDate' => $currentDate,
        'startDate' => $data['startDate'],
        'endDate' => $data['endDate'],
        'shiftsByDate' => $data['shiftsByDate'],
    ]);
}


   #[Route('/planning/day/{date}', name: 'admin_planning_day')]
public function planningDay(
    string $date,
    PlanningService $planningService
): Response {
    $currentDate = new \DateTime($date);

    $data = $planningService->getDayPlanning($currentDate);

    return $this->render('admin/shifts/planning_day.html.twig', $data);
}




#[Route('/shifts/new', name: 'admin_shift_new')]
public function newShift(
    Request $request,
    ShiftCreatorService $shiftCreator,
    AgentRepository $agentRepo,
    SiteRepository $siteRepo
): Response {
    $shift = new Shift();

    $form = $this->createFormBuilder($shift)
        ->add('agent', EntityType::class, [
            'class' => Agent::class,
            'choice_label' => fn($agent) => $agent->getFirstName().' '.$agent->getLastName(),
            'label' => 'Agent',
            'query_builder' => fn($repo) =>
                $repo->createQueryBuilder('a')
                     ->where("a.status = 'ACTIF'")
                     ->orderBy('a.lastName', 'ASC'),
        ])
        ->add('site', EntityType::class, [
            'class' => Site::class,
            'choice_label' => 'name',
            'label' => 'Site',
        ])
        ->add('shiftDate', null, [
            'widget' => 'single_text',
            'label' => 'Date',
        ])
        ->add('startTime', null, [
            'widget' => 'single_text',
            'label' => 'Heure de début',
        ])
        ->add('endTime', null, [
            'widget' => 'single_text',
            'label' => 'Heure de fin',
        ])
        ->add('type', ChoiceType::class, [
            'label' => 'Type',
            'choices' => [
                'Jour' => 'JOUR',
                'Nuit' => 'NUIT',
            ],
        ])
        ->add('status', ChoiceType::class, [
            'label' => 'Statut',
            'choices' => [
                'Planifié' => 'PLANIFIE',
                'Confirmé' => 'CONFIRME',
                'Effectué' => 'EFFECTUE',
                'Annulé' => 'ANNULE',
            ],
        ])
        ->getForm();

    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $shiftCreator->create($shift);

        $this->addFlash('success', 'Shift créé avec succès !');
        return $this->redirectToRoute('admin_planning');
    }

    return $this->render('admin/shifts/shift_new.html.twig', [
        'form' => $form->createView(),
    ]);
}



#[Route('/shifts/{id}/edit', name: 'admin_shift_edit')]
public function editShift(
    Request $request,
    ShiftRepository $shiftRepo,
    ShiftUpdaterService $shiftUpdater,
    int $id
): Response {
    $shift = $shiftRepo->find($id);
    if (!$shift) {
        throw $this->createNotFoundException("Shift non trouvé");
    }

    $form = $this->createFormBuilder($shift)
        ->add('agent', EntityType::class, [
            'class' => Agent::class,
            'choice_label' => fn($agent) => $agent->getFirstName().' '.$agent->getLastName(),
            'label' => 'Agent',
            'query_builder' => fn($repo) =>
                $repo->createQueryBuilder('a')
                     ->where("a.status = 'ACTIF'")
                     ->orderBy('a.lastName', 'ASC'),
        ])
        ->add('site', EntityType::class, [
            'class' => Site::class,
            'choice_label' => 'name',
            'label' => 'Site',
        ])
        ->add('shiftDate', null, [
            'widget' => 'single_text',
            'label' => 'Date',
        ])
        ->add('startTime', null, [
            'widget' => 'single_text',
            'label' => 'Heure de début',
        ])
        ->add('endTime', null, [
            'widget' => 'single_text',
            'label' => 'Heure de fin',
        ])
        ->add('type', ChoiceType::class, [
            'label' => 'Type',
            'choices' => [
                'Jour' => 'JOUR',
                'Nuit' => 'NUIT',
            ],
        ])
        ->add('status', ChoiceType::class, [
            'label' => 'Statut',
            'choices' => [
                'Planifié' => 'PLANIFIE',
                'Confirmé' => 'CONFIRME',
                'Effectué' => 'EFFECTUE',
                'Annulé' => 'ANNULE',
            ],
        ])
        ->getForm();

    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $shiftUpdater->update($shift);

        $this->addFlash('success', 'Shift modifié avec succès !');
        return $this->redirectToRoute('admin_planning');
    }

    return $this->render('admin/shifts/shift_edit.html.twig', [
        'form' => $form->createView(),
        'shift' => $shift,
    ]);
}


   

#[Route('/shifts/{id}/delete', name: 'admin_shift_delete', methods: ['POST'])]
public function deleteShift(
    Request $request,
    ShiftRepository $shiftRepo,
    ShiftDeleterService $shiftDeleter,
    int $id
): Response {
    $shift = $shiftRepo->find($id);
    if (!$shift) {
        throw $this->createNotFoundException("Shift non trouvé");
    }

    if ($this->isCsrfTokenValid('delete' . $shift->getId(), $request->request->get('_token'))) {
        $shiftDeleter->delete($shift);
        $this->addFlash('success', 'Shift supprimé avec succès !');
    }

    return $this->redirectToRoute('admin_planning');
}


    // ==================== SITES ====================

    #[Route('/sites', name: 'admin_sites')]
public function sites(SiteStatsService $siteStatsService): Response
{
    return $this->render('admin/sites/sites.html.twig', [
        'sites' => $siteStatsService->getSitesWithStats(),
    ]);
}

#[Route('/sites/new', name: 'admin_site_new')]
public function newSite(
    Request $request,
    SiteCreatorService $siteCreatorService
): Response {
    $site = new Site();

    $form = $this->createFormBuilder($site)
        ->add('name', TextType::class, ['label' => 'Nom du site'])
        ->add('address', TextType::class, ['label' => 'Adresse', 'required' => false])
        ->add('city', TextType::class, ['label' => 'Ville', 'required' => false])
        ->add('contactName', TextType::class, ['label' => 'Nom du contact', 'required' => false])
        ->add('contactPhone', TextType::class, ['label' => 'Téléphone contact', 'required' => false])
        ->getForm();

    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $siteCreatorService->create($site);

        $this->addFlash('success', 'Site créé avec succès !');
        return $this->redirectToRoute('admin_sites');
    }

    return $this->render('admin/sites/site_new.html.twig', [
        'form' => $form->createView(),
    ]);
}


   

#[Route('/sites/{id}/edit', name: 'admin_site_edit')]
public function editSite(
    Request $request,
    SiteRepository $siteRepo,
    SiteUpdaterService $siteUpdaterService,
    int $id
): Response {
    $site = $siteRepo->find($id);

    if (!$site) {
        throw $this->createNotFoundException("Site non trouvé");
    }

    $form = $this->createFormBuilder($site)
        ->add('name', TextType::class, ['label' => 'Nom du site'])
        ->add('address', TextType::class, ['label' => 'Adresse', 'required' => false])
        ->add('city', TextType::class, ['label' => 'Ville', 'required' => false])
        ->add('contactName', TextType::class, ['label' => 'Nom du contact', 'required' => false])
        ->add('contactPhone', TextType::class, ['label' => 'Téléphone contact', 'required' => false])
        ->getForm();

    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $siteUpdaterService->update($site);

        $this->addFlash('success', 'Site modifié avec succès !');
        return $this->redirectToRoute('admin_sites');
    }

    return $this->render('admin/sites/site_edit.html.twig', [
        'form' => $form->createView(),
        'site' => $site,
    ]);
}


    

#[Route('/sites/{id}/delete', name: 'admin_site_delete', methods: ['POST'])]
public function deleteSite(
    Request $request,
    SiteRepository $siteRepo,
    SiteDeleterService $siteDeleterService,
    int $id
): Response {
    $site = $siteRepo->find($id);

    if (!$site) {
        throw $this->createNotFoundException("Site non trouvé");
    }

    if ($this->isCsrfTokenValid('delete' . $site->getId(), $request->request->get('_token'))) {
        $siteDeleterService->delete($site);
        $this->addFlash('success', 'Site supprimé avec succès !');
    }

    return $this->redirectToRoute('admin_sites');
}


    // ==================== PAIEMENTS ====================



#[Route('/payments', name: 'admin_payments')]
public function payments(
    Request $request,
    PaymentReportService $paymentReportService,
    AgentRepository $agentRepo
): Response {
    $period = $request->query->get('period', date('Y-m'));
    $agentId = $request->query->get('agent');

    $report = $paymentReportService->getPaymentsReport(
        $period,
        $agentId ? (int) $agentId : null
    );

    return $this->render('admin/payments/payments.html.twig', [
        'payments' => $report['payments'],
        'agents' => $agentRepo->findAll(),
        'currentPeriod' => $period,
        'currentAgent' => $agentId,
        'stats' => $report['stats'],
    ]);
}


#[Route('/payments/generate', name: 'admin_payments_generate', methods: ['POST'])]
public function generatePayments(
    Request $request,
    PaymentGeneratorService $paymentGeneratorService
): Response {
    $period = $request->request->get('period', date('Y-m'));

    $paymentGeneratorService->generateForPeriod($period);

    $this->addFlash(
        'success',
        'Paiements générés pour la période ' . $period
    );

    return $this->redirectToRoute('admin_payments', [
        'period' => $period,
    ]);
}

#[Route('/payments/{id}/mark-paid', name: 'admin_payment_mark_paid', methods: ['POST'])]
public function markPaymentPaid(
    Request $request,
    Payment $payment,
    PaymentStatusService $paymentStatusService
): Response {
    $paymentDate = $request->request->get('payment_date');

    if ($paymentDate) {
        $paymentStatusService->markAsPaid(
            $payment,
            new \DateTime($paymentDate)
        );

        $this->addFlash('success', 'Paiement marqué comme effectué.');
    }

    return $this->redirectToRoute('admin_payments');
}


    // ==================== UTILISATEURS ====================

#[Route('/users', name: 'admin_users')]
public function users(UserRepository $userRepo): Response
{
    return $this->render('admin/users/users.html.twig', [
        'users' => $userRepo->findAll(),
    ]);
}

#[Route('/users/{id}/edit', name: 'admin_user_edit', methods: ['POST'])]
public function editUser(
    Request $request,
    User $user,
    UserUpdaterService $userUpdaterService
): Response {
    $userUpdaterService->update(
        $user,
        $request->request->get('email'),
        $request->request->get('role'),
        $request->request->get('password')
    );

    $this->addFlash('success', 'Utilisateur modifié avec succès.');

    return $this->redirectToRoute('admin_users');
}

#[Route('/users/{id}/delete', name: 'admin_user_delete', methods: ['POST'])]
public function deleteUser(
    Request $request,
    User $user,
    UserDeletionService $userDeletionService
): Response {
    if ($this->isCsrfTokenValid('delete' . $user->getId(), $request->request->get('_token'))) {
        try {
            $userDeletionService->delete($user);
            $this->addFlash('success', 'Utilisateur supprimé avec succès.');
        } catch (\LogicException $e) {
            $this->addFlash('error', $e->getMessage());
        }
    }

    return $this->redirectToRoute('admin_users');
}


    // ==================== RAPPORTS ====================

#[Route('/reports', name: 'admin_reports')]
public function reports(
    AgentRepository $agentRepo,
    SiteRepository $siteRepo,
    ReportStatsService $reportStatsService
): Response {
    $currentMonth = date('Y-m');
    $startOfMonth = new \DateTime('first day of this month');
    $endOfMonth = new \DateTime('last day of this month');

    $stats = $reportStatsService->getMonthlyStats($startOfMonth, $endOfMonth);

    return $this->render('admin/reports/reports.html.twig', [
        'currentMonth' => $currentMonth,
        'stats' => $stats,
        'agents' => $agentRepo->findAll(),
        'sites' => $siteRepo->findAll(),
    ]);
}

#[Route('/reports/hours-csv', name: 'admin_report_hours_csv')]
public function exportHoursCsv(
    Request $request,
    HoursReportService $hoursReportService
): Response {
    $period = $request->query->get('period', date('Y-m'));

    $rows = [];
    $rows[] = ['Agent', 'Heures Jour', 'Heures Nuit', 'Total Heures', 'Taux Horaire', 'Montant Estimé'];

    $data = $hoursReportService->getMonthlyHours($period);

    foreach ($data as $item) {
        $rows[] = [
            $item['agent']->getFirstName() . ' ' . $item['agent']->getLastName(),
            number_format($item['hours_day'], 2),
            number_format($item['hours_night'], 2),
            number_format($item['total_hours'], 2),
            number_format($item['hourly_rate'], 2) . '€',
            number_format($item['amount'], 2) . '€',
        ];
    }

    $csv = $this->generateCsv($rows);

    return new Response($csv, 200, [
        'Content-Type' => 'text/csv; charset=utf-8',
        'Content-Disposition' => 'attachment; filename="rapport-heures-' . $period . '.csv"',
    ]);
}


#[Route('/reports/payments-csv', name: 'admin_report_payments_csv')]
public function exportPaymentsCsv(
    Request $request,
    PaymentsReportService $paymentsReportService
): Response {
    $periodFrom = $request->query->get('from', date('Y-m'));
    $periodTo = $request->query->get('to', date('Y-m'));

    $rows = [];
    $rows[] = ['Période', 'Agent', 'Heures Jour', 'Heures Nuit', 'Total Heures', 'Montant', 'Date Paiement', 'Statut'];

    $data = $paymentsReportService->getPaymentsBetween($periodFrom, $periodTo);

    foreach ($data as $item) {
        $rows[] = [
            $item['period'],
            $item['agent']->getFirstName() . ' ' . $item['agent']->getLastName(),
            number_format($item['hours_day'], 2),
            number_format($item['hours_night'], 2),
            number_format($item['total_hours'], 2),
            number_format($item['amount'], 2) . '€',
            $item['payment_date'] ? $item['payment_date']->format('d/m/Y') : '',
            $item['is_paid'] ? 'Payé' : 'En attente',
        ];
    }

    $csv = $this->generateCsv($rows);

    return new Response($csv, 200, [
        'Content-Type' => 'text/csv; charset=utf-8',
        'Content-Disposition' => sprintf(
            'attachment; filename="export-paiements-%s-a-%s.csv"',
            $periodFrom,
            $periodTo
        ),
    ]);
}


#[Route('/reports/agent/{id}', name: 'admin_report_agent')]
public function agentReport(
    Agent $agent,
    Request $request,
    AgentReportService $agentReportService
): Response {
    $period = $request->query->get('period', date('Y-m'));

    $report = $agentReportService->getMonthlyReport($agent, $period);

    return $this->render('admin/reports/report_agent.html.twig', [
        'agent' => $agent,
        'period' => $period,
        'shifts' => $report['shifts'],
        'stats' => $report['stats'],
        'payment' => $report['payment'],
    ]);
}


#[Route('/reports/sites', name: 'admin_report_sites')]
public function sitesReport(
    Request $request,
    SiteReportService $siteReportService
): Response {
    $period = $request->query->get('period', date('Y-m'));
    $siteId = $request->query->get('site');

    $sitesData = $siteReportService->getMonthlyReport(
        $period,
        $siteId ? (int) $siteId : null
    );

    return $this->render('admin/reports/report_sites.html.twig', [
        'period' => $period,
        'sitesData' => $sitesData,
        'siteId' => $siteId,
    ]);
}


#[Route('/reports/site/{id}/details', name: 'admin_report_site_details')]
public function siteDetails(
    Site $site,
    Request $request,
    SiteDetailsReportService $siteDetailsReportService
): Response {
    $period = $request->query->get('period', date('Y-m'));

    $agentsData = $siteDetailsReportService->getSiteDetails(
        $site,
        $period
    );

    return $this->render('admin/reports/report_site_details.html.twig', [
        'site' => $site,
        'period' => $period,
        'agentsData' => $agentsData,
    ]);
}

#[Route('/reports/site/{id}/facture', name: 'admin_report_site_facture')]
public function generateSiteInvoice(
    Site $site,
    Request $request,
    SiteInvoiceReportService $invoiceService,
    PdfService $pdfService
): Response {
    $period = $request->query->get('period', date('Y-m'));

    $invoiceData = $invoiceService->buildInvoiceData($site, $period);
    $periodLabel = $invoiceService->buildPeriodLabel($period);

    // Taux horaires (plus tard : config ou DB)
    $hourlyRateDay = 15.00;
    $hourlyRateNight = 18.00;

    $invoiceNumber = 'FAC-' . $site->getId() . '-' . str_replace('-', '', $period);

    $pdfContent = $pdfService->generatePdf(
        'admin/reports/facture_site.html.twig',
        [
            'site' => $site,
            'period' => $period,
            'periodLabel' => $periodLabel,
            'invoiceNumber' => $invoiceNumber,
            'invoiceDate' => new \DateTime(),
            'hourlyRateDay' => $hourlyRateDay,
            'hourlyRateNight' => $hourlyRateNight,
            ...$invoiceData,
        ]
    );

    $filename = 'Facture_' .
        preg_replace('/[^a-zA-Z0-9]/', '_', $site->getName()) .
        '_' . $period . '.pdf';

    return new Response($pdfContent, 200, [
        'Content-Type' => 'application/pdf',
        'Content-Disposition' => 'inline; filename="' . $filename . '"',
    ]);
}


}
