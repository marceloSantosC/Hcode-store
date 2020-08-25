<?php

use Hcode\Model\Product;
use Hcode\Page;
use	Hcode\Model\Category;

$app->get('/', function() {
	$products = Product::listAll();
	$products = Product::checklist($products);

	$page = new Page();	
	$page->setTpl("index", [
		"products"=>Product::checklist($products)
	
	]);
});

$app->get('/categories/:idcategory', function($idcategory){
	$category = new Category();
	$category->get((int)$idcategory);

	$currentPage = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;
	$pagination = $category->getProductsPage($currentPage);

	$pages = [];
	for($i = 1; $i <= $pagination['pages']; $i++) {
		array_push($pages, [
			'link'=>"/categories/$idcategory?page=$i",
			'page'=>$i
		]);
	}

	$page = new Page();	
	$page->setTpl("category", [
		"category"=>$category->getValues(),
		"products"=>$pagination['data'],
		"pages"=>$pages
	]);


});

$app->get('/admin/products', function(){

});
