<?php

namespace App\Twig;

use App\Classe\Cart;
use App\Repository\CategoryRepository;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFilter;

class AppExtensions extends AbstractExtension implements GlobalsInterface
{
    private $categoryRepository;
    private $cart;
    private $requestStack;

    public function __construct(CategoryRepository $categoryRepository, Cart $cart, RequestStack $requestStack)
    {
        $this->categoryRepository = $categoryRepository;
        $this->cart = $cart;
        $this->requestStack = $requestStack;
    }

    public function getFilters()
    {
        return [
            new TwigFilter('price', [$this, 'formatPrice'])
        ];
    }

    public function formatPrice($number)
    {
        return number_format($number, '2', ',') . ' €';
    }

    public function getGlobals(): array
    {
        // Check if we're in an API context (stateless)
        $request = $this->requestStack->getCurrentRequest();
        $isApiRequest = $request && str_starts_with($request->getPathInfo(), '/api');

        return [
            'allCategories' => $this->categoryRepository->findAll(),
            // Only get cart quantity if not in API context
            'fullCartQuantity' => $isApiRequest ? 0 : $this->cart->fullQuantity()
        ];
    }
}