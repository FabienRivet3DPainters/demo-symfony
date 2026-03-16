<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Tests fonctionnels — Sécurisation API par JWT
 * Flux : 401 sans token → login → token → 200 avec token
 */
class ApiAuthTest extends WebTestCase
{
    /** Sans token → 401 : la route /api/products est bien protégée */
    public function testAccesSansTokenRetourne401(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/products');

        $this->assertResponseStatusCodeSame(401);
    }

    /** Login valide → 200 + token JWT dans la réponse */
    public function testLoginValideRetourneToken(): void
    {
        $client = static::createClient();
        $client->request(
            'POST',
            '/api/login_check',
            [], [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'username' => 'chezdijoux@gmail.com',
                'password' => 'Admin123456!',
            ])
        );

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('token', $data);
        $this->assertNotEmpty($data['token']);
    }

    /** Avec token valide → 200 + liste produits JSON */
    public function testAccesAvecTokenValideRetourne200(): void
    {
        $client = static::createClient();

        // Étape 1 : obtenir le token
        $client->request(
            'POST',
            '/api/login_check',
            [], [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'username' => 'chezdijoux@gmail.com',
                'password' => 'Admin123456!',
            ])
        );
        $token = json_decode($client->getResponse()->getContent(), true)['token'];

        // Étape 2 : appel authentifié
        $client->request(
            'GET', '/api/products',
            [], [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]
        );

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($data);
        $this->assertNotEmpty($data);
    }
}