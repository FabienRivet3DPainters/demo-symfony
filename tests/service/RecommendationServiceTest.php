<?php

namespace App\Tests\Unit\Service;

use App\Entity\Product;
use App\Entity\User;
use App\Repository\ProductRepository;
use App\Service\RecommendationService;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class RecommendationServiceTest extends TestCase
{
    // ── HELPER : crée un produit sans BDD via réflexion ──────────────
    private function makeProduct(int $id, string $category = 'masques'): Product
    {
        $product = new Product();
        $product->setCategory($category);

        $ref = new \ReflectionClass($product);
        $prop = $ref->getProperty('id');
        $prop->setAccessible(true);
        $prop->setValue($product, $id);

        return $product;
    }

    // ── HELPER : cache qui exécute toujours le callback ──────────────
    private function makeCacheThatExecutesCallback(): CacheInterface
    {
        $cache = $this->createMock(CacheInterface::class);
        $cache->method('get')
            ->willReturnCallback(function (string $key, callable $callback) {
                $item = $this->createMock(ItemInterface::class);
                $item->expects($this->once())
                     ->method('expiresAfter')
                     ->with(300);
                return $callback($item);
            });

        return $cache;
    }

    // ── RÈGLE 1 : retourne des produits de même catégorie ────────────
    public function testReturnsSameCategoryProducts(): void
    {
        $current = $this->makeProduct(1, 'masques');
        $rec1    = $this->makeProduct(2, 'masques');
        $rec2    = $this->makeProduct(3, 'masques');

        $repo = $this->createMock(ProductRepository::class);
        $repo->expects($this->once())
             ->method('findByCategory')
             ->with('masques', 1, limit: 8)
             ->willReturn([$rec1, $rec2]);

        // Pas assez pour déclencher findRecent (2 < 4 mais on teste juste la catégorie)
        $repo->method('findRecent')->willReturn([]);

        $service = new RecommendationService($repo, $this->makeCacheThatExecutesCallback());
        $result  = $service->getRecommendations($current);

        $this->assertContains($rec1, $result);
        $this->assertContains($rec2, $result);
    }

    // ── RÈGLE 2 : ne dépasse pas 4 résultats ─────────────────────────
    public function testReturnsMaxFourProducts(): void
    {
        $current = $this->makeProduct(1, 'masques');

        // Le repo retourne 8 produits
        $manyProducts = array_map(
            fn(int $i) => $this->makeProduct($i, 'masques'),
            range(2, 9)
        );

        $repo = $this->createMock(ProductRepository::class);
        $repo->method('findByCategory')->willReturn($manyProducts);
        $repo->method('findRecent')->willReturn([]);

        $service = new RecommendationService($repo, $this->makeCacheThatExecutesCallback());
        $result  = $service->getRecommendations($current);

        // Le service doit limiter à 4 même si le repo en retourne 8
        $this->assertCount(4, $result);
    }

    // ── RÈGLE 3 : fallback si pas assez dans la catégorie ────────────
    public function testFallsBackToRecentWhenNotEnoughInCategory(): void
    {
        $current = $this->makeProduct(1, 'potions');

        // Seulement 1 produit dans la catégorie
        $categoryProduct = $this->makeProduct(2, 'potions');
        $recentProduct1  = $this->makeProduct(10, 'masques');
        $recentProduct2  = $this->makeProduct(11, 'costumes');
        $recentProduct3  = $this->makeProduct(12, 'jeux');

        $repo = $this->createMock(ProductRepository::class);
        $repo->method('findByCategory')
             ->willReturn([$categoryProduct]);

        // findRecent doit être appelé pour compléter jusqu'à 4
        $repo->expects($this->once())
             ->method('findRecent')
             ->with(
                 limit: 3, // 4 - 1 produit déjà trouvé
                 excludeIds: $this->arrayHasKey(0)
             )
             ->willReturn([$recentProduct1, $recentProduct2, $recentProduct3]);

        $service = new RecommendationService($repo, $this->makeCacheThatExecutesCallback());
        $result  = $service->getRecommendations($current);

        $this->assertCount(4, $result);
        $this->assertContains($categoryProduct, $result);
        $this->assertContains($recentProduct1, $result);
    }

    // ── RÈGLE 4 : retourne tableau vide si rien en BDD ───────────────
    public function testReturnsEmptyArrayWhenNothingFound(): void
    {
        $current = $this->makeProduct(1, 'potions');

        $repo = $this->createMock(ProductRepository::class);
        $repo->method('findByCategory')->willReturn([]);
        $repo->method('findRecent')->willReturn([]);

        $service = new RecommendationService($repo, $this->makeCacheThatExecutesCallback());
        $result  = $service->getRecommendations($current);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    // ── CACHE : clé contient l'id du produit ─────────────────────────
    public function testCacheKeyContainsProductId(): void
    {
        $current = $this->makeProduct(42, 'costumes');

        $repo = $this->createMock(ProductRepository::class);
        $repo->method('findByCategory')->willReturn([]);
        $repo->method('findRecent')->willReturn([]);

        $cache = $this->createMock(CacheInterface::class);
        $cache->expects($this->once())
              ->method('get')
              ->with(
                  $this->stringContains('recommendations_42'),
                  $this->isCallable()
              )
              ->willReturn([]);

        $service = new RecommendationService($repo, $cache);
        $service->getRecommendations($current);
    }

    // ── CACHE : clé différente selon user connecté ou anonyme ────────
    public function testCacheKeyDiffersBetweenUserAndAnonymous(): void
    {
        $current = $this->makeProduct(1, 'masques');

        $repo = $this->createMock(ProductRepository::class);
        $repo->method('findByCategory')->willReturn([]);
        $repo->method('findRecent')->willReturn([]);

        $capturedKeys = [];

        $cache = $this->createMock(CacheInterface::class);
        $cache->method('get')
              ->willReturnCallback(function (string $key, callable $callback) use (&$capturedKeys) {
                  $capturedKeys[] = $key;
                  $item = $this->createMock(ItemInterface::class);
                  $item->method('expiresAfter');
                  return $callback($item);
              });

        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(99);

        $service = new RecommendationService($repo, $cache);

        // Appel anonyme
        $service->getRecommendations($current, null);
        // Appel avec user connecté
        $service->getRecommendations($current, $user);

        // Les deux clés doivent être différentes
        $this->assertNotSame($capturedKeys[0], $capturedKeys[1]);
        $this->assertStringContainsString('anonymous', $capturedKeys[0]);
        $this->assertStringContainsString('99', $capturedKeys[1]);
    }
}