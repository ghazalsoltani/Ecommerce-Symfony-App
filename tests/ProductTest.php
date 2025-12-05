<?php

namespace App\Tests;

use App\Entity\Product;
use App\Entity\Category;
use PHPUnit\Framework\TestCase;

class ProductTest extends TestCase
{
    public function testGetPriceWtCalculation(): void
    {
        $product = new Product();
        $product->setPrice(100);
        $product->setTva(20);

        // Price with tax = 100 * (1 + 20/100) = 100 * 1.2 = 120
        $this->assertEquals(120, $product->getPriceWt());
    }

    public function testGetPriceWtWithZeroTva(): void
    {
        $product = new Product();
        $product->setPrice(50);
        $product->setTva(0);

        // Price with 0% tax = 50
        $this->assertEquals(50, $product->getPriceWt());
    }

    public function testProductSettersAndGetters(): void
    {
        $product = new Product();

        $product->setName('Test Product');
        $product->setSlug('test-product');
        $product->setDescription('A test product description');
        $product->setIllustration('test-image.jpg');
        $product->setPrice(99.99);
        $product->setTva(20);
        $product->setIsHomepage(true);

        $this->assertEquals('Test Product', $product->getName());
        $this->assertEquals('test-product', $product->getSlug());
        $this->assertEquals('A test product description', $product->getDescription());
        $this->assertEquals('test-image.jpg', $product->getIllustration());
        $this->assertEquals(99.99, $product->getPrice());
        $this->assertEquals(20, $product->getTva());
        $this->assertTrue($product->isHomepage());
    }

    public function testProductCategoryRelation(): void
    {
        $product = new Product();
        $category = new Category();
        $category->setName('Electronics');

        $product->setCategory($category);

        $this->assertSame($category, $product->getCategory());
        $this->assertEquals('Electronics', $product->getCategory()->getName());
    }
}