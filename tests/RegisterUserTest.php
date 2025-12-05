<?php
namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RegisterUserTest extends WebTestCase
{
    public function testSomething(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/inscription');

        // Use unique email to avoid duplicate error
        $uniqueEmail = 'test_' . time() . '@exemple.fr';
        
        $form = $crawler->selectButton('valider')->form([
            'register_user[email]' => $uniqueEmail,
            'register_user[plainPassword][first]' => '123456',
            'register_user[plainPassword][second]' => '123456',
            'register_user[firstname]' => 'Test',
            'register_user[lastname]' => 'User',
        ]);
        $client->submit($form);

        $this->assertResponseRedirects('/connexion');
        $client->followRedirect();
        $this->assertSelectorExists('div.alert');
    }
}