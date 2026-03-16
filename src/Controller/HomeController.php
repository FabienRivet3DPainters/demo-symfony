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
        // Temporaire : désactiver la DB pour diagnostiquer
        $products = []; // $this->productRepository->findAll();
        $recommended = []; // !empty($products) ? $this->recommendationService->getRecommendations($products[0]) : [];

        return $this->render('home/index.html.twig', [
            'products' => $products,
            'recommended' => $recommended,
        ]);
    }
}