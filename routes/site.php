<?php

use Hcode\Model\Product;
use \Hcode\Page;

$app->get('/', function() {
	$products = Product::listAll();
	$products = Product::checklist($products);

	$page = new Page();	
	$page->setTpl("index", [
		"products"=>Product::checklist($products)
	
	]);
});

$app->get('/admin/products', function(){

});
