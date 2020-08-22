<?php 

session_start();

require_once("vendor/autoload.php");

use \Hcode\Model\User;
use \Slim\Slim;
use \Hcode\Page;
use \Hcode\PageAdmin;
use \Rain\Tpl;
use \Hcode\Model\Category;

$app = new Slim();

$app->config('debug', true);

$app->get('/', function() {
	$page = new Page();	
	$page->setTpl("index");
});

$app->get('/admin', function() {
	$page = new PageAdmin();	
	$page->setTpl("index");

	User::verifyLogin();
});

$app->get('/admin/login', function() {
	$page = new PageAdmin(array(
		"header"=>false,
		"footer"=>false
	));	
	$page->setTpl("login");
});

$app->post('/admin/login', function() {
	User::login($_POST["login"], $_POST["password"]);

	header('Location: /admin');
	exit;
});

$app->get('/admin/logout', function(){
	User::logout();

	header('Location: /admin/login');
	exit;
});

$app->get('/admin/users', function(){
	User::verifyLogin();

	$users = User::listAll();

	$page = new PageAdmin();
	$page->setTpl('users', array('users'=>$users));

});

$app->get('/admin/users/create', function(){
	User::verifyLogin();

	$page = new PageAdmin();
	$page->setTpl('users-create');

});

$app->post('/admin/users/create', function(){
	User::verifyLogin();

	$_POST['inadmin'] = (isset($_POST['inadmin']) ? 1 : 0);

	$user = new User();

	$user->setData($_POST);

	$user->save();

	header("Location: /admin/users");
	exit;
});

$app->get('/admin/users/:iduser/delete', function($iduser){
	User::verifyLogin();

	$user = new User();

	$user->get((int)$iduser);

	$user->delete();

	header("Location: /admin/users");
	exit;
});

$app->get('/admin/users/:iduser', function($iduser){
	User::verifyLogin();

	$user = new User();

	$user->get((int)$iduser);

	$page = new PageAdmin();
	$page->setTpl('users-update', array(
		"user"=>$user->getValues()
	));

});

$app->post('/admin/users/:iduser', function($iduser){
	User::verifyLogin();

	$user = new User();

	$user->get((int)$iduser);

	$user->setData($_POST);

	$user->update();

	header("Location: /admin/users");
	exit;
});

$app->get('/admin/forgot', function(){
	$page = new PageAdmin(array(
		"header"=>false,
		"footer"=>false
	));	
	$page->setTpl("forgot");

});

$app->post('/admin/forgot', function(){
	$user = User::getForgot($_POST['email']);

});

$app->get('/admin/forgot/sent', function(){
	$page = new PageAdmin(array(
		"header"=>false,
		"footer"=>false
	));	
	$page->setTpl("forgot-sent");
});

$app->get("/admin/forgot/reset", function(){
	$user = User::validForgotDecrypt($_GET['code']);

	$page = new PageAdmin(array(
		"header"=>false,
		"footer"=>false
	));	
	$page->setTpl("forgot-reset", array(
		"name"=>$user['desperson'],
		"code"=>$_GET['code']
	));
});

$app->post("/admin/forgot/reset", function(){ 
	$forgot = User::validForgotDecrypt($_POST['code']);

	User::setForgotUsed($forgot['idrecovery']);

	$user = new User();

	$user->get((int)$forgot['iduser']);

	$password = password_hash($_POST['password'], PASSWORD_DEFAULT, ["cost"=>12]);

	$user->setPassword($password);

	$page = new PageAdmin(array(
		"header"=>false,
		"footer"=>false
	));	

	$page->setTpl("forgot-reset-success");
});

$app->get('/admin/categories', function(){
	User::verifyLogin();
	$page = new PageAdmin();	

	$categories = Category::listAll();

	$page->setTpl("categories", [
		"categories"=>$categories
	]);
});

$app->get('/admin/categories/create', function(){
	User::verifyLogin();
	$page = new PageAdmin();	

	$page->setTpl("categories-create");
});

$app->post('/admin/categories/create', function(){
	$category = new Category();

	$category->setData($_POST);

	$category->save();

	header('Location: /admin/categories');
	exit;
});

$app->get('/admin/categories/:idcategory/delete', function($idcategory){
	User::verifyLogin();
	$category = new Category();

	$category->get((int)$idcategory);

	$category->delete();

	header('Location: /admin/categories');
	exit;
});

$app->get('/admin/categories/:idcategory', function($idcategory){
	User::verifyLogin();
	$category = new Category();

	$category->get((int)$idcategory);
	
	$page = new PageAdmin();	

	$page->setTpl("categories-update", [
		"category"=>$category->getValues()
	]);
});

$app->post('/admin/categories/:idcategory', function($idcategory){
	User::verifyLogin();
	
	$category = new Category();

	$category->get((int)$idcategory);

	$category->setData($_POST);

	$category->save();

	header('Location: /admin/categories');
	exit;
});

$app->get('/categories/:idcategory', function($idcategory){
	$category = new Category();

	$category->get((int)$idcategory);

	$page = new Page();	

	$page->setTpl("category", [
		"category"=>$category->getValues(),
		"produtos"=>[]
	]);
});

$app->run();

 ?>
