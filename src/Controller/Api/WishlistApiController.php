<?php

namespace App\Controller\Api;

use App\Repository\ProductRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/wishlist')]
class WishlistApiController extends AbstractController
{
    /**
     * GET /api/wishlist
     *
     * Returns all products in the user's wishlist.
     * Uses a single query with JOIN FETCH to avoid N+1 on categories.
     */
    #[Route('', name: 'api_wishlist_index', methods: ['GET'])]
    public function index(UserRepository $userRepository): JsonResponse
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $userWithWishlist = $userRepository->findWithWishlistAndCategories($user->getId());

        if (!$userWithWishlist) {
            return $this->json([]);
        }

        $products = [];
        foreach ($userWithWishlist->getWishlists() as $product) {
            $category = $product->getCategory();
            $products[] = [
                'id' => $product->getId(),
                'name' => $product->getName(),
                'slug' => $product->getSlug(),
                'price' => $product->getPrice(),
                'tva' => $product->getTva(),
                'illustration' => $product->getIllustration(),
                'category' => $category ? [
                    'id' => $category->getId(),
                    'name' => $category->getName(),
                    'slug' => $category->getSlug(),
                ] : null,
            ];
        }

        return $this->json($products);
    }

    /**
     * POST /api/wishlist/add/{id}
     *
     * Idempotent - adding a product already in the wishlist returns 200.
     */
    #[Route('/add/{id}', name: 'api_wishlist_add', methods: ['POST'])]
    public function add(
        ProductRepository      $productRepository,
        EntityManagerInterface $entityManager,
        int                    $id,
    ): JsonResponse {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $product = $productRepository->find($id);
        if (!$product) {
            return $this->json(['error' => 'Product not found'], 404);
        }

        if (!$user->getWishlists()->contains($product)) {
            $user->addWishlist($product);
            $entityManager->flush();
        }

        return $this->json([
            'success' => true,
            'inWishlist' => true,
        ]);
    }

    /**
     * DELETE /api/wishlist/remove/{id}
     *
     * Idempotent - removing a product not in the wishlist returns 200.
     */
    #[Route('/remove/{id}', name: 'api_wishlist_remove', methods: ['DELETE'])]
    public function remove(
        ProductRepository      $productRepository,
        EntityManagerInterface $entityManager,
        int                    $id,
    ): JsonResponse {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $product = $productRepository->find($id);
        if (!$product) {
            return $this->json(['error' => 'Product not found'], 404);
        }

        $user->removeWishlist($product);
        $entityManager->flush();

        return $this->json([
            'success' => true,
            'inWishlist' => false,
        ]);
    }
}