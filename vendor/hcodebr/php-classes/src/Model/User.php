<?php

namespace Hcode\Model;

use Hcode\DB\Sql;
use Hcode\Mailer;
use Hcode\Model;

class User extends Model
{
    public const SESSION = 'User';
    public const ERROR = 'UserErrors';
    public const REGISTER_ERROR = 'RegisterErrors';
    public const SUCESS = 'SucessMessages';
    private const KEY = 'ALYbhXPbrQPMscfc';
    private const KEY_IV = 'qAtdP3CX9q9WGykT';

    public static function login($login, $password)
    {
        $sql = new Sql();
        $result = $sql->select("SELECT * FROM tb_users a INNER JOIN tb_persons b 
        USING(idperson) WHERE deslogin = :LOGIN", array(
            ":LOGIN" => $login
        ));

        if (count($result) === 0) {
            throw new \Exception("Usuário inexistente ou senha inválida.", 1);
        }

        $data = $result[0];

        if (password_verify($password, $data["despassword"])) {
            $user = new User();
            $data['desperson'] = $data['desperson'];
            $user->setData($data);

            $_SESSION[User::SESSION] = $user->getValues();
            return $user;
        } else {
            throw new \Exception("Usuário inexistente ou senha inválida.", 1);
        }
    }

    public static function verifyLogin($inadmin = true)
    {
        if (!User::checkLogin($inadmin)) {
            if ($inadmin) {
                header("Location: /admin/login");
            } else {
                header("Location: /login");
            }
            exit;
        }
    }

    public static function checkLogin($inadmin = true)
    {
        if (
            !isset($_SESSION[User::SESSION]) ||
            !$_SESSION[User::SESSION] ||
            !(int)$_SESSION[User::SESSION]['iduser'] > 0
        ) {
            return false;
        } else {
            if ($inadmin === true && (bool)$_SESSION[User::SESSION]['inadmin']) {
                return true;
            } elseif (!$inadmin) {
                return true;
            } else {
                return false;
            }
        }
    }

    public static function checkLoginExists($login)
    {
        $sql = new Sql();
        $result = $sql->select("SELECT * FROM tb_users WHERE deslogin = :LOGIN", [
            ":LOGIN" => $login
        ]);

        return (count($result) > 0);
    }

    public static function logout()
    {
        session_unset();
    }

    public function setData($data = [])
    {
        parent::setData($data);
        $_SESSION[User::SESSION] = $this->getValues();
    }

    public static function listAll()
    {
        $sql = new Sql();
        return $sql->select("SELECT * FROM tb_users a 
            INNER JOIN tb_persons b USING(idperson) ORDER BY b.desperson");
    }

    public function save()
    {
        $sql = new Sql();

        $result = $sql->select("CALL sp_users_save(:desperson, :deslogin, :despassword,
            :desemail, :nrphone, :inadmin);", array(
            ":desperson" => $this->getdesperson(),
            ":deslogin" => $this->getdeslogin(),
            ":despassword" => self::getPasswordHash($this->getdespassword()),
            ":desemail" => $this->getdesemail(),
            ":nrphone" => $this->getnrphone(),
            ":inadmin" => $this->getinadmin()
        ));

        $this->setData($result[0]);
    }

    public function get($iduser)
    {
        $sql = new Sql();
        $result = $sql->select("SELECT * FROM tb_users a INNER JOIN tb_persons b 
            USING(idperson) WHERE a.iduser = :iduser;", array(
                ":iduser" => $iduser
        ));

        $result[0]['desperson'] = $result[0]['desperson'];
        $this->setData($result[0]);
    }

    public static function getFromSession()
    {
        $user = new User();
        if (isset($_SESSION[User::SESSION]) && (int)$_SESSION[User::SESSION]['iduser'] > 0) {
            $user->setData($_SESSION[User::SESSION]);
        }

        return $user;
    }
    
    public function update()
    {
        $sql = new Sql();

        $result = $sql->select("CALL sp_usersupdate_save(:iduser, :desperson, 
            :deslogin, :despassword, :desemail, :nrphone, :inadmin);", array(
            ":iduser" => $this->getiduser(),
            ":desperson" => $this->getdesperson(),
            ":deslogin" => $this->getdeslogin(),
            ":despassword" => self::getPasswordHash($this->getdespassword()),
            ":desemail" => $this->getdesemail(),
            ":nrphone" => $this->getnrphone(),
            ":inadmin" => $this->getinadmin()
        ));

        $this->setData($result[0]);
    }
  
    public function delete()
    {
        $sql = new Sql();

        $sql->query("CALL sp_users_delete(:iduser);", array(
            ":iduser" => $this->getiduser()
        ));
    }

    public static function getForgot($email, $inadmin = true)
    {
        $sql = new Sql();
        $result = $sql->select("SELECT * FROM tb_persons a INNER JOIN 
            tb_users b USING(idperson) WHERE a.desemail = :email;", array(":email" => $email));

        if (count($result) === 0) {
            throw new \Exception("Não foi possível recuperar a senha.");
        } else {
            $data = $result[0];

            $resultRecovery = $sql->select(
                "CALL sp_userspasswordsrecoveries_create(:iduser, :desip)",
                [
                    ":iduser" => $data["iduser"],
                    ":desip" => $_SERVER["REMOTE_ADDR"]
                ]
            );

            if (count($resultRecovery) === 0) {
                throw new \Exception("Não foi possível recuperar a senha.");
            } else {
                $dataRecovery = $resultRecovery[0]['idrecovery'];
                $code = openssl_encrypt(base64_encode($dataRecovery), 'aes-128-ctr', User::KEY, 0, User::KEY_IV);

                $link = null;
                if ($inadmin) {
                    $link = "http://www.hcodecommerce.com.br/admin/forgot/reset?code=$code";
                } else {
                    $link = "http://www.hcodecommerce.com.br/forgot/reset?code=$code";
                }

                $mailer = new Mailer(
                    $data['desemail'],
                    $data['desperson'],
                    'Redefir senha da Hcode Store',
                    'forgot',
                    [
                        "name" => $data['desperson'],
                        "link" => $link
                    ]
                );

                return $mailer->sendMail();
            }
        }
    }

    public static function validForgotDecrypt($code)
    {
        $idrecovery = (int)base64_decode(openssl_decrypt($code, 'aes-128-ctr', User::KEY, 0, User::KEY_IV));
        $query = "SELECT * FROM tb_userspasswordsrecoveries a 
            INNER JOIN tb_users b USING(iduser) 
            INNER JOIN tb_persons c USING(idperson) 
            WHERE 
                a.idrecovery = :idrecovery
                AND a.dtrecovery IS NULL 
                AND DATE_ADD(a.dtregister, INTERVAL 1 HOUR) >= NOW();";



        $sql = new Sql();
        $result = $sql->select($query, array(":idrecovery" => $idrecovery));

        if (count($result) === 0) {
            throw new \Exception("Não foi possível recuperar a senha.");
        } else {
            return $result[0];
        }
    }

    public static function setForgotUsed($idrecovery)
    {
        $sql = new Sql();

        $sql->query(
            "UPDATE tb_userspasswordsrecoveries SET dtrecovery = NOW() WHERE idrecovery = :idrecovery",
            array(":idrecovery" => $idrecovery)
        );
    }

    public function setPassword($password)
    {
        $sql = new Sql();
        $sql->query("UPDATE tb_users SET despassword = :despassword WHERE iduser = :iduser;", array(
            ":despassword" => $password,
            ":iduser" => $this->getiduser()
        ));
    }

    public static function setMsgError($msg)
    {
        $_SESSION[User::ERROR] = $msg;
    }

    public static function getMsgError()
    {
        $msg = isset($_SESSION[User::ERROR]) && $_SESSION[User::ERROR] ? $_SESSION[User::ERROR] : '';
        User::clearMsgError();
        return $msg;
    }

    public static function clearMsgError()
    {
        $_SESSION[User::ERROR] = null;
    }

    public static function setRegisterMsgError($msg)
    {
        $_SESSION[User::REGISTER_ERROR] = $msg;
    }

    public static function getRegisterMsgError()
    {
        $msg = isset($_SESSION[User::REGISTER_ERROR]) && $_SESSION[User::REGISTER_ERROR] ?
            $_SESSION[User::REGISTER_ERROR] : '';
        User::clearRegisterMsgError();
        return $msg;
    }

    public static function clearRegisterMsgError()
    {
        $_SESSION[User::SUCESS] = null;
    }

    public static function setSucess($msg)
    {
        $_SESSION[User::SUCESS] = $msg;
    }

    public static function getSucess()
    {
        $msg = isset($_SESSION[User::SUCESS]) && $_SESSION[User::SUCESS] ?
            $_SESSION[User::SUCESS] : '';
        User::clearSucess();
        return $msg;
    }

    public static function clearSucess()
    {
        $_SESSION[User::SUCESS] = null;
    }

    public static function getPasswordHash($password)
    {
        return password_hash($password, PASSWORD_DEFAULT, [
            'cost' => 12
        ]);
    }

    public function getOrders()
    {
        $sql = new Sql();

        $result = $sql->select(
            'SELECT * FROM tb_orders a
                JOIN tb_ordersstatus b USING(idstatus)
                JOIN tb_carts c USING(idcart)
                JOIN tb_users d ON d.iduser = a.iduser
                JOIN tb_addresses e USING(idaddress)
                JOIN tb_persons f ON f.idperson = d.idperson
            WHERE a.iduser = :iduser',
            [
                ':iduser' => $this->getiduser()
            ]
        );

        return $result;
    }

    public static function getPages($page = 1, $itemsPerPage = 15)
    {
        $start = ($page - 1) * $itemsPerPage;

        $sql = new Sql();
        $result = $sql->select(
            "SELECT SQL_CALC_FOUND_ROWS *
            FROM tb_users a 
            INNER JOIN tb_persons b USING(idperson) 
            ORDER BY b.desperson
            LIMIT $start, $itemsPerPage;"
        );

        $totalItems = $sql->select("SELECT FOUND_ROWS() AS nrtotal;");

        return [
            'data' => $result,
            'total' => (int)$totalItems[0]['nrtotal'],
            'pages' => ceil($totalItems[0]['nrtotal'] / $itemsPerPage)
        ];
    }

    public static function getPagesUsingSearch($search, $page = 1, $itemsPerPage = 15)
    {
        $start = ($page - 1) * $itemsPerPage;

        $sql = new Sql();
        $result = $sql->select(
            "SELECT SQL_CALC_FOUND_ROWS *
            FROM tb_users a 
            INNER JOIN tb_persons b USING(idperson)
            WHERE b.desperson LIKE :search OR b.desemail = :search OR a.deslogin LIKE :search
            ORDER BY b.desperson
            LIMIT $start, $itemsPerPage;",
            [':search' => $search]
        );

        $totalItems = $sql->select("SELECT FOUND_ROWS() AS nrtotal;");

        return [
            'data' => $result,
            'total' => (int)$totalItems[0]['nrtotal'],
            'pages' => ceil($totalItems[0]['nrtotal'] / $itemsPerPage)
        ];
    }
}
