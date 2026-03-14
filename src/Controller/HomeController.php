<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use App\Service\RecommendationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    public function __construct(
        private ProductRepository $productRepository,
        private RecommendationService $recommendationService
    ) {}

    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        // On affiche seulement 4 produits en vedette sur la page d'accueil
        // Le catalogue complet reste accessible via /products
        $products = $this->productRepository->findBy([], ['id' => 'DESC'], 4);

        return $this->render('home/index.html.twig', [
            'products' => $products,
        ]);
    }
}