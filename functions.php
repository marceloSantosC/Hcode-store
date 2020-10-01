<?php

use Hcode\Model\Cart;
use Hcode\Model\User;

function formatPrice($vlprice)
{
    return (float)number_format($vlprice, 2, ',', '.');
}

function formatDate($date)
{
    return date('d/m/Y', strtotime($date));
}

function checkLogin($inadmin = true)
{
    return User::checkLogin($inadmin);
}

function getUserName()
{
    $user = User::getFromSession();
    return $user->getdesperson();
}


function getQuantityOfProductsInCart()
{
    $cart = Cart::getFromSession();
    $totals = $cart->getProductsTotals();
    
    return $totals['nrqtd'];
}

function getTotalPriceOfProductsInCart()
{
    $cart = Cart::getFromSession();
    $totals = $cart->getProductsTotals();
    
    return formatPrice($totals['vlprice']);
}
