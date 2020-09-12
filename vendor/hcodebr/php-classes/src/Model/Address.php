<?php

namespace Hcode\Model;

use Hcode\DB\Sql;
use Hcode\Model;

class Address extends Model
{
    public const SESSION_ERROR = 'AddressError';

    public static function getCEP($nrcep)
    {
        $nrcep = str_replace('-', '', $nrcep);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://viacep.com.br/ws/$nrcep/json/");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $data = json_decode(curl_exec($ch), true);

        curl_close($ch);

        return $data;
    }

    public function loadFromCEP($nrcep)
    {
        $data = Address::getCEP($nrcep);

        if (isset($data['logradouro']) && $data['logradouro']) {
            $this->setdesaddress($data['logradouro']);
            $this->setdescomplement($data['complemento']);
            $this->setdesdistrict($data['bairro']);
            $this->setdescity($data['localidade']);
            $this->setdesstate($data['uf']);
            $this->setdescountry('Brasil');
            $this->setdesnrzipcode($nrcep);
        } else {
            Address::setMsgError('Não foi possível encontrar o CEP informado.');
            $this->setdesnrzipcode($nrcep);
        }
    }

    public function save()
    {
        $sql = new Sql();
        $result = $sql->select(
            'CALL sp_addresses_save(:idaddress, :idperson,
            :desaddress, :descomplement, :descity, :desstate, :descountry,
            :desnrzipcode, :desdistrict);',
            [
                ':idaddress' => $this->getidaddress(),
                ':idperson' => $this->getidperson(),
                ':desaddress' => $this->getdesaddress(),
                ':descomplement' => $this->getdescomplement(),
                ':descity' => $this->getdescity(),
                ':desstate' => $this->getdesstate(),
                ':descountry' => $this->getdescountry(),
                ':desnrzipcode' => $this->getnrzipcode(),
                ':desdistrict' => $this->getdesdistrict()
            ]
        );

        if (count($result) > 0) {
            $this->setData($result[0]);
        }
    }

    public static function setMsgError($msg)
    {
        $_SESSION[Address::SESSION_ERROR] = $msg;
    }

    public static function getMsgError()
    {
        $msg = isset($_SESSION[Address::SESSION_ERROR]) ? $_SESSION[Address::SESSION_ERROR] : '';
        Address::clearMsgError();
        return $msg;
    }

    public static function clearMsgError()
    {
        $_SESSION[Address::SESSION_ERROR] = null;
    }
}
