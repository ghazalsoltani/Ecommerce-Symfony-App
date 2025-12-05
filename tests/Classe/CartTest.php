<?php

namespace App\Tests\Classe;

use App\Classe\Cart;
use App\Entity\Product;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class CartTest extends TestCase
{
    private Cart $cart;
    private $sessionMock;
    private array $sessionData = [];

    protected function setUp(): void
    {
        // Create mock for SessionInterface
        $this->sessionMock = $this->createMock(SessionInterface::class);

        // Configure session mock to store and retrieve data
        $this->sessionMock->method('get')
            ->with('cart')
            ->willReturnCallback(function () {
                return $this->sessionData;
            });

        $this->sessionMock->method('set')
            ->with('cart', $this->anything())
            ->willReturnCallback(function ($key, $value) {
                $this->sessionData = $value;
            });

        $this->sessionMock->method('remove')
            ->with('cart')
            ->willReturnCallback(function () {
                $this->sessionData = [];
            });

        // Create mock for RequestStack
        $requestStackMock = $this->createMock(RequestStack::class);
        $requestStackMock->method('getSession')
            ->willReturn($this->sessionMock);

        $this->cart = new Cart($requestStackMock);
    }

    public function testFullQuantityReturnsZeroForEmptyCart(): void
    {
        $this->assertEquals(0, $this->cart->fullQuantity());
    }

    public function testGetTotalWtReturnsZeroForEmptyCart(): void
    {
        $this->assertEquals(0, $this->cart->getTotalWt());
    }

    public function testAddProductToCart(): void
    {
        $product = $this->createProductMock(1, 100, 20);

        $this->cart->add($product);

        $cart = $this->cart->getCart();
        $this->assertArrayHasKey(1, $cart);
        $this->assertEquals(1, $cart[1]['qty']);
    }

    public function testAddSameProductTwiceIncreasesQuantity(): void
    {
        $product = $this->createProductMock(1, 100, 20);

        $this->cart->add($product);
        $this->cart->add($product);

        $cart = $this->cart->getCart();
        $this->assertEquals(2, $cart[1]['qty']);
    }

    public function testDecreaseReducesQuantity(): void
    {
        $product = $this->createProductMock(1, 100, 20);

        $this->cart->add($product);
        $this->cart->add($product);
        $this->cart->decrease(1);

        $cart = $this->cart->getCart();
        $this->assertEquals(1, $cart[1]['qty']);
    }

    public function testDecreaseRemovesProductWhenQuantityIsOne(): void
    {
        $product = $this->createProductMock(1, 100, 20);

        $this->cart->add($product);
        $this->cart->decrease(1);

        $cart = $this->cart->getCart();
        $this->assertArrayNotHasKey(1, $cart);
    }

    private function createProductMock(int $id, float $price, float $tva): Product
    {
        $product = $this->createMock(Product::class);
        $product->method('getId')->willReturn($id);
        $product->method('getPrice')->willReturn($price);
        $product->method('getTva')->willReturn($tva);
        $product->method('getPriceWt')->willReturn($price * (1 + $tva / 100));

        return $product;
    }
}