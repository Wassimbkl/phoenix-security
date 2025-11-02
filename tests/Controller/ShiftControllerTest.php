<?php

namespace App\Test\Controller;

use App\Entity\Shift;
use App\Repository\ShiftRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ShiftControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private ShiftRepository $repository;
    private string $path = '/shift/';

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->repository = (static::getContainer()->get('doctrine'))->getRepository(Shift::class);

        foreach ($this->repository->findAll() as $object) {
            $this->repository->remove($object, true);
        }
    }

    public function testIndex(): void
    {
        $crawler = $this->client->request('GET', $this->path);

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Shift index');

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
            'shift[shiftDate]' => 'Testing',
            'shift[startTime]' => 'Testing',
            'shift[endTime]' => 'Testing',
            'shift[type]' => 'Testing',
            'shift[status]' => 'Testing',
            'shift[agent]' => 'Testing',
            'shift[site]' => 'Testing',
        ]);

        self::assertResponseRedirects('/shift/');

        self::assertSame($originalNumObjectsInRepository + 1, count($this->repository->findAll()));
    }

    public function testShow(): void
    {
        $this->markTestIncomplete();
        $fixture = new Shift();
        $fixture->setShiftDate('My Title');
        $fixture->setStartTime('My Title');
        $fixture->setEndTime('My Title');
        $fixture->setType('My Title');
        $fixture->setStatus('My Title');
        $fixture->setAgent('My Title');
        $fixture->setSite('My Title');

        $this->repository->add($fixture, true);

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Shift');

        // Use assertions to check that the properties are properly displayed.
    }

    public function testEdit(): void
    {
        $this->markTestIncomplete();
        $fixture = new Shift();
        $fixture->setShiftDate('My Title');
        $fixture->setStartTime('My Title');
        $fixture->setEndTime('My Title');
        $fixture->setType('My Title');
        $fixture->setStatus('My Title');
        $fixture->setAgent('My Title');
        $fixture->setSite('My Title');

        $this->repository->add($fixture, true);

        $this->client->request('GET', sprintf('%s%s/edit', $this->path, $fixture->getId()));

        $this->client->submitForm('Update', [
            'shift[shiftDate]' => 'Something New',
            'shift[startTime]' => 'Something New',
            'shift[endTime]' => 'Something New',
            'shift[type]' => 'Something New',
            'shift[status]' => 'Something New',
            'shift[agent]' => 'Something New',
            'shift[site]' => 'Something New',
        ]);

        self::assertResponseRedirects('/shift/');

        $fixture = $this->repository->findAll();

        self::assertSame('Something New', $fixture[0]->getShiftDate());
        self::assertSame('Something New', $fixture[0]->getStartTime());
        self::assertSame('Something New', $fixture[0]->getEndTime());
        self::assertSame('Something New', $fixture[0]->getType());
        self::assertSame('Something New', $fixture[0]->getStatus());
        self::assertSame('Something New', $fixture[0]->getAgent());
        self::assertSame('Something New', $fixture[0]->getSite());
    }

    public function testRemove(): void
    {
        $this->markTestIncomplete();

        $originalNumObjectsInRepository = count($this->repository->findAll());

        $fixture = new Shift();
        $fixture->setShiftDate('My Title');
        $fixture->setStartTime('My Title');
        $fixture->setEndTime('My Title');
        $fixture->setType('My Title');
        $fixture->setStatus('My Title');
        $fixture->setAgent('My Title');
        $fixture->setSite('My Title');

        $this->repository->add($fixture, true);

        self::assertSame($originalNumObjectsInRepository + 1, count($this->repository->findAll()));

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));
        $this->client->submitForm('Delete');

        self::assertSame($originalNumObjectsInRepository, count($this->repository->findAll()));
        self::assertResponseRedirects('/shift/');
    }
}
