<?php

use Hcode\Model\Cart;
use Hcode\Model\Product;
use Hcode\Page;
use Hcode\Model\Category;

$app->get('/categories/:idcategory', function ($idcategory) {
    $category = new Category();
    $category->get((int)$idcategory);

    $currentPage = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;
    $pagination = $category->getProductsPage($currentPage);

    $pages = [];
    for ($i = 1; $i <= $pagination['pages']; $i++) {
        array_push($pages, [
            'link' => "/categories/$idcategory?page=$i",
            'page' => $i
        ]);
    }

    $page = new Page();
    $page->setTpl("category", [
        "category" => $category->getValues(),
        "products" => $pagination['data'],
        "pages" => $pages
    ]);
});

$app->get('/products/:desurl', function ($desurl) {
    $product = new Product();
    $product->getFromURL($desurl);

    $page = new Page();
    $page->setTpl("product-detail", [
        "product" => $product->getValues(),
        "categories" => $product->getCategories()
    ]);
});

$app->get('/cart', function () {
    $cart = Cart::getFromSession();

    $page = new Page();

    $page->setTpl("cart", [
        'cart' => $cart->getValues(),
        'products' => $cart->getProducts(),
        'error' => Cart::getMsgError()
    ]);
});

$app->get('/cart/:idproduct/add', function ($idproduct) {
    $product = new Product();
    $product->get((int)$idproduct);

    $cart = Cart::getFromSession();

    $qtd = (isset($_GET['qtd'])) ? (int)$_GET['qtd'] : 1;
    for ($i = 0; $i < $qtd; $i++) {
        $cart->addProduct($product);
    }

    header('Location: /cart');
    exit;
});

$app->get('/cart/:idproduct/minus', function ($idproduct) {
    $product = new Product();
    $product->get((int)$idproduct);

    $cart = Cart::getFromSession();
    $cart->removeProduct($product);

    header('Location: /cart');
    exit;
});

$app->get('/cart/:idproduct/remove', function ($idproduct) {
    $product = new Product();
    $product->get((int)$idproduct);

    $cart = Cart::getFromSession();
    $cart->removeProduct($product, true);

    header('Location: /cart');
    exit;
});

$app->get('/products', function () {
    $products = Product::listAll();

    $page = new Page();
    $page->setTpl("products", [
        "products" => Product::checklist($products)
    ]);
});
