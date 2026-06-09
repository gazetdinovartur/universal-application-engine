<?php

namespace App\Service;

use App\DTO\CreateApplicationRequest;
use App\Entity\Application;
use App\Enum\ApplicationStatus;
use Doctrine\ORM\EntityManagerInterface;

class ApplicationService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function create(CreateApplicationRequest $request): Application
    {
        // Sprint 2: полная логика создания заявки
        $application = new Application();
        $application->setStatus(ApplicationStatus::New);
        $application->setPayload($request->payload);

        $this->entityManager->persist($application);
        $this->entityManager->flush();

        return $application;
    }
}
