<?php

use Hcode\Model\Address;
use Hcode\Model\Cart;
use Hcode\Page;
use Hcode\Model\Order;
use Hcode\Model\OrderStatus;
use Hcode\Model\User;

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
