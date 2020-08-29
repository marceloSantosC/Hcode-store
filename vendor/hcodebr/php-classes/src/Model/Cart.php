<?php

namespace Hcode\Model;

use Hcode\DB\Sql;
use Hcode\Model;
use Hcode\Model\Product;

class Cart extends Model
{

    public const SESSION = 'Cart';
    public const SESSION_ERROR = 'CartError';

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
                    'dessessionid' => session_id()
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
        $result = $sql->select(
            "SELECT * FROM tb_carts WHERE dessessionid = :dessessionid;",
            [":dessessionid" => $dessessionid]
        );

        if (count($result) > 0) {
            $this->setData($result[0]);
        }
    }

    public function get(int $idcart)
    {
        $sql = new Sql();
        $result = $sql->select(
            "SELECT * FROM tb_carts WHERE idcart = :idcart;",
            [":idcart" => $idcart]
        );

        if (count($result) > 0) {
            $this->setData($result[0]);
        }
    }

    public function save()
    {
        $sql = new Sql();

        $result = $sql->select("CALL sp_carts_save(:idcart, :dessessionid, :iduser, :deszipcode,
            :vlfreight, :nrdays)", [
                ":idcart" => $this->getidcart(),
                ":dessessionid" => $this->getdessessionid(),
                ":iduser" => $this->getiduser(),
                ":deszipcode" => $this->getdeszipcode(),
                ":vlfreight" => $this->getvlfreight(),
                ":nrdays" => $this->getnrdays()
        ]);
        return $result[0];
    }

    public function addProduct(Product $product)
    {
        $sql = new Sql();
        $sql->select("INSERT INTO tb_cartsproducts (idcart, idproduct)
            VALUES(:idcart, :idproduct)", [
                ":idcart" => $this->getidcart(),
                ":idproduct" => $product->getidproduct()
        ]);
        
        $this->calculateTotal();
    }

    public function removeProduct(Product $product, $all = false)
    {
        $sql = new Sql();
        $query = null;

        if ($all) {
            $query = "UPDATE tb_cartsproducts SET dtremoved = NOW() WHERE
                idcart = :idcart AND idproduct = :idproduct AND dtremoved IS NULL";
        } else {
            $query = "UPDATE tb_cartsproducts SET dtremoved = NOW() WHERE
            idcart = :idcart AND idproduct = :idproduct AND dtremoved IS NULL
            LIMIT 1;";
        }

        $sql->query($query, [
            ":idcart" => $this->getidcart(),
            ":idproduct" => $product->getidproduct()
        ]);

        $this->calculateTotal();
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
            ':idcart' => $this->getidcart()
        ]));
    }

    public function getProductsTotals()
    {
        $sql = new Sql();
        $result = $sql->select("
            SELECT SUM(vlprice) AS vlprice, SUM(vlwidth) AS vlwidth, 
            SUM(vlheight) AS vlheight, SUM(vllength) AS vllength, 
            SUM(vlweight) AS vlweight, COUNT(*) AS nrqtd
            FROM tb_products a 
            INNER JOIN tb_cartsproducts b ON a.idproduct = b.idproduct
            WHERE b.idcart = :idcart AND b.dtremoved IS NULL;
        ", [
            ':idcart' => $this->getidcart()
        ]);

        if (count($result) > 0) {
            return $result[0];
        } else {
            return [];
        }
    }

    public function setFreight($zipcode)
    {
        $nrZipCode = str_replace('-', '', $zipcode);

        $totals = $this->getProductsTotals();
        
        $totals['vlheight'] = $totals['vlheight'] > 2 ? $totals['vlheight'] : 2;
        $totals['vllength'] = $totals['vllength'] > 16 ? $totals['vllength'] : 16;

        if ($totals['nrqtd'] > 0) {
            $qs = http_build_query([
                'nCdEmpresa' => '',
                'sDsSenha' => '',
                'nCdServico' => '40010',
                'sCepOrigem' => '09853120',
                'sCepDestino' => $nrZipCode,
                'nVlPeso' => $totals['vlweight'],
                'nCdFormato' => 1,
                'nVlComprimento' => $totals['vllength'],
                'nVlAltura' => $totals['vlheight'],
                'nVlLargura' => $totals['vlwidth'],
                'nVlDiametro' => '0',
                'sCdMaoPropria' => 'S',
                'nVlValorDeclarado' => $totals['vlprice'],
                'sCdAvisoRecebimento' => 'S',
            ]);
            $xml = simplexml_load_file("http://ws.correios.com.br/calculador/CalcPrecoPrazo.asmx/CalcPrecoPrazo?$qs");


            $result = $xml->Servicos->cServico;

            if ($result->MsgErro != '') {
                Cart::setMsgError($result->MsgErro);
            } else {
                Cart::clearMsgError();
            }

            $this->setnrdays($result->PrazoEntrega);
            $this->setvlfreight(Cart::formatValueToDecimal($result->Valor));
            $this->setdeszipcode($nrZipCode);

            $this->save();

            return $result;
        } else {
            return null;
        }
    }

    public function updateFreight()
    {
        if ($this->getdeszipcode() != '') {
            $this->setFreight($this->getdeszipcode());
        }
    }

    public static function formatValueToDecimal($value): float
    {
        $value = str_replace('.', '', $value);
        return str_replace(',', '.', $value);
    }

    public static function setMsgError($msg)
    {
        $_SESSION[Cart::SESSION_ERROR] = $msg;
    }

    public static function getMsgError()
    {
        $msg = isset($_SESSION[Cart::SESSION_ERROR]) ? $_SESSION[Cart::SESSION_ERROR] : '';
        Cart::clearMsgError();
        return $msg;
    }

    public static function clearMsgError()
    {
        $_SESSION[Cart::SESSION_ERROR] = null;
    }

    public function getValues()
    {
        $this->calculateTotal();
        return parent::getValues();
    }

    public function calculateTotal()
    {
        $this->updateFreight();

        $totals = $this->getProductsTotals();

        $this->setvlsubtotal($totals['vlprice']);
        $this->setvltotal($totals['vlprice'] + $this->getvlfreight());
    }
}
