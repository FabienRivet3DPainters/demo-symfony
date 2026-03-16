<?php

namespace App\Tests;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class LoginControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $container    = static::getContainer();
        $em           = $container->get('doctrine.orm.entity_manager');

        // Nettoyage de la BDD de test
        foreach ($em->getRepository(User::class)->findAll() as $user) {
            $em->remove($user);
        }
        $em->flush();

        // Création d'un utilisateur de test
        /** @var UserPasswordHasherInterface $hasher */
        $hasher = $container->get('security.user_password_hasher');
        $user   = (new User())->setEmail('email@example.com');
        $user->setPassword($hasher->hashPassword($user, 'password'));
        $em->persist($user);
        $em->flush();
    }

    public function testLogin(): void
    {
        // Email inexistant → redirection vers /login
        $this->client->request('GET', '/login');
        self::assertResponseIsSuccessful();

        $this->client->submitForm('login', [
            '_username' => 'doesNotExist@example.com',
            '_password' => 'password',
        ]);
        self::assertResponseRedirects('/login');
        $this->client->followRedirect();
        self::assertSelectorTextContains('.alert', 'Invalid credentials.');

        // Mot de passe incorrect → redirection vers /login
        $this->client->request('GET', '/login');
        $this->client->submitForm('login', [
            '_username' => 'email@example.com',
            '_password' => 'bad-password',
        ]);
        self::assertResponseRedirects('/login');
        $this->client->followRedirect();
        self::assertSelectorTextContains('.alert', 'Invalid credentials.');

        // Credentials valides → redirection vers /admin
        $this->client->submitForm('login', [
            '_username' => 'email@example.com',
            '_password' => 'password',
        ]);
        self::assertResponseRedirects('/admin');
        $this->client->followRedirect();
        self::assertSelectorNotExists('.alert');
    }
}