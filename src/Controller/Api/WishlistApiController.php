<?php

namespace App\Controller\Api;

use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
class WishlistApiController extends AbstractController
{
    #[Route('/wishlist', name: 'api_wishlist_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user) {
            return new JsonResponse(['error' => 'User is not authenticated'], 401);
        }

        $wishlist = $user->getWishlists();
        $products = [];

        foreach ($wishlist as $product) {
            $products[] = [
                'id' => $product->getId(),
                'name' => $product->getName(),
                'slug' => $product->getSlug(),
                'price' => $product->getPrice(),
                'tva' => $product->getTva(),
                'illustration' => $product->getIllustration(),
                'category' => [
                    'id' => $product->getCategory()->getId(),
                    'name' => $product->getCategory()->getName(),
                    'slug' => $product->getCategory()->getSlug(),
                ],
            ];
        }

        return new JsonResponse($products);
    }

    #[Route('/wishlist/add/{id}', name: 'api_wishlist_add', methods: ['POST'])]
    public function add(
        ProductRepository      $productRepository,
        EntityManagerInterface $entityManager,
        int                    $id
    ): JsonResponse
    {
        $user = $this->getUser();

        if (!$user) {
            return new JsonResponse(['error' => 'Non authentifié'], 401);
        }

        $product = $productRepository->find($id);

        if (!$product) {
            return new JsonResponse(['error' => 'Produit non trouvé'], 404);
        }

        if ($user->getWishlists()->contains($product)) {
            return new JsonResponse([
                'success' => true,
                'message' => 'Produit déjà dans la liste de souhaits',
                'inWishlist' => true
            ]);
        }

        $user->addWishlist($product);
        $entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Produit ajouté à la liste de souhaits',
            'inWishlist' => true
        ]);
    }

    #[Route('/wishlist/remove/{id}', name: 'api_wishlist_remove', methods: ['DELETE'])]
    public function remove(
        ProductRepository      $productRepository,
        EntityManagerInterface $entityManager,
        int                    $id
    ): JsonResponse
    {
        $user = $this->getUser();

        if (!$user) {
            return new JsonResponse(['error' => 'Non authentifié'], 401);
        }

        $product = $productRepository->find($id);

        if (!$product) {
            return new JsonResponse(['error' => 'Produit non trouvé'], 404);
        }

        $user->removeWishlist($product);
        $entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Produit supprimé de la liste de souhaits',
            'inWishlist' => false
        ]);
    }

    #[Route('/wishlist/check/{id}', name: 'api_wishlist_check', methods: ['GET'])]
    public function check(ProductRepository $productRepository, int $id): JsonResponse
    {
        $user = $this->getUser();

        if (!$user) {
            return new JsonResponse(['inWishlist' => false]);
        }

        $product = $productRepository->find($id);

        if (!$product) {
            return new JsonResponse(['error' => 'Produit non trouvé'], 404);
        }

        $inWishlist = $user->getWishlists()->contains($product);

        return new JsonResponse(['inWishlist' => $inWishlist]);
    }
}