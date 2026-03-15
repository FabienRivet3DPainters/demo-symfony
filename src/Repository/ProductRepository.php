<?php
namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    public function findPaginated(int $page, int $limit): array
    {
        return $this->createQueryBuilder('p')
            ->orderBy('p.id', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findByCategory(string $category, int $excludeId, int $limit = 4): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.category = :category')
            ->andWhere('p.id != :excludeId')
            ->andWhere('p.stock > 0')        // ← ajout : pas de rupture
            ->setParameter('category', $category)
            ->setParameter('excludeId', $excludeId)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    // Fallback quand pas assez de produits dans la même catégorie
    public function findRecent(int $limit = 4, array $excludeIds = []): array
    {
        $qb = $this->createQueryBuilder('p')
            ->where('p.stock > 0')
            ->orderBy('p.id', 'DESC')
            ->setMaxResults($limit);

        if (!empty($excludeIds)) {
            $qb->andWhere('p.id NOT IN (:excludeIds)')
               ->setParameter('excludeIds', $excludeIds);
        }

        return $qb->getQuery()->getResult();
    }

    // Pour la homepage — produits mis en avant
    public function findFeatured(int $limit = 8): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.stock > 0')
            ->orderBy('p.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}