<?php

namespace App\Test\Controller;

use App\Entity\Payment;
use App\Repository\PaymentRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PaymentControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private PaymentRepository $repository;
    private string $path = '/payment/';

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->repository = (static::getContainer()->get('doctrine'))->getRepository(Payment::class);

        foreach ($this->repository->findAll() as $object) {
            $this->repository->remove($object, true);
        }
    }

    public function testIndex(): void
    {
        $crawler = $this->client->request('GET', $this->path);

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Payment index');

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
            'payment[period]' => 'Testing',
            'payment[totalHoursDay]' => 'Testing',
            'payment[totalHoursNight]' => 'Testing',
            'payment[totalAmount]' => 'Testing',
            'payment[paymentDate]' => 'Testing',
            'payment[agent]' => 'Testing',
        ]);

        self::assertResponseRedirects('/payment/');

        self::assertSame($originalNumObjectsInRepository + 1, count($this->repository->findAll()));
    }

    public function testShow(): void
    {
        $this->markTestIncomplete();
        $fixture = new Payment();
        $fixture->setPeriod('My Title');
        $fixture->setTotalHoursDay('My Title');
        $fixture->setTotalHoursNight('My Title');
        $fixture->setTotalAmount('My Title');
        $fixture->setPaymentDate('My Title');
        $fixture->setAgent('My Title');

        $this->repository->add($fixture, true);

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Payment');

        // Use assertions to check that the properties are properly displayed.
    }

    public function testEdit(): void
    {
        $this->markTestIncomplete();
        $fixture = new Payment();
        $fixture->setPeriod('My Title');
        $fixture->setTotalHoursDay('My Title');
        $fixture->setTotalHoursNight('My Title');
        $fixture->setTotalAmount('My Title');
        $fixture->setPaymentDate('My Title');
        $fixture->setAgent('My Title');

        $this->repository->add($fixture, true);

        $this->client->request('GET', sprintf('%s%s/edit', $this->path, $fixture->getId()));

        $this->client->submitForm('Update', [
            'payment[period]' => 'Something New',
            'payment[totalHoursDay]' => 'Something New',
            'payment[totalHoursNight]' => 'Something New',
            'payment[totalAmount]' => 'Something New',
            'payment[paymentDate]' => 'Something New',
            'payment[agent]' => 'Something New',
        ]);

        self::assertResponseRedirects('/payment/');

        $fixture = $this->repository->findAll();

        self::assertSame('Something New', $fixture[0]->getPeriod());
        self::assertSame('Something New', $fixture[0]->getTotalHoursDay());
        self::assertSame('Something New', $fixture[0]->getTotalHoursNight());
        self::assertSame('Something New', $fixture[0]->getTotalAmount());
        self::assertSame('Something New', $fixture[0]->getPaymentDate());
        self::assertSame('Something New', $fixture[0]->getAgent());
    }

    public function testRemove(): void
    {
        $this->markTestIncomplete();

        $originalNumObjectsInRepository = count($this->repository->findAll());

        $fixture = new Payment();
        $fixture->setPeriod('My Title');
        $fixture->setTotalHoursDay('My Title');
        $fixture->setTotalHoursNight('My Title');
        $fixture->setTotalAmount('My Title');
        $fixture->setPaymentDate('My Title');
        $fixture->setAgent('My Title');

        $this->repository->add($fixture, true);

        self::assertSame($originalNumObjectsInRepository + 1, count($this->repository->findAll()));

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));
        $this->client->submitForm('Delete');

        self::assertSame($originalNumObjectsInRepository, count($this->repository->findAll()));
        self::assertResponseRedirects('/payment/');
    }
}
