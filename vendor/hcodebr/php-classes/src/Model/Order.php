<?php

namespace Hcode\Model;

use Hcode\DB\Sql;
use Hcode\Model;

class Order extends Model
{
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
}
