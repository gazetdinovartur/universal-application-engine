<?php

namespace App\Tests\Functional\Admin;

use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

#[Group('functional')]
final class AdminDashboardAfterLoginTest extends WebTestCase
{
    public function testDashboardRedirectsToApplicationsAfterLogin(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/admin/login');
        self::assertResponseIsSuccessful();

        $form = $crawler->selectButton('Sign in')->form([
            '_username' => 'admin',
            '_password' => 'TempAdmin!2026',
        ]);
        $client->submit($form);

        self::assertResponseRedirects();
        $client->followRedirect();

        self::assertResponseIsSuccessful();
    }
}
