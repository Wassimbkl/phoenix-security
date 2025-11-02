<?php

namespace App\Test\Controller;

use App\Entity\Agent;
use App\Repository\AgentRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AgentControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private AgentRepository $repository;
    private string $path = '/agent/';

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->repository = (static::getContainer()->get('doctrine'))->getRepository(Agent::class);

        foreach ($this->repository->findAll() as $object) {
            $this->repository->remove($object, true);
        }
    }

    public function testIndex(): void
    {
        $crawler = $this->client->request('GET', $this->path);

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Agent index');

        // Use the $crawler to perform additional assertions e.g.
        // self::assertSame('Some text on the page', $crawler->filter('.p')->first());
    }

    public function testNew(): void
    {
        $originalNumObjectsInRepository = count($this->repository->findAll());

        $this->markTestIncomplete();
        $this->client->request('GET', sprintf('%snew', $this->path));

        self::assertResponseStatusCodeSame(200);

        $this->client->submitForm('Save', [
            'agent[firstName]' => 'Testing',
            'agent[lastName]' => 'Testing',
            'agent[phone]' => 'Testing',
            'agent[hourlyRate]' => 'Testing',
            'agent[hireDate]' => 'Testing',
            'agent[status]' => 'Testing',
            'agent[user]' => 'Testing',
            'agent[site]' => 'Testing',
        ]);

        self::assertResponseRedirects('/agent/');

        self::assertSame($originalNumObjectsInRepository + 1, count($this->repository->findAll()));
    }

    public function testShow(): void
    {
        $this->markTestIncomplete();
        $fixture = new Agent();
        $fixture->setFirstName('My Title');
        $fixture->setLastName('My Title');
        $fixture->setPhone('My Title');
        $fixture->setHourlyRate('My Title');
        $fixture->setHireDate('My Title');
        $fixture->setStatus('My Title');
        $fixture->setUser('My Title');
        $fixture->setSite('My Title');

        $this->repository->add($fixture, true);

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Agent');

        // Use assertions to check that the properties are properly displayed.
    }

    public function testEdit(): void
    {
        $this->markTestIncomplete();
        $fixture = new Agent();
        $fixture->setFirstName('My Title');
        $fixture->setLastName('My Title');
        $fixture->setPhone('My Title');
        $fixture->setHourlyRate('My Title');
        $fixture->setHireDate('My Title');
        $fixture->setStatus('My Title');
        $fixture->setUser('My Title');
        $fixture->setSite('My Title');

        $this->repository->add($fixture, true);

        $this->client->request('GET', sprintf('%s%s/edit', $this->path, $fixture->getId()));

        $this->client->submitForm('Update', [
            'agent[firstName]' => 'Something New',
            'agent[lastName]' => 'Something New',
            'agent[phone]' => 'Something New',
            'agent[hourlyRate]' => 'Something New',
            'agent[hireDate]' => 'Something New',
            'agent[status]' => 'Something New',
            'agent[user]' => 'Something New',
            'agent[site]' => 'Something New',
        ]);

        self::assertResponseRedirects('/agent/');

        $fixture = $this->repository->findAll();

        self::assertSame('Something New', $fixture[0]->getFirstName());
        self::assertSame('Something New', $fixture[0]->getLastName());
        self::assertSame('Something New', $fixture[0]->getPhone());
        self::assertSame('Something New', $fixture[0]->getHourlyRate());
        self::assertSame('Something New', $fixture[0]->getHireDate());
        self::assertSame('Something New', $fixture[0]->getStatus());
        self::assertSame('Something New', $fixture[0]->getUser());
        self::assertSame('Something New', $fixture[0]->getSite());
    }

    public function testRemove(): void
    {
        $this->markTestIncomplete();

        $originalNumObjectsInRepository = count($this->repository->findAll());

        $fixture = new Agent();
        $fixture->setFirstName('My Title');
        $fixture->setLastName('My Title');
        $fixture->setPhone('My Title');
        $fixture->setHourlyRate('My Title');
        $fixture->setHireDate('My Title');
        $fixture->setStatus('My Title');
        $fixture->setUser('My Title');
        $fixture->setSite('My Title');

        $this->repository->add($fixture, true);

        self::assertSame($originalNumObjectsInRepository + 1, count($this->repository->findAll()));

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));
        $this->client->submitForm('Delete');

        self::assertSame($originalNumObjectsInRepository, count($this->repository->findAll()));
        self::assertResponseRedirects('/agent/');
    }
}
