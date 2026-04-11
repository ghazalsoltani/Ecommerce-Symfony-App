<?php

namespace App\Twig;

use App\Repository\CategoryRepository;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFilter;

/**
 * Twig extension used by EasyAdmin templates.
 *
 * Previously depended on Cart class (session-based cart from the Twig frontend).
 * Cart dependency has been removed since the frontend is now React and manages
 * its own cart via localStorage. The fullCartQuantity global is kept at 0
 * in case any remaining Twig template references it.
 */
class AppExtensions extends AbstractExtension implements GlobalsInterface
{
    private $categoryRepository;

    public function __construct(CategoryRepository $categoryRepository)
    {
        $this->categoryRepository = $categoryRepository;
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('price', [$this, 'formatPrice'])
        ];
    }

    public function formatPrice($number): string
    {
        return number_format($number, '2', ',') . ' €';
    }

    public function getGlobals(): array
    {
        return [
            'allCategories' => $this->categoryRepository->findAll(),
            'fullCartQuantity' => 0
        ];
    }
}