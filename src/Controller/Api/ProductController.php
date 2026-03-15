<?php

namespace App\Controller\Api;

use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/products', name: 'api_products_')]
class ProductController extends AbstractController
{
    // GET /api/products → 401 sans token, 200 avec token
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(ProductRepository $repository): JsonResponse
    {
        $products = $repository->findAll();

        $data = array_map(fn($p) => [
            'id'          => $p->getId(),
            'name'        => $p->getName(),
            'description' => $p->getDescription(),
            'price'       => $p->getPrice(),
            'category'    => $p->getCategory(),
            'stock'       => $p->getStock(),
        ], $products);

        return $this->json($data);
    }
}