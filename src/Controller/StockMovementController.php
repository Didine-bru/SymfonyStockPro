<?php

namespace App\Controller;

use App\Entity\StockMovement;
use App\Form\StockMovementType;
use App\Repository\ProductRepository;
use App\Repository\StockMovementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/stock/movement')]
final class StockMovementController extends AbstractController
{
    #[Route(name: 'app_stock_movement_index', methods: ['GET'])]
    public function index(Request $request, StockMovementRepository $stockMovementRepository): Response
    {
        $search = $request->query->get('search');
        $type = $request->query->get('type');

        $stockMovements = $stockMovementRepository->findByFilters($search, $type);

        return $this->render('stock_movement/index.html.twig', [
            'stock_movements' => $stockMovements,
            'search' => $search,
            'type' => $type,
        ]);
    }

    #[Route('/new', name: 'app_stock_movement_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        ProductRepository $productRepository,
    ): Response {
        $stockMovement = new StockMovement();

        $productId = $request->query->get('product');

        if ($productId) {
            $product = $productRepository->find($productId);

            if ($product) {
                $stockMovement->setProduct($product);
            }
        }

        $form = $this->createForm(StockMovementType::class, $stockMovement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $product = $stockMovement->getProduct();
            $quantity = $stockMovement->getQuantity();

            if ($stockMovement->getType() === 'Sortie') {
                if ($quantity > $product->getQuantity()) {
                    $this->addFlash('error', 'Stock insuffisant. Stock disponible : ' . $product->getQuantity());
                } else {
                    $product->setQuantity($product->getQuantity() - $quantity);

                    $entityManager->persist($stockMovement);
                    $entityManager->flush();

                    $this->addFlash('success', 'Sortie de stock enregistrée avec succès.');

                    return $this->redirectToRoute('app_stock_movement_index', [], Response::HTTP_SEE_OTHER);
                }
            } elseif ($stockMovement->getType() === 'Entrée') {
                $product->setQuantity($product->getQuantity() + $quantity);

                $entityManager->persist($stockMovement);
                $entityManager->flush();

                $this->addFlash('success', 'Entrée de stock enregistrée avec succès.');

                return $this->redirectToRoute('app_stock_movement_index', [], Response::HTTP_SEE_OTHER);
            }
        }

        return $this->render('stock_movement/new.html.twig', [
            'stock_movement' => $stockMovement,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_stock_movement_show', methods: ['GET'])]
    public function show(StockMovement $stockMovement): Response
    {
        return $this->render('stock_movement/show.html.twig', [
            'stock_movement' => $stockMovement,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_stock_movement_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        StockMovement $stockMovement,
        EntityManagerInterface $entityManager,
    ): Response {
        // On mémorise les anciennes informations
        $oldProduct = $stockMovement->getProduct();
        $oldQuantity = $stockMovement->getQuantity();
        $oldType = $stockMovement->getType();

        $form = $this->createForm(StockMovementType::class, $stockMovement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // 1. Annuler l'ancien mouvement

            if ($oldType === 'Entrée') {
                $oldProduct->setQuantity($oldProduct->getQuantity() - $oldQuantity);
            } elseif ($oldType === 'Sortie') {
                $oldProduct->setQuantity($oldProduct->getQuantity() + $oldQuantity);
            }

            // 2. Récupérer les nouvelles informations

            $newProduct = $stockMovement->getProduct();
            $newQuantity = $stockMovement->getQuantity();
            $newType = $stockMovement->getType();

            // 3. Vérifier le nouveau mouvement

            if ($newType === 'Sortie') {
                if ($newQuantity > $newProduct->getQuantity()) {
                    // On remet l'ancien mouvement comme avant
                    if ($oldType === 'Entrée') {
                        $oldProduct->setQuantity($oldProduct->getQuantity() + $oldQuantity);
                    } elseif ($oldType === 'Sortie') {
                        $oldProduct->setQuantity($oldProduct->getQuantity() - $oldQuantity);
                    }

                    $this->addFlash('error', 'Stock insuffisant. Stock disponible : ' . $newProduct->getQuantity());

                    return $this->render('stock_movement/edit.html.twig', [
                        'stock_movement' => $stockMovement,
                        'form' => $form,
                    ]);
                }

                $newProduct->setQuantity($newProduct->getQuantity() - $newQuantity);
            } elseif ($newType === 'Entrée') {
                $newProduct->setQuantity($newProduct->getQuantity() + $newQuantity);
            }

            $entityManager->flush();

            $this->addFlash('success', 'Mouvement modifié et stock mis à jour avec succès.');

            return $this->redirectToRoute('app_stock_movement_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('stock_movement/edit.html.twig', [
            'stock_movement' => $stockMovement,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_stock_movement_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        StockMovement $stockMovement,
        EntityManagerInterface $entityManager,
    ): Response {
        if ($this->isCsrfTokenValid('delete' . $stockMovement->getId(), $request->getPayload()->getString('_token'))) {
            $product = $stockMovement->getProduct();
            $quantity = $stockMovement->getQuantity();

            if ($stockMovement->getType() === 'Entrée') {
                $product->setQuantity($product->getQuantity() - $quantity);
            } elseif ($stockMovement->getType() === 'Sortie') {
                $product->setQuantity($product->getQuantity() + $quantity);
            }

            $entityManager->remove($stockMovement);
            $entityManager->flush();

            $this->addFlash('success', 'Mouvement supprimé et stock mis à jour avec succès.');
        }

        return $this->redirectToRoute('app_stock_movement_index', [], Response::HTTP_SEE_OTHER);
    }
}
