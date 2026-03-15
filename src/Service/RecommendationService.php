<?php
namespace App\Service;

use App\Entity\Product;
use App\Entity\User;
use App\Repository\ProductRepository;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class RecommendationService
{
    public function __construct(
        private ProductRepository $productRepository,
        private CacheInterface    $cache
    ) {}

    public function getRecommendations(Product $product, ?User $user = null): array
    {
        // Clé différenciée : anonyme vs utilisateur connecté
        $cacheKey = 'recommendations_' . $product->getId()
            . '_' . ($user?->getId() ?? 'anonymous');

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($product, $user) {
            $item->expiresAfter(300);

            // Règle 1 : produits de même catégorie, hors produit actuel
            $candidates = $this->productRepository->findByCategory(
                $product->getCategory(),
                $product->getId(),
                limit: 8
            );

            // Règle 2 : exclure les produits déjà achetés (évolutif)
            if ($user !== null) {
                $purchasedIds = $this->getPurchasedIds($user);
                $candidates = array_values(array_filter(
                    $candidates,
                    fn(Product $p) => !in_array($p->getId(), $purchasedIds)
                ));
            }

            // Règle 3 : limiter à 4 résultats
            $candidates = array_slice($candidates, 0, 4);

            // Règle 4 : fallback si pas assez de produits dans la catégorie
            if (count($candidates) < 4) {
                $excludeIds = array_map(fn(Product $p) => $p->getId(), $candidates);
                $excludeIds[] = $product->getId();

                $recent = $this->productRepository->findRecent(
                    limit: 4 - count($candidates),
                    excludeIds: $excludeIds
                );
                $candidates = array_merge($candidates, $recent);
            }

            return $candidates;
        });
    }

    // Prêt pour quand tu ajouteras la table Order
    private function getPurchasedIds(User $user): array
    {
        return [];
    }
}