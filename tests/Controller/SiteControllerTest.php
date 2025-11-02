<?php

namespace App\Test\Controller;

use App\Entity\Site;
use App\Repository\SiteRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SiteControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private SiteRepository $repository;
    private string $path = '/site/';

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->repository = (static::getContainer()->get('doctrine'))->getRepository(Site::class);

        foreach ($this->repository->findAll() as $object) {
            $this->repository->remove($object, true);
        }
    }

    public function testIndex(): void
    {
        $crawler = $this->client->request('GET', $this->path);

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Site index');

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
            'site[name]' => 'Testing',
            'site[address]' => 'Testing',
            'site[city]' => 'Testing',
            'site[contactName]' => 'Testing',
            'site[contactPhone]' => 'Testing',
        ]);

        self::assertResponseRedirects('/site/');

        self::assertSame($originalNumObjectsInRepository + 1, count($this->repository->findAll()));
    }

    public function testShow(): void
    {
        $this->markTestIncomplete();
        $fixture = new Site();
        $fixture->setName('My Title');
        $fixture->setAddress('My Title');
        $fixture->setCity('My Title');
        $fixture->setContactName('My Title');
        $fixture->setContactPhone('My Title');

        $this->repository->add($fixture, true);

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Site');

        // Use assertions to check that the properties are properly displayed.
    }

    public function testEdit(): void
    {
        $this->markTestIncomplete();
        $fixture = new Site();
        $fixture->setName('My Title');
        $fixture->setAddress('My Title');
        $fixture->setCity('My Title');
        $fixture->setContactName('My Title');
        $fixture->setContactPhone('My Title');

        $this->repository->add($fixture, true);

        $this->client->request('GET', sprintf('%s%s/edit', $this->path, $fixture->getId()));

        $this->client->submitForm('Update', [
            'site[name]' => 'Something New',
            'site[address]' => 'Something New',
            'site[city]' => 'Something New',
            'site[contactName]' => 'Something New',
            'site[contactPhone]' => 'Something New',
        ]);

        self::assertResponseRedirects('/site/');

        $fixture = $this->repository->findAll();

        self::assertSame('Something New', $fixture[0]->getName());
        self::assertSame('Something New', $fixture[0]->getAddress());
        self::assertSame('Something New', $fixture[0]->getCity());
        self::assertSame('Something New', $fixture[0]->getContactName());
        self::assertSame('Something New', $fixture[0]->getContactPhone());
    }

    public function testRemove(): void
    {
        $this->markTestIncomplete();

        $originalNumObjectsInRepository = count($this->repository->findAll());

        $fixture = new Site();
        $fixture->setName('My Title');
        $fixture->setAddress('My Title');
        $fixture->setCity('My Title');
        $fixture->setContactName('My Title');
        $fixture->setContactPhone('My Title');

        $this->repository->add($fixture, true);

        self::assertSame($originalNumObjectsInRepository + 1, count($this->repository->findAll()));

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));
        $this->client->submitForm('Delete');

        self::assertSame($originalNumObjectsInRepository, count($this->repository->findAll()));
        self::assertResponseRedirects('/site/');
    }
}
