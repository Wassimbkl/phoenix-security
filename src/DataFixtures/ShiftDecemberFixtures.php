<?php

namespace App\DataFixtures;

use App\Entity\Shift;
use App\Repository\AgentRepository;
use App\Repository\SiteRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

class ShiftDecemberFixtures extends Fixture implements FixtureGroupInterface
{
    private AgentRepository $agentRepository;
    private SiteRepository $siteRepository;

    public function __construct(AgentRepository $agentRepository, SiteRepository $siteRepository)
    {
        $this->agentRepository = $agentRepository;
        $this->siteRepository = $siteRepository;
    }

    public static function getGroups(): array
    {
        return ['shifts', 'december'];
    }

    public function load(ObjectManager $manager): void
    {
        $agents = $this->agentRepository->findAll();
        $sites = $this->siteRepository->findAll();

        if (empty($agents) || empty($sites)) {
            echo "⚠️ Veuillez d'abord créer des agents et des sites.\n";
            return;
        }

        // Configuration des shifts de décembre 2025
        $shiftsConfig = [
            // Semaine 1 (1-7 décembre)
            ['day' => 1, 'type' => 'JOUR', 'start' => '08:00', 'end' => '16:00', 'status' => 'EFFECTUE'],
            ['day' => 1, 'type' => 'NUIT', 'start' => '20:00', 'end' => '04:00', 'status' => 'EFFECTUE'],
            ['day' => 2, 'type' => 'JOUR', 'start' => '08:00', 'end' => '16:00', 'status' => 'EFFECTUE'],
            ['day' => 2, 'type' => 'NUIT', 'start' => '20:00', 'end' => '04:00', 'status' => 'EFFECTUE'],
            ['day' => 3, 'type' => 'JOUR', 'start' => '08:00', 'end' => '16:00', 'status' => 'EFFECTUE'],
            ['day' => 3, 'type' => 'NUIT', 'start' => '20:00', 'end' => '04:00', 'status' => 'EFFECTUE'],
            ['day' => 4, 'type' => 'JOUR', 'start' => '08:00', 'end' => '16:00', 'status' => 'EFFECTUE'],
            ['day' => 4, 'type' => 'NUIT', 'start' => '20:00', 'end' => '04:00', 'status' => 'EFFECTUE'],
            ['day' => 5, 'type' => 'JOUR', 'start' => '08:00', 'end' => '16:00', 'status' => 'EFFECTUE'],
            ['day' => 5, 'type' => 'NUIT', 'start' => '20:00', 'end' => '04:00', 'status' => 'EFFECTUE'],
            ['day' => 6, 'type' => 'JOUR', 'start' => '08:00', 'end' => '16:00', 'status' => 'EFFECTUE'],
            ['day' => 7, 'type' => 'JOUR', 'start' => '08:00', 'end' => '16:00', 'status' => 'EFFECTUE'],
            
            // Semaine 2 (8-14 décembre)
            ['day' => 8, 'type' => 'JOUR', 'start' => '08:00', 'end' => '16:00', 'status' => 'EFFECTUE'],
            ['day' => 8, 'type' => 'NUIT', 'start' => '20:00', 'end' => '04:00', 'status' => 'EFFECTUE'],
            ['day' => 9, 'type' => 'JOUR', 'start' => '08:00', 'end' => '16:00', 'status' => 'EFFECTUE'],
            ['day' => 9, 'type' => 'NUIT', 'start' => '20:00', 'end' => '04:00', 'status' => 'EFFECTUE'],
            ['day' => 10, 'type' => 'JOUR', 'start' => '08:00', 'end' => '16:00', 'status' => 'EFFECTUE'],
            ['day' => 10, 'type' => 'NUIT', 'start' => '20:00', 'end' => '04:00', 'status' => 'ABSENT'],
            ['day' => 11, 'type' => 'JOUR', 'start' => '08:00', 'end' => '16:00', 'status' => 'EFFECTUE'],
            ['day' => 11, 'type' => 'NUIT', 'start' => '20:00', 'end' => '04:00', 'status' => 'EFFECTUE'],
            ['day' => 12, 'type' => 'JOUR', 'start' => '08:00', 'end' => '16:00', 'status' => 'EFFECTUE'],
            ['day' => 12, 'type' => 'NUIT', 'start' => '20:00', 'end' => '04:00', 'status' => 'EFFECTUE'],
            ['day' => 13, 'type' => 'JOUR', 'start' => '08:00', 'end' => '16:00', 'status' => 'EFFECTUE'],
            ['day' => 14, 'type' => 'JOUR', 'start' => '08:00', 'end' => '16:00', 'status' => 'EFFECTUE'],
            
            // Semaine 3 (15-21 décembre)
            ['day' => 15, 'type' => 'JOUR', 'start' => '08:00', 'end' => '16:00', 'status' => 'EFFECTUE'],
            ['day' => 15, 'type' => 'NUIT', 'start' => '20:00', 'end' => '04:00', 'status' => 'EFFECTUE'],
            ['day' => 16, 'type' => 'JOUR', 'start' => '08:00', 'end' => '16:00', 'status' => 'EFFECTUE'],
            ['day' => 16, 'type' => 'NUIT', 'start' => '20:00', 'end' => '04:00', 'status' => 'EFFECTUE'],
            ['day' => 17, 'type' => 'JOUR', 'start' => '08:00', 'end' => '16:00', 'status' => 'EFFECTUE'],
            ['day' => 17, 'type' => 'NUIT', 'start' => '20:00', 'end' => '04:00', 'status' => 'EFFECTUE'],
            ['day' => 18, 'type' => 'JOUR', 'start' => '08:00', 'end' => '16:00', 'status' => 'EFFECTUE'],
            ['day' => 18, 'type' => 'NUIT', 'start' => '20:00', 'end' => '04:00', 'status' => 'EFFECTUE'],
            ['day' => 19, 'type' => 'JOUR', 'start' => '08:00', 'end' => '16:00', 'status' => 'EFFECTUE'],
            ['day' => 19, 'type' => 'NUIT', 'start' => '20:00', 'end' => '04:00', 'status' => 'EFFECTUE'],
            ['day' => 20, 'type' => 'JOUR', 'start' => '08:00', 'end' => '16:00', 'status' => 'PREVU'],
            ['day' => 21, 'type' => 'JOUR', 'start' => '08:00', 'end' => '16:00', 'status' => 'PREVU'],
            
            // Semaine 4 - Noël (22-28 décembre)
            ['day' => 22, 'type' => 'JOUR', 'start' => '08:00', 'end' => '16:00', 'status' => 'PREVU'],
            ['day' => 22, 'type' => 'NUIT', 'start' => '20:00', 'end' => '04:00', 'status' => 'PREVU'],
            ['day' => 23, 'type' => 'JOUR', 'start' => '08:00', 'end' => '16:00', 'status' => 'PREVU'],
            ['day' => 23, 'type' => 'NUIT', 'start' => '20:00', 'end' => '04:00', 'status' => 'PREVU'],
            ['day' => 24, 'type' => 'JOUR', 'start' => '08:00', 'end' => '16:00', 'status' => 'PREVU'],
            ['day' => 24, 'type' => 'NUIT', 'start' => '20:00', 'end' => '06:00', 'status' => 'PREVU'], // Veille Noël
            ['day' => 25, 'type' => 'JOUR', 'start' => '08:00', 'end' => '16:00', 'status' => 'PREVU'], // Noël
            ['day' => 25, 'type' => 'NUIT', 'start' => '20:00', 'end' => '04:00', 'status' => 'PREVU'],
            ['day' => 26, 'type' => 'JOUR', 'start' => '08:00', 'end' => '16:00', 'status' => 'PREVU'],
            ['day' => 27, 'type' => 'JOUR', 'start' => '08:00', 'end' => '16:00', 'status' => 'PREVU'],
            ['day' => 28, 'type' => 'JOUR', 'start' => '08:00', 'end' => '16:00', 'status' => 'PREVU'],
            
            // Fin d'année (29-31 décembre)
            ['day' => 29, 'type' => 'JOUR', 'start' => '08:00', 'end' => '16:00', 'status' => 'PREVU'],
            ['day' => 29, 'type' => 'NUIT', 'start' => '20:00', 'end' => '04:00', 'status' => 'PREVU'],
            ['day' => 30, 'type' => 'JOUR', 'start' => '08:00', 'end' => '16:00', 'status' => 'PREVU'],
            ['day' => 30, 'type' => 'NUIT', 'start' => '20:00', 'end' => '04:00', 'status' => 'PREVU'],
            ['day' => 31, 'type' => 'JOUR', 'start' => '08:00', 'end' => '16:00', 'status' => 'PREVU'], // Saint-Sylvestre
            ['day' => 31, 'type' => 'NUIT', 'start' => '20:00', 'end' => '06:00', 'status' => 'PREVU'], // Réveillon
        ];

        $count = 0;
        foreach ($shiftsConfig as $config) {
            // Alterner entre les agents et les sites
            $agent = $agents[$count % count($agents)];
            $site = $sites[$count % count($sites)];

            $shift = new Shift();
            $shift->setAgent($agent);
            $shift->setSite($site);
            $shift->setShiftDate(new \DateTime("2025-12-{$config['day']}"));
            $shift->setStartTime(new \DateTime($config['start']));
            $shift->setEndTime(new \DateTime($config['end']));
            $shift->setType($config['type']);
            $shift->setStatus($config['status']);

            $manager->persist($shift);
            $count++;
        }

        $manager->flush();

        echo "✅ {$count} shifts créés pour décembre 2025 !\n";
    }
}
