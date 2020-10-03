<?php

use Hcode\Model\User;
use Hcode\PageAdmin;

$app->get('/admin/users', function () {
    User::verifyLogin();

    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;

    if ($search != '') {
        $pagination = User::getPagesUsingSearch($search, $currentPage);
    } else {
        $pagination = User::getPages($currentPage);
    }

    $pages = [];
    for ($i = 0; $i < $pagination['pages']; $i++) {
        array_push($pages, [
            'href' => '/admin/users?' . http_build_query([
                'page' => $i + 1,
                'search' => $search
            ]),
            'text' => $i + 1
        ]);
    }

    $page = new PageAdmin();
    $page->setTpl('users', [
        'users' => $pagination['data'],
        'search' => $search,
        'pages' => $pages
    ]);
});

$app->get('/admin/users/create', function () {
    User::verifyLogin();

    $page = new PageAdmin();
    $page->setTpl('users-create');
});

$app->post('/admin/users/create', function () {
    User::verifyLogin();

    $_POST['inadmin'] = (isset($_POST['inadmin']) ? 1 : 0);

    $user = new User();
    $user->setData($_POST);
    $user->save();

    header("Location: /admin/users");
    exit;
});

$app->get('/admin/users/:iduser/delete', function ($iduser) {
    User::verifyLogin();

    $user = new User();
    $user->get((int)$iduser);
    $user->delete();

    header("Location: /admin/users");
    exit;
});

$app->get('/admin/users/:iduser', function ($iduser) {
    User::verifyLogin();

    $user = new User();

    $user->get((int)$iduser);

    $page = new PageAdmin();
    $page->setTpl('users-update', array(
        "user" => $user->getValues()
    ));
});

$app->post('/admin/users/:iduser', function ($iduser) {
    User::verifyLogin();

    $user = new User();

    $user->get((int)$iduser);

    $user->setData($_POST);

    $user->update();

    header("Location: /admin/users");
    exit;
});
