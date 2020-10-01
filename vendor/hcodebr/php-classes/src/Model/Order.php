<?php

namespace Hcode\Model;

use Hcode\DB\Sql;
use Hcode\Model;

class Order extends Model
{
    public const ERROR = 'OrderError';
    public const SUCESS = 'OrderSucess';
    public function save()
    {
        $sql = new Sql();
        $result = $sql->select(
            'CALL sp_orders_save (:idorder, :idcart, :iduser, :idstatus, :idaddress, :vltotal);',
            [
                'idorder' => $this->getidorder(),
                'idcart' => $this->getidcart(),
                'iduser' => $this->getiduser(),
                'idstatus' => $this->getidstatus(),
                'idaddress' => $this->getidaddress(),
                'vltotal' => $this->getvltotal()
            ]
        );

        if (count($result) > 0) {
            $this->setData($result[0]);
        }
    }

    public function get($idorder)
    {
        $sql = new Sql();
        $result = $sql->select(
            'SELECT * FROM tb_orders a
                JOIN tb_ordersstatus b USING(idstatus)
                JOIN tb_carts c USING(idcart)
                JOIN tb_users d ON d.iduser = a.iduser
                JOIN tb_addresses e USING(idaddress)
                JOIN tb_persons f ON f.idperson = d.idperson
            WHERE idorder = :idorder',
            [
                ':idorder' => $idorder
            ]
        );

        if (count($result) > 0) {
            $this->setData($result[0]);
        }
    }

    public static function listAll()
    {
        $sql = new Sql();

        return $sql->select(
            'SELECT * FROM tb_orders a
                JOIN tb_ordersstatus b USING(idstatus)
                JOIN tb_carts c USING(idcart)
                JOIN tb_users d ON d.iduser = a.iduser
                JOIN tb_addresses e USING(idaddress)
                JOIN tb_persons f ON f.idperson = d.idperson;
            ORDER BY a.dtregister DESC;'
        );
    }

    public function delete($idorder)
    {
        $sql = new Sql();
        $sql->query('DELETE FROM tb_orders WHERE idorder = :idorder;', [
            'idorder' => $idorder
        ]);
    }

    public function getCart(): Cart
    {
        $cart = new Cart();
        $cart->get($this->getidcart());

        return $cart;
    }

    public static function setMsgError($msg)
    {
        $_SESSION[self::ERROR] = $msg;
    }

    public static function getMsgError()
    {
        $msg = isset($_SESSION[self::ERROR]) && $_SESSION[self::ERROR] ? $_SESSION[self::ERROR] : '';
        self::clearMsgError();
        return $msg;
    }

    public static function clearMsgError()
    {
        $_SESSION[self::ERROR] = null;
    }

    public static function setSucess($msg)
    {
        $_SESSION[self::SUCESS] = $msg;
    }

    public static function getSucess()
    {
        $msg = isset($_SESSION[self::SUCESS]) && $_SESSION[self::SUCESS] ?
            $_SESSION[self::SUCESS] : '';
        self::clearSucess();
        return $msg;
    }

    public static function clearSucess()
    {
        $_SESSION[self::SUCESS] = null;
    }
}
