<?php

namespace App\Controller;

use App\Entity\Product;
use App\Form\ProductType;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use App\Repository\StockMovementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/product')]
final class ProductController extends AbstractController
{
    #[Route('/', name: 'app_product_index', methods: ['GET'])]
    public function index(
        Request $request,
        ProductRepository $productRepository,
        CategoryRepository $categoryRepository,
    ): Response {
        $search = $request->query->get('search');
        $categoryId = $request->query->get('category');

        $category = null;

        if ($categoryId) {
            $category = $categoryRepository->find($categoryId);
        }

        $products = $productRepository->findByFilters($search, $category);

        return $this->render('product/index.html.twig', [
            'products' => $products,
            'search' => $search,
            'categories' => $categoryRepository->findBy([], ['name' => 'ASC']),
            'selectedCategory' => $categoryId,
        ]);
    }

    #[Route('/new', name: 'app_product_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($product);
            $entityManager->flush();

            return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('product/new.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_product_show', methods: ['GET'])]
    public function show(Product $product, StockMovementRepository $stockMovementRepository): Response
    {
        $movements = $stockMovementRepository->findByProduct($product);

        $stock = $product->getStockInitial();
        $stockAfterMovements = [];

        foreach ($movements as $movement) {
            if ($movement->getType() === 'Entrée') {
                $stock += $movement->getQuantity();
            } elseif ($movement->getType() === 'Sortie') {
                $stock -= $movement->getQuantity();
            }

            $stockAfterMovements[$movement->getId()] = $stock;
        }

        return $this->render('product/show.html.twig', [
            'product' => $product,
            'movements' => $movements,
            'stockAfterMovements' => $stockAfterMovements,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_product_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Product $product, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $product->setUpdatedAt(new \DateTimeImmutable());

            $entityManager->flush();

            return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('product/edit.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_product_delete', methods: ['POST'])]
    public function delete(Request $request, Product $product, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $product->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($product);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
    }
}
