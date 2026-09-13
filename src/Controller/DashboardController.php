<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use App\Repository\StockMovementRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    #[Route('/dashboard', name: 'app_dashboard')]
    #[Route('/dashboard', name: 'app_dashboard')]
    #[Route('/dashboard', name: 'app_dashboard')]
    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(
        ProductRepository $productRepository,
        CategoryRepository $categoryRepository,
        StockMovementRepository $stockMovementRepository,
    ): Response {
        $totalProducts = $productRepository->count([]);
        $totalCategories = $categoryRepository->count([]);
        $totalMovements = $stockMovementRepository->count([]);
        $lowStockProducts = $productRepository->countLowStockProducts();
        $lowStockProductList = $productRepository->findLowStockProducts();
        $totalStockValue = $productRepository->calculateTotalStockValue();

        return $this->render('dashboard/index.html.twig', [
            'totalProducts' => $totalProducts,
            'totalCategories' => $totalCategories,
            'totalMovements' => $totalMovements,
            'lowStockProducts' => $lowStockProducts,
            'lowStockProductList' => $lowStockProductList,
            'totalStockValue' => $totalStockValue,
        ]);
    }
}
