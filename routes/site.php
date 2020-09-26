<?php

use Hcode\Model\Address;
use Hcode\Model\Cart;
use Hcode\Model\Product;
use Hcode\Page;
use Hcode\Model\Category;
use Hcode\Model\Order;
use Hcode\Model\OrderStatus;
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

    $address = new Address();
    $cart = Cart::getFromSession();

    if (!isset($_GET['zipcode'])) {
        $_GET['zipcode'] = $cart->getdeszipcode();
    }

    if (isset($_GET['zipcode'])) {
        $address->loadFromCEP($_GET['zipcode']);

        $cart->setdeszipcode($_GET['zipcode']);
        $cart->save();
        $cart->calculateTotal();
    }

    $fields = [
        'idaddress' => '',
        'idperson' => '',
        'desaddress' => '',
        'descomplement' => '',
        'descity' => '',
        'desstate' => '',
        'descountry' => '',
        'desnrzipcode' => '',
        'desdistrict' => ''
    ];

    $address->setData(array_merge($fields, $address->getValues()));

    $page = new Page();
    $page->setTpl('checkout', [
        'cart' => $cart->getValues(),
        'address' => $address->getValues(),
        'products' => $cart->getProducts(),
        'error' => $address::getMsgError()
    ]);
});

$app->post('/checkout', function () {
    User::verifyLogin(false);

    if (isset($_POST['zipcode']) && $_POST['zipcode'] === '') {
        Address::setMsgError('O campo CEP deve ser preenchido.');

        header('Location: /checkout');
        exit;
    }

    
    if (isset($_POST['desaddress']) && $_POST['desaddress'] === '') {
        Address::setMsgError('O campo endereço deve ser preenchido.');

        header('Location: /checkout');
        exit;
    }

    if (isset($_POST['desdistrict']) && $_POST['desdistrict'] === '') {
        Address::setMsgError('O campo bairro deve ser preenchido.');

        header('Location: /checkout');
        exit;
    }

    if (isset($_POST['desdistrict']) && $_POST['desdistrict'] === '') {
        Address::setMsgError('O campo bairro deve ser preenchido.');

        header('Location: /checkout');
        exit;
    }

    if (isset($_POST['descity']) && $_POST['descity'] === '') {
        Address::setMsgError('O campo cidade deve ser preenchido.');

        header('Location: /checkout');
        exit;
    }
    
    if (isset($_POST['desstate']) && $_POST['desstate'] === '') {
        Address::setMsgError('O campo estado deve ser preenchido.');

        header('Location: /checkout');
        exit;
    }

    if (isset($_POST['descountry']) && $_POST['descountry'] === '') {
        Address::setMsgError('O campo pais deve ser preenchido.');

        header('Location: /checkout');
        exit;
    }

    $user = User::getFromSession();

    $address = new Address();
    $_POST['deszipcode'] = $_POST['zipcode'];
    $_POST['idperson'] = $user->getidperson();

    $address->setData($_POST);
    $address->save();

    $cart = Cart::getFromSession();
    $total = $cart->calculateTotal();

    $order = new Order();
    $order->setData([
        'idcart' => $cart->getidcart(),
        'idaddress' => $address->getidaddress(),
        'iduser' => $user->getiduser(),
        'idstatus' => OrderStatus::EM_ABERTO,
        'vltotal' => $cart->getvltotal()
    ]);
    $order->save();

    header('Location: /order/' . $order->getidorder());
    exit;
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
        header('Location: /login');
        exit;
    }

    header('Location: /profile');
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
    $password = $_POST['password'];
    $user->setdespassword($password);
    $user->update();

    $page = new Page();

    $page->setTpl("forgot-reset-success");
});


$app->get("/profile", function () {
    User::verifyLogin(false);
    $user = User::getFromSession();

    $page = new Page();
    $page->setTpl('profile', [
        'user' => $user->getValues(),
        'profileMsg' => User::getSucess(),
        'profileError' => User::getMsgError()
    ]);
});

$app->post("/profile", function () {
    User::verifyLogin(false);

    if (!isset($_POST['desperson']) || $_POST['desperson'] === '') {
        User::setMsgError('Preencha seu nome.');
        header("Location: /profile");
        exit;
    }

    if (!isset($_POST['desemail']) || $_POST['desemail'] === '') {
        User::setMsgError('Preencha o campo e-mail.');
        header("Location: /profile");
        exit;
    }

    $user = User::getFromSession();

    if ($_POST['desemail'] !== $user->getdesemail()) {
        if (User::checkLoginExists($_POST['desemail']) != $user->getdesemail()) {
            User::setMsgError('Este endereço de e-mail já está cadastrado.');
            header("Location: /profile");
            exit;
        }
    }

    $_POST['inadmin'] = $user->getinadmin();
    $_POST['despassword'] = $user->getdespassword();
    $_POST['deslogin'] = $_POST['desemail'];

    $user->setData($_POST);
    $user->update();

    User::setSucess('Dados alterados com sucesso!');
    
    header("Location: /profile");
    exit;
});

$app->get('/order/:id', function ($idorder) {
    User::verifyLogin(false);

    $order = new Order();
    $order->get((int)$idorder);

    $page = new Page();
    $page->setTpl('payment', ['order' => $order->getValues()]);
});

$app->get('/boleto/:idorder', function ($idorder) {
    User::verifyLogin(false);

    $order = new Order();
    $order->get($idorder);

    // DADOS DO BOLETO PARA O SEU CLIENTE
    $dias_de_prazo_para_pagamento = 10;
    $taxa_boleto = 5.00;
    $data_venc = date("d/m/Y", time() + ($dias_de_prazo_para_pagamento * 86400));
    $valor_cobrado = $order->getvltotal();
    $valor_cobrado = str_replace(",", ".", $valor_cobrado);
    $valor_boleto = number_format($valor_cobrado + $taxa_boleto, 2, ',', '');

    $dadosboleto["nosso_numero"] = $order->getidorder();  // Nosso numero - REGRA: Máximo de 8 caracteres!
    $dadosboleto["numero_documento"] = $order->getidorder();// Num do pedido ou nosso numero
    $dadosboleto["data_vencimento"] = $data_venc; // Data de Vencimento do Boleto - REGRA: Formato DD/MM/AAAA
    $dadosboleto["data_documento"] = date("d/m/Y"); // Data de emissão do Boleto
    $dadosboleto["data_processamento"] = date("d/m/Y"); // Data de processamento do boleto (opcional)
    $dadosboleto["valor_boleto"] = $valor_boleto;
    // DADOS DO SEU CLIENTE
    $dadosboleto["sacado"] = $order->getdesperson();
    $dadosboleto["endereco1"] = $order->getdesaddress() . ',' . $order->getdesdistrict();
     
    $dadosboleto["endereco2"] = $order->getdescity() . ' - ' . $order->getdesstate() . " - CEP:" . $order->getdescep();

    // INFORMACOES PARA O CLIENTE
    $dadosboleto["demonstrativo1"] = "Pagamento de Compra na Loja Hcode E-commerce";
    $dadosboleto["demonstrativo2"] = "Taxa bancária - R$ 0,00";
    $dadosboleto["demonstrativo3"] = "";
    $dadosboleto["instrucoes1"] = "- Sr. Caixa, cobrar multa de 2% após o vencimento";
    $dadosboleto["instrucoes2"] = "- Receber até 10 dias após o vencimento";
    $dadosboleto["instrucoes3"] = "- Em caso de dúvidas entre em contato conosco: suporte@hcode.com.br";
    $dadosboleto["instrucoes4"] = "&nbsp; Emitido pelo sistema Projeto Loja Hcode E-commerce - www.hcode.com.br";

    // DADOS OPCIONAIS DE ACORDO COM O BANCO OU CLIENTE
    $dadosboleto["quantidade"] = "";
    $dadosboleto["valor_unitario"] = "";
    $dadosboleto["aceite"] = "";
    $dadosboleto["especie"] = "R$";
    $dadosboleto["especie_doc"] = "";


    // ---------------------- DADOS FIXOS DE CONFIGURAÇÃO DO SEU BOLETO --------------- //


    // DADOS DA SUA CONTA - ITAÚ
    $dadosboleto["agencia"] = "1690"; // Num da agencia, sem digito
    $dadosboleto["conta"] = "48781"; // Num da conta, sem digito
    $dadosboleto["conta_dv"] = "2"; // Digito do Num da conta

    // DADOS PERSONALIZADOS - ITAÚ
    $dadosboleto["carteira"] = "175";  // Código da Carteira: pode ser 175, 174, 104, 109, 178, ou 157

    // SEUS DADOS
    $dadosboleto["identificacao"] = "Hcode Treinamentos";
    $dadosboleto["cpf_cnpj"] = "24.700.731/0001-08";
    $dadosboleto["endereco"] = "Rua Ademar Saraiva Leão, 234 - Alvarenga, 09853-120";
    $dadosboleto["cidade_uf"] = "São Bernardo do Campo - SP";
    $dadosboleto["cedente"] = "HCODE TREINAMENTOS LTDA - ME";

    // NÃO ALTERAR!
    $path = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'res' . DIRECTORY_SEPARATOR . 'boletophp' .
        DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR;
    require_once($path . 'funcoes_itau.php');
    require_once($path . 'layout_itau.php');
});

$app->get('/profile/orders', function () {
    User::verifyLogin(false);

    $user = User::getFromSession();

    $page = new Page();
    $page->setTpl('profile-orders', [
        'orders' => $user->getOrders()
    ]);
});

$app->get('/profile/orders/:idorder', function ($idorder) {
    User::verifyLogin(false);

    $order = new Order();
    $order->get((int)$idorder);

    $cart = new Cart();
    $cart->get((int)$order->getidcart());
    $cart->calculateTotal();

    $page = new Page();
    $page->setTpl('profile-orders-detail', [
        'order' => $order->getValues(),
        'cart' => $cart->getValues(),
        'products' => $cart->getProducts()
    ]);
});
