<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\Agent;
use App\Entity\Site;
use App\Entity\Shift;
use App\Entity\Payment;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AdminFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // --- 0️⃣ Créer un user générique pour les agents (si besoin) ---
$user = new User();
$user->setEmail('agent-userr@example.com');
$user->setPassword('$2y$13$V3DJOkRI4VDSjlBczgXR.Ohc1pXeHhtXv3pfAJ5GrDoQvFIKvXrcq'); // mot de passe 0000
$user->setRole('AGENT');
$manager->persist($user);

        // --- 1. Créer quelques sites ---
        $sites = [];
        $siteNames = [
            ['Centre Commercial', '10 rue de Paris', 'Niort', 'Paul Martin', '06.11.22.33.44'],
            ['Bureau Central', '45 avenue du Centre', 'Poitiers', 'Julie Robert', '06.55.44.33.22'],
            ['Entrepôt Nord', 'Zone Industrielle Nord', 'La Rochelle', 'Luc Bernard', '06.99.88.77.66'],
        ];

        foreach ($siteNames as [$name, $address, $city, $contactName, $contactPhone]) {
            $site = new Site();
            $site->setName($name);
            $site->setAddress($address);
            $site->setCity($city);
            $site->setContactName($contactName);
            $site->setContactPhone($contactPhone);
            $manager->persist($site);
            $sites[] = $site;
        }

        // --- 2. Créer quelques agents ---
        $agents = [];
        $agentSites = [];
        $agentData = [
            ['Jean', 'Dupont', '06.12.34.56.78', 12.50, 'ACTIF'],
            ['Marie', 'Martin', '06.98.76.54.32', 11.00, 'ACTIF'],
            ['Pierre', 'Durand', '06.55.44.33.22', 15.00, 'ACTIF'],
        ];

        foreach ($agentData as $i => [$first, $last, $phone, $rate, $status]) {
            $agent = new Agent();
            $agent->setFirstName($first);
            $agent->setLastName($last);
            $agent->setPhone($phone);
            $agent->setHourlyRate($rate);
            $agent->setHireDate(new \DateTime('2023-05-01'));
            $agent->setStatus($status);
            $manager->persist($agent);
            $agents[] = $agent;
            $agent->setUser($user);
            
            // Stocker le site correspondant pour les shifts
            $agentSites[$i] = $sites[$i % count($sites)];
        }

        // --- 3. Créer des shifts ---
        foreach ($agents as $index => $agent) {
            for ($i = 1; $i <= 5; $i++) {
                $shift = new Shift();
                $shift->setAgent($agent);
                $shift->setSite($agentSites[$index]);
                $shift->setShiftDate(new \DateTime('2025-11-' . rand(1, 7)));
                $shift->setStartTime(new \DateTime('08:00'));
                $shift->setEndTime(new \DateTime('16:00'));
                $shift->setType('JOUR');
                $shift->setStatus('EFFECTUE');
                $manager->persist($shift);
            }
        }

        // --- 4. Créer les paiements ---
        foreach ($agents as $agent) {
            $payment = new Payment();
            $payment->setAgent($agent);
            $payment->setPeriod('2025-11');
            $payment->setTotalHoursDay(140);
            $payment->setTotalHoursNight(0);
            $payment->setTotalAmount($agent->getHourlyRate() * 140);
            $payment->setPaymentDate(new \DateTime('2025-11-01'));
            $manager->persist($payment);
        }

        $manager->flush();
    }
}
