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


#[Route('/admin')]
class AdminController extends AbstractController
{
    #[Route('', name: 'admin_dashboard')]
    public function index(
        AgentRepository $agentRepo,
        ShiftRepository $shiftRepo,
        EntityManagerInterface $em
    ): Response {

        // ====== STATS PRINCIPALES ======
        $totalAgents = $agentRepo->count([]);
        $activeAgents = $agentRepo->count(['status' => 'ACTIF']);

        $startOfMonth = new \DateTime('first day of this month 00:00:00');
        $endOfMonth = new \DateTime('last day of this month 23:59:59');

        // Nombre total de shifts du mois
        $totalShifts = $shiftRepo->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->where('s.shiftDate BETWEEN :start AND :end')
            ->setParameter('start', $startOfMonth)
            ->setParameter('end', $endOfMonth)
            ->getQuery()
            ->getSingleScalarResult();

        $totalHours = $totalShifts * 8;

        // Salaire total du mois
        $totalSalary = $shiftRepo->createQueryBuilder('s')
            ->select('SUM(a.hourlyRate * 8)')
            ->join('s.agent', 'a')
            ->where('s.shiftDate BETWEEN :start AND :end')
            ->setParameter('start', $startOfMonth)
            ->setParameter('end', $endOfMonth)
            ->getQuery()
            ->getSingleScalarResult() ?? 0;


        // ====== ACTIVITÉS RÉCENTES ======
        $recentActivities = $shiftRepo->createQueryBuilder('s')
            ->join('s.agent', 'a')
            ->orderBy('s.shiftDate', 'DESC')
            ->addOrderBy('s.startTime', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();


        // ====== ALERTES ======
        $tomorrow = new \DateTime('+1 day');

        $unassignedShifts = $shiftRepo->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->where('s.shiftDate = :tomorrow')
            ->andWhere('s.agent IS NULL')
            ->setParameter('tomorrow', $tomorrow)
            ->getQuery()
            ->getSingleScalarResult();

        $lateAgents = $shiftRepo->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->where('s.startTime < :now')
            ->andWhere('s.status != :done')
            ->setParameter('now', new \DateTime())
            ->setParameter('done', 'EFFECTUE')
            ->getQuery()
            ->getSingleScalarResult();


        // ====== APERÇU DES SITES ======
        $sites = $em->getRepository(Site::class)->findAll();

        $siteOverview = [];
        foreach ($sites as $site) {
            $siteOverview[] = [
                'name'   => $site->getName(),
                'agents' => $agentRepo->count(['site' => $site]),
                'hours'  => '24h/24 - 7j/7',
            ];
        }


        return $this->render('admin/index.html.twig', [
            'stats' => [
                'total_agents' => $totalAgents,
                'active_agents' => $activeAgents,
                'total_shifts' => $totalShifts,
                'total_hours' => $totalHours,
                'total_salary' => $totalSalary,
            ],
            'recentActivities' => $recentActivities,
            'alerts' => [
                'unassigned' => $unassignedShifts,
                'late_agents' => $lateAgents,
                'monthly_report' => true
            ],
            'sites' => $siteOverview
        ]);
    }

    #[Route('/agents', name: 'admin_agents')]
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
            ->select('SUM(a.hourlyRate * 160)')
            ->getQuery()
            ->getSingleScalarResult();

        return $this->render('admin/agents.html.twig', [
            'agents' => $agents,
            'stats' => [
                'total_agents' => $totalAgents,
                'active_agents' => $activeAgents,
                'total_hours' => $totalHours,
                'total_salary' => round($totalSalary ?? 0, 2),
            ],
        ]);
    }

    #[Route('/agents/search', name: 'admin_agents_search')]
    public function agentsSearch(Request $request, AgentRepository $agentRepo, SiteRepository $siteRepo): Response
    {
        $q = $request->query->get('q', '');
        $status = $request->query->get('status', '');
        $siteId = $request->query->get('site', '');

        $qb = $agentRepo->createQueryBuilder('a')
            ->leftJoin('a.site', 's');

        if ($q) {
            $qb->andWhere('a.firstName LIKE :q OR a.lastName LIKE :q OR a.phone LIKE :q')
               ->setParameter('q', '%' . $q . '%');
        }

        if ($status) {
            $qb->andWhere('a.status = :status')
               ->setParameter('status', $status);
        }

        if ($siteId) {
            $qb->andWhere('s.id = :siteId')
               ->setParameter('siteId', $siteId);
        }

        $agents = $qb->getQuery()->getResult();

        return $this->render('admin/_agents_table.html.twig', [
            'agents' => $agents,
        ]);
    }

    #[Route('/agents/new', name: 'admin_agent_new')]
    public function newAgent(
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $agent = new Agent();

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
            $agent->setStatus('ACTIF');
            $agent->setHireDate(new \DateTime());

            $em->persist($agent);
            $em->flush();

            $this->addFlash('success', 'Agent créé avec succès !');
            return $this->redirectToRoute('admin_agents');
        }

        return $this->render('admin/agent_new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/agents/{id}/edit', name: 'admin_agent_edit')]
    public function editAgent(Request $request, AgentRepository $agentRepo, EntityManagerInterface $em, int $id): Response
    {
        $agent = $agentRepo->find($id);

        if (!$agent) {
            throw $this->createNotFoundException("Agent non trouvé");
        }

        $form = $this->createFormBuilder($agent)
            ->add('firstName', TextType::class, ['label' => 'Prénom'])
            ->add('lastName', TextType::class, ['label' => 'Nom'])
            ->add('phone', TextType::class, ['label' => 'Téléphone', 'required' => false])
            ->add('hourlyRate', MoneyType::class, ['label' => 'Taux horaire', 'currency' => 'EUR'])
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
            $em->flush();
            $this->addFlash('success', 'Agent modifié avec succès !');
            return $this->redirectToRoute('admin_agents');
        }

        return $this->render('admin/agent_edit.html.twig', [
            'form' => $form->createView(),
            'agent' => $agent,
        ]);
    }

    #[Route('/agents/{id}/delete', name: 'admin_agent_delete', methods: ['POST'])]
    public function deleteAgent(Request $request, AgentRepository $agentRepo, EntityManagerInterface $em, int $id): Response
    {
        $agent = $agentRepo->find($id);
        if (!$agent) {
            throw $this->createNotFoundException("Agent non trouvé");
        }

        if ($this->isCsrfTokenValid('delete' . $agent->getId(), $request->request->get('_token'))) {
            $em->remove($agent);
            $em->flush();
            $this->addFlash('success', 'Agent supprimé avec succès !');
        }

        return $this->redirectToRoute('admin_agents');
    }

    // ==================== PLANNING ====================

    #[Route('/planning', name: 'admin_planning')]
    public function planning(Request $request, ShiftRepository $shiftRepo, AgentRepository $agentRepo, SiteRepository $siteRepo): Response
    {
        $view = $request->query->get('view', 'month');
        $dateStr = $request->query->get('date', date('Y-m-d'));
        $currentDate = new \DateTime($dateStr);

        if ($view === 'week') {
            $startDate = (clone $currentDate)->modify('monday this week');
            $endDate = (clone $startDate)->modify('+6 days');
        } else {
            $startDate = (clone $currentDate)->modify('first day of this month');
            $endDate = (clone $currentDate)->modify('last day of this month');
        }

        $shifts = $shiftRepo->createQueryBuilder('s')
            ->where('s.shiftDate BETWEEN :start AND :end')
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->orderBy('s.shiftDate', 'ASC')
            ->addOrderBy('s.startTime', 'ASC')
            ->getQuery()
            ->getResult();

        // Organiser les shifts par date
        $shiftsByDate = [];
        foreach ($shifts as $shift) {
            $dateKey = $shift->getShiftDate()->format('Y-m-d');
            if (!isset($shiftsByDate[$dateKey])) {
                $shiftsByDate[$dateKey] = [];
            }
            $shiftsByDate[$dateKey][] = $shift;
        }

        return $this->render('admin/planning.html.twig', [
            'view' => $view,
            'currentDate' => $currentDate,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'shiftsByDate' => $shiftsByDate,
            'agents' => $agentRepo->findBy(['status' => 'ACTIF']),
            'sites' => $siteRepo->findAll(),
        ]);
    }

    #[Route('/planning/calendar', name: 'admin_planning_calendar')]
    public function planningCalendar(Request $request, ShiftRepository $shiftRepo): Response
    {
        $view = $request->query->get('view', 'month');
        $dateStr = $request->query->get('date', date('Y-m-d'));
        $currentDate = new \DateTime($dateStr);

        if ($view === 'week') {
            $startDate = (clone $currentDate)->modify('monday this week');
            $endDate = (clone $startDate)->modify('+6 days');
        } else {
            $startDate = (clone $currentDate)->modify('first day of this month');
            $endDate = (clone $currentDate)->modify('last day of this month');
        }

        $shifts = $shiftRepo->createQueryBuilder('s')
            ->where('s.shiftDate BETWEEN :start AND :end')
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->orderBy('s.shiftDate', 'ASC')
            ->addOrderBy('s.startTime', 'ASC')
            ->getQuery()
            ->getResult();

        $shiftsByDate = [];
        foreach ($shifts as $shift) {
            $dateKey = $shift->getShiftDate()->format('Y-m-d');
            if (!isset($shiftsByDate[$dateKey])) {
                $shiftsByDate[$dateKey] = [];
            }
            $shiftsByDate[$dateKey][] = $shift;
        }

        return $this->render('admin/_planning_calendar.html.twig', [
            'view' => $view,
            'currentDate' => $currentDate,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'shiftsByDate' => $shiftsByDate,
        ]);
    }

    #[Route('/planning/day/{date}', name: 'admin_planning_day')]
    public function planningDay(string $date, ShiftRepository $shiftRepo, AgentRepository $agentRepo, SiteRepository $siteRepo): Response
    {
        $currentDate = new \DateTime($date);
        
        $shifts = $shiftRepo->createQueryBuilder('s')
            ->where('s.shiftDate = :date')
            ->setParameter('date', $currentDate)
            ->orderBy('s.startTime', 'ASC')
            ->getQuery()
            ->getResult();

        return $this->render('admin/planning_day.html.twig', [
            'date' => $currentDate,
            'shifts' => $shifts,
            'agents' => $agentRepo->findBy(['status' => 'ACTIF']),
            'sites' => $siteRepo->findAll(),
        ]);
    }

    #[Route('/shifts/new', name: 'admin_shift_new')]
    public function newShift(Request $request, EntityManagerInterface $em, AgentRepository $agentRepo, SiteRepository $siteRepo): Response
    {
        $shift = new Shift();
        
        $form = $this->createFormBuilder($shift)
            ->add('agent', EntityType::class, [
                'class' => Agent::class,
                'choice_label' => fn($agent) => $agent->getFirstName() . ' ' . $agent->getLastName(),
                'label' => 'Agent',
                'query_builder' => fn($repo) => $repo->createQueryBuilder('a')->where("a.status = 'ACTIF'")->orderBy('a.lastName', 'ASC'),
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
            $em->persist($shift);
            $em->flush();
            $this->addFlash('success', 'Shift créé avec succès !');
            return $this->redirectToRoute('admin_planning');
        }

        return $this->render('admin/shift_new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/shifts/{id}/edit', name: 'admin_shift_edit')]
    public function editShift(Request $request, ShiftRepository $shiftRepo, EntityManagerInterface $em, int $id): Response
    {
        $shift = $shiftRepo->find($id);
        if (!$shift) {
            throw $this->createNotFoundException("Shift non trouvé");
        }

        $form = $this->createFormBuilder($shift)
            ->add('agent', EntityType::class, [
                'class' => Agent::class,
                'choice_label' => fn($agent) => $agent->getFirstName() . ' ' . $agent->getLastName(),
                'label' => 'Agent',
                'query_builder' => fn($repo) => $repo->createQueryBuilder('a')->where("a.status = 'ACTIF'")->orderBy('a.lastName', 'ASC'),
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
            $em->flush();
            $this->addFlash('success', 'Shift modifié avec succès !');
            return $this->redirectToRoute('admin_planning');
        }

        return $this->render('admin/shift_edit.html.twig', [
            'form' => $form->createView(),
            'shift' => $shift,
        ]);
    }

    #[Route('/shifts/{id}/delete', name: 'admin_shift_delete', methods: ['POST'])]
    public function deleteShift(Request $request, ShiftRepository $shiftRepo, EntityManagerInterface $em, int $id): Response
    {
        $shift = $shiftRepo->find($id);
        if (!$shift) {
            throw $this->createNotFoundException("Shift non trouvé");
        }

        if ($this->isCsrfTokenValid('delete' . $shift->getId(), $request->request->get('_token'))) {
            $em->remove($shift);
            $em->flush();
            $this->addFlash('success', 'Shift supprimé avec succès !');
        }

        return $this->redirectToRoute('admin_planning');
    }

    // ==================== SITES ====================

    #[Route('/sites', name: 'admin_sites')]
    public function sites(SiteRepository $siteRepo, AgentRepository $agentRepo): Response
    {
        $sites = $siteRepo->findAll();
        
        $sitesWithStats = [];
        foreach ($sites as $site) {
            $sitesWithStats[] = [
                'site' => $site,
                'agentCount' => $agentRepo->count(['site' => $site]),
            ];
        }

        return $this->render('admin/sites.html.twig', [
            'sites' => $sitesWithStats,
        ]);
    }

    #[Route('/sites/new', name: 'admin_site_new')]
    public function newSite(Request $request, EntityManagerInterface $em): Response
    {
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
            $em->persist($site);
            $em->flush();
            $this->addFlash('success', 'Site créé avec succès !');
            return $this->redirectToRoute('admin_sites');
        }

        return $this->render('admin/site_new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/sites/{id}/edit', name: 'admin_site_edit')]
    public function editSite(Request $request, SiteRepository $siteRepo, EntityManagerInterface $em, int $id): Response
    {
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
            $em->flush();
            $this->addFlash('success', 'Site modifié avec succès !');
            return $this->redirectToRoute('admin_sites');
        }

        return $this->render('admin/site_edit.html.twig', [
            'form' => $form->createView(),
            'site' => $site,
        ]);
    }

    #[Route('/sites/{id}/delete', name: 'admin_site_delete', methods: ['POST'])]
    public function deleteSite(Request $request, SiteRepository $siteRepo, EntityManagerInterface $em, int $id): Response
    {
        $site = $siteRepo->find($id);
        if (!$site) {
            throw $this->createNotFoundException("Site non trouvé");
        }

        if ($this->isCsrfTokenValid('delete' . $site->getId(), $request->request->get('_token'))) {
            $em->remove($site);
            $em->flush();
            $this->addFlash('success', 'Site supprimé avec succès !');
        }

        return $this->redirectToRoute('admin_sites');
    }

    // ==================== PAIEMENTS ====================

    #[Route('/payments', name: 'admin_payments')]
    public function payments(Request $request, PaymentRepository $paymentRepo, AgentRepository $agentRepo): Response
    {
        $period = $request->query->get('period', date('Y-m'));
        $agentId = $request->query->get('agent', '');

        $qb = $paymentRepo->createQueryBuilder('p')
            ->leftJoin('p.agent', 'a');

        if ($period) {
            $qb->andWhere('p.period = :period')
               ->setParameter('period', $period);
        }

        if ($agentId) {
            $qb->andWhere('a.id = :agentId')
               ->setParameter('agentId', $agentId);
        }

        $payments = $qb->orderBy('p.period', 'DESC')->getQuery()->getResult();

        // Stats
        $totalAmount = array_sum(array_map(fn($p) => (float)$p->getTotalAmount(), $payments));
        $totalHours = array_sum(array_map(fn($p) => (float)$p->getTotalHoursDay() + (float)$p->getTotalHoursNight(), $payments));

        return $this->render('admin/payments.html.twig', [
            'payments' => $payments,
            'agents' => $agentRepo->findAll(),
            'currentPeriod' => $period,
            'currentAgent' => $agentId,
            'stats' => [
                'total_amount' => $totalAmount,
                'total_hours' => $totalHours,
                'count' => count($payments),
            ],
        ]);
    }

    #[Route('/payments/generate', name: 'admin_payments_generate', methods: ['POST'])]
    public function generatePayments(Request $request, AgentRepository $agentRepo, ShiftRepository $shiftRepo, EntityManagerInterface $em): Response
    {
        $period = $request->request->get('period', date('Y-m'));
        
        $startDate = new \DateTime($period . '-01');
        $endDate = (clone $startDate)->modify('last day of this month');

        $agents = $agentRepo->findBy(['status' => 'ACTIF']);

        foreach ($agents as $agent) {
            // Récupérer les shifts effectués de l'agent pour ce mois
            $shifts = $shiftRepo->createQueryBuilder('s')
                ->where('s.agent = :agent')
                ->andWhere('s.shiftDate BETWEEN :start AND :end')
                ->andWhere('s.status = :status')
                ->setParameter('agent', $agent)
                ->setParameter('start', $startDate)
                ->setParameter('end', $endDate)
                ->setParameter('status', 'EFFECTUE')
                ->getQuery()
                ->getResult();

            if (count($shifts) === 0) {
                continue;
            }

            $hoursDay = 0;
            $hoursNight = 0;

            foreach ($shifts as $shift) {
                $start = $shift->getStartTime();
                $end = $shift->getEndTime();
                $duration = ($end->format('H') * 60 + $end->format('i')) - ($start->format('H') * 60 + $start->format('i'));
                if ($duration < 0) $duration += 24 * 60;
                $hours = $duration / 60;

                if ($shift->getType() === 'NUIT') {
                    $hoursNight += $hours;
                } else {
                    $hoursDay += $hours;
                }
            }

            $hourlyRate = (float)$agent->getHourlyRate();
            $nightBonus = 1.25;
            $totalAmount = ($hoursDay * $hourlyRate) + ($hoursNight * $hourlyRate * $nightBonus);

            // Créer ou mettre à jour le paiement
            $existingPayment = $em->getRepository(Payment::class)->findOneBy([
                'agent' => $agent,
                'period' => $period,
            ]);

            if ($existingPayment) {
                $payment = $existingPayment;
            } else {
                $payment = new Payment();
                $payment->setAgent($agent);
                $payment->setPeriod($period);
            }

            $payment->setTotalHoursDay((string)$hoursDay);
            $payment->setTotalHoursNight((string)$hoursNight);
            $payment->setTotalAmount((string)round($totalAmount, 2));

            $em->persist($payment);
        }

        $em->flush();
        $this->addFlash('success', 'Paiements générés pour la période ' . $period);

        return $this->redirectToRoute('admin_payments', ['period' => $period]);
    }

    #[Route('/payments/{id}/mark-paid', name: 'admin_payment_mark_paid', methods: ['POST'])]
    public function markPaymentPaid(Request $request, Payment $payment, EntityManagerInterface $em): Response
    {
        $paymentDate = $request->request->get('payment_date');
        
        if ($paymentDate) {
            $payment->setPaymentDate(new \DateTime($paymentDate));
            $em->flush();
            $this->addFlash('success', 'Paiement marqué comme effectué.');
        }

        return $this->redirectToRoute('app_payment_show', ['id' => $payment->getId()]);
    }

    // ==================== UTILISATEURS ====================

    #[Route('/users', name: 'admin_users')]
    public function users(UserRepository $userRepo): Response
    {
        return $this->render('admin/users.html.twig', [
            'users' => $userRepo->findAll(),
        ]);
    }

    // ==================== RAPPORTS ====================

    #[Route('/reports', name: 'admin_reports')]
    public function reports(AgentRepository $agentRepo, ShiftRepository $shiftRepo, PaymentRepository $paymentRepo): Response
    {
        $currentMonth = date('Y-m');
        $startOfMonth = new \DateTime('first day of this month');
        $endOfMonth = new \DateTime('last day of this month');

        // Stats globales
        $totalShifts = $shiftRepo->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->where('s.shiftDate BETWEEN :start AND :end')
            ->setParameter('start', $startOfMonth)
            ->setParameter('end', $endOfMonth)
            ->getQuery()
            ->getSingleScalarResult();

        $completedShifts = $shiftRepo->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->where('s.shiftDate BETWEEN :start AND :end')
            ->andWhere('s.status = :status')
            ->setParameter('start', $startOfMonth)
            ->setParameter('end', $endOfMonth)
            ->setParameter('status', 'EFFECTUE')
            ->getQuery()
            ->getSingleScalarResult();

        return $this->render('admin/reports.html.twig', [
            'currentMonth' => $currentMonth,
            'stats' => [
                'total_shifts' => $totalShifts,
                'completed_shifts' => $completedShifts,
                'completion_rate' => $totalShifts > 0 ? round(($completedShifts / $totalShifts) * 100, 1) : 0,
            ],
            'agents' => $agentRepo->findAll(),
        ]);
    }
}
