<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class HomeControllerTest extends WebTestCase
{
    public function testHomePageReturns200(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);
    }

    public function testHomePageContainsExpectedContent(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        // Check that the page contains some expected element
        $this->assertSelectorExists('body');
    }

    public function testCartPageReturns200(): void
    {
        $client = static::createClient();
        $client->request('GET', '/mon-panier');

        $this->assertResponseIsSuccessful();
    }

    public function testLoginPageReturns200(): void
    {
        $client = static::createClient();
        $client->request('GET', '/connexion');

        $this->assertResponseIsSuccessful();
    }

    public function testRegisterPageReturns200(): void
    {
        $client = static::createClient();
        $client->request('GET', '/inscription');

        $this->assertResponseIsSuccessful();
    }

    public function testNonExistentPageReturns404(): void
    {
        $client = static::createClient();
        $client->request('GET', '/page-qui-nexiste-pas');

        $this->assertResponseStatusCodeSame(404);
    }
}