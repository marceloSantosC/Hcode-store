<?php

use Hcode\Model\Order;
use Hcode\Model\OrderStatus;
use Hcode\Model\User;
use Hcode\PageAdmin;

$app->get('/admin/orders/:idorder/status', function ($idorder) {
    User::verifyLogin();

    $order = new Order();
    $order->get((int)$idorder);

    $page = new PageAdmin();
    $page->setTpl('order-status', [
        'order' => $order->getValues(),
        'status' => OrderStatus::listAll(),
        'msgSuccess' => Order::getSucess(),
        'msgError' => Order::getmsgError()
    ]);
});

$app->post('/admin/orders/:idorder/status', function ($idorder) {
    User::verifyLogin();

    if (!isset($_POST['idstatus']) || !(int)$_POST['idstatus'] > 0) {
        Order::setMsgError('O campo status está em branco.');
        
        header("Location: /admin/orders/$idorder/status");
        exit;
    }

    $order = new Order();
    $order->get((int)$idorder);

    $order->setidstatus((int)$_POST['idstatus']);
    $order->save();

    Order::setSucess('Status atualizado!');
    header("Location: /admin/orders/$idorder/status");
    exit;
});

$app->get('/admin/orders/:idorder/delete', function ($idorder) {
    User::verifyLogin();

    $order = new Order();
    $order->get((int)$idorder);

    $order->delete($idorder);

    header('Location: /admin/orders');
    exit;
});

$app->get('/admin/orders/:idorder', function ($idorder) {
    User::verifyLogin();

    $order = new Order();
    $order->get((int)$idorder);

    $cart = $order->getCart();
    
    $page = new PageAdmin();
    $page->setTpl('order', [
        'order' => $order->getValues(),
        'cart' => $cart->getValues(),
        'products' => $cart->getProducts()
    ]);
});

$app->get('/admin/orders', function () {
    User::verifyLogin();

    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;

    if ($search != '') {
        $pagination = Order::getPagesUsingSearch($search, $currentPage);
    } else {
        $pagination = Order::getPages($currentPage);
    }

    $pages = [];
    for ($i = 0; $i < $pagination['pages']; $i++) {
        array_push($pages, [
            'href' => '/admin/orders?' . http_build_query([
                'page' => $i + 1,
                'search' => $search
            ]),
            'text' => $i + 1
        ]);
    }
    
    $page = new PageAdmin();
    $page->setTpl('orders', [
        'orders' => $pagination['data'],
        'search' => $search,
        'pages' => $pages
    ]);
});
