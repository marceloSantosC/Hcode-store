<?php

use Hcode\Model\Address;
use Hcode\Model\Cart;
use Hcode\Model\Product;
use Hcode\Page;
use Hcode\Model\Category;
use Hcode\Model\User;

$app->get('/', function () {
    $products = Product::listAll();
    $products = Product::checklist($products);

    $page = new Page();
    $page->setTpl("index", [
        "products" => Product::checklist($products)
    
    ]);
});

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

$app->post('/cart/freight', function () {
    $cart = Cart::getFromSession();
    $cart->setFreight($_POST['zipcode']);

    header('Location: /cart');
    exit;
});

$app->get('/checkout', function () {
    User::verifyLogin(false);

    $cart = Cart::getFromSession();
    $address = new Address();

    $page = new Page();
    $page->setTpl('checkout', [
        'cart' => $cart->getValues(),
        'address' => $address->getValues()
    ]);
});

$app->get('/login', function () {
    $page = new Page();
    $page->setTpl('login', [
        'error' => User::getMsgError(),
        'errorRegister' => User::getRegisterMsgError(),
        'registerValues' => isset($_SESSION['registerValues']) ? $_SESSION['registerValues'] : [
            'name' => '',
            'email' => '',
            'phone' => ''
        ]
    ]);
});

$app->post('/login', function () {
    try {
        User::login($_POST['login'], $_POST['password']);
    } catch (Exception $e) {
        User::setMsgError($e->getMessage());
    }

    header('Location: /cart');
    exit;
});

$app->get('/logout', function () {
    User::logout();
    header('Location: /login');
    exit;
});

$app->post('/register', function () {
    if (!isset($_POST['name']) || $_POST['name'] == '') {
        User::setRegisterMsgError('Por favor preencha o campo Nome Completo.');
        header('Location: /login');
        exit;
    }

    if (!isset($_POST['email']) || $_POST['email'] == '') {
        User::setRegisterMsgError('Por favor preencha o campo email.');
        header('Location: /login');
        exit;
    }

    if (!isset($_POST['password']) || $_POST['password'] == '') {
        User::setRegisterMsgError('Por favor preencha o campo senha.');
        header('Location: /login');
        exit;
    }

    if (User::checkLoginExists($_POST['login'])) {
        User::setRegisterMsgError('O e-mail informado já está sendo usado por outro usuário.');
        header('Location: /login');
        exit;
    }

    $_SESSION['registerValues'] = $_POST;

    $user = new User();
    $user->setData([
        'inadmin' => 0,
        'deslogin' => $_POST['email'],
        'desperson' => $_POST['name'],
        'desemail' => $_POST['email'],
        'despassword' => $_POST['password'],
        'nrphone' => $_POST['phone']
    ]);

    $user->save();
    
    User::login($_POST['email'], $_POST['password']);
    header('Location: /checkout');
    exit;
});

$app->get('/forgot', function () {
    $page = new Page();
    $page->setTpl("forgot");
});

$app->post('/forgot', function () {
    $sendSucess = User::getForgot($_POST['email'], false);
    
    if ($sendSucess) {
        header("Location: /forgot/sent");
        exit;
    } else {
        header("Location: /forgot");
        exit;
    }
});

$app->get('/forgot/sent', function () {
    $page = new Page();
    $page->setTpl("forgot-sent");
});

$app->get("/forgot/reset", function () {
    $user = User::validForgotDecrypt($_GET['code']);

    $page = new Page();
    $page->setTpl("forgot-reset", array(
        "name" => $user['desperson'],
        "code" => $_GET['code']
    ));
});

$app->post("/forgot/reset", function () {
    $forgot = User::validForgotDecrypt($_POST['code']);

    User::setForgotUsed($forgot['idrecovery']);

    $user = new User();
    $user->get((int)$forgot['iduser']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT, ["cost" => 12]);
    $user->setPassword($password);

    $page = new Page();

    $page->setTpl("forgot-reset-success");
});
