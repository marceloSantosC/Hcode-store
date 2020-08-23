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

	$page = new Page();	
	$page->setTpl("category", [
		"category"=>$category->getValues(),
		"products"=>Product::checklist($category->getProducts())
	]);


});

$app->get('/admin/products', function(){

});
