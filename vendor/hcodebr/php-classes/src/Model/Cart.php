<?php

namespace Hcode\Model;

use Hcode\DB\Sql;
use Hcode\Model;
use Hcode\Model\Product;

class Cart extends Model{

    const SESSION = 'Cart';

    public static function getFromSession()
    {
        $cart = new Cart();

        if (
                isset($_SESSION[Cart::SESSION]) &&
                isset($_SESSION[Cart::SESSION]['idcart']) &&
                (int)$_SESSION[Cart::SESSION]['idcart'] > 0
            ) {
                $cart->get((int)$_SESSION[Cart::SESSION]['idcart']);
        } else {
            $cart->getFromSessionId();
            if (!(int)$cart->getidcart()) {
                $data = [
                    'dessessionid'=>session_id()
                ];
                

                if (User::checkLogin(false)) {
                    $user = User::getFromSession();
                    $data['iduser'] = $user->getiduser();
                }

                $cart->setData($data);
                $cart->save();
                $cart->setToSession();
            }
        }

        return $cart;
    }

    public function setToSession()
    {
        $_SESSION[Cart::SESSION] = $this->getValues();
    }

    public function getFromSessionId()
    {
        $dessessionid = session_id();

        $sql = new Sql();
        $result = $sql->select("SELECT * FROM tb_carts WHERE dessessionid = :dessessionid;",
            [":dessessionid"=>$dessessionid]);

        if(count($result) > 0) {
            $this->setData($result[0]);
        }
    }

    public function get(int $idcart)
    {
        $sql = new Sql();
        $result = $sql->select("SELECT * FROM tb_carts WHERE idcart = :idcart;",
            [":idcart"=>$idcart]);

        if(count($result) > 0) {
            $this->setData($result[0]);
        }
    }

    public function save()
    {
        $sql = new Sql();

        $result = $sql->select("CALL sp_carts_save(:idcart, :dessessionid, :iduser, :deszipcode,
            :vlfreight, :nrdays)",[
                ":idcart"=>$this->getidcart(),
                ":dessessionid"=>$this->getdessessionid(),
                ":iduser"=>$this->getiduser(),
                ":deszipcode"=>$this->getdeszipcode(),
                ":vlfreight"=>$this->getvlfreight(),
                ":nrdays"=>$this->getnrdays()
        ]);
        return $result[0];
    }

    public function addProduct(Product $product)
    {
        $sql = new Sql();
        $sql->select("INSERT INTO tb_cartsproducts (idcart, idproduct)
            VALUES(:idcart, :idproduct)",[
                ":idcart"=>$this->getidcart(),
                ":idproduct"=>$product->getidproduct()
            ]);
    }

    public function removeProduct(Product $product, $all = false)
    {
        $sql = new Sql();
        $query = NULL;

        if ($all) {
            $query = "UPDATE tb_cartsproducts SET dtremoved = NOW() WHERE
                idcart = :idcart AND idproduct = :idproduct AND dtremoved IS NULL";
        } else {
            $query = "UPDATE tb_cartsproducts SET dtremoved = NOW() WHERE
            idcart = :idcart AND idproduct = :idproduct AND dtremoved IS NULL
            LIMIT 1;";
        }

        $sql->query($query, [
            ":idcart"=>$this->getidcart(),
            ":idproduct"=>$product->getidproduct()
        ]);
    }

    public function getProducts()
    {
        $sql = new Sql();

        return Product::checklist($sql->select("
            SELECT b.idproduct, b.desproduct, b.vlprice, b.vlheight, b.vllength, 
                b.vlweight, b.desurl, COUNT(*) AS nrqtd, SUM(b.vlprice) AS vltotal
            FROM tb_cartsproducts a 
            INNER JOIN tb_products b ON a.idproduct = b.idproduct 
            WHERE a.idcart = :idcart
            AND a.dtremoved IS NULL 
            GROUP BY b.idproduct, b.desproduct, b.vlprice, b.vlheight, 
                b.vllength, b.vlweight, b.desurl
            ORDER BY b.desproduct;
        ", [
            ':idcart'=>$this->getidcart()
        ]));
    }
}
