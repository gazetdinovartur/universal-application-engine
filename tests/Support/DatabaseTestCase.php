<?php

namespace App\Tests\Support;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class DatabaseTestCase extends KernelTestCase
{
    protected EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = static::getContainer()->get('doctrine')->getManager();
        $this->resetDatabase();
        $this->registerDefaultYookassaStub();
    }

    protected function registerDefaultYookassaStub(): void
    {
        $yookassa = $this->createStub(\App\Infrastructure\Yookassa\YookassaClient::class);
        static::getContainer()->set(\App\Infrastructure\Yookassa\YookassaClient::class, $yookassa);
    }

    protected function resetDatabase(): void
    {
        $schemaTool = new SchemaTool($this->entityManager);
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
        self::ensureKernelShutdown();
    }
}
