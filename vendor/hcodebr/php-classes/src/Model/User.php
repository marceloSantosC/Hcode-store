<?php

namespace Hcode\Model;

use \Hcode\DB\Sql;
use Hcode\Mailer;
use \Hcode\Model;

class User extends Model{
    const SESSION = 'User';
    const KEY = 'ALYbhXPbrQPMscfc';
    const KEY_IV = 'qAtdP3CX9q9WGykT';

    public static function login($login, $password)
    {
        $sql = new Sql();
        $result = $sql->select("SELECT * FROM tb_users WHERE deslogin = :LOGIN", array(
            ":LOGIN"=>$login
        ));

        if (count($result) === 0) {
            throw new \Exception("Usuário inexistente ou senha inválida.", 1);
        }

        $data = $result[0];

        if (password_verify($password, $data["despassword"])) {
            $user = new User();
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
            header("Location: /admin/login");
            exit;
        } 
    }

    public static function checkLogin($inadmin = true)
    {   
        if (
            !isset($_SESSION[User::SESSION]) || 
            !(int)$_SESSION[User::SESSION]['iduser'] > 0
        ) {
            return false;
        } else {
            if(
                $inadmin === true && 
                (bool)$_SESSION[User::SESSION]['inadmin']
            ) {
                return true;
            } else if(!$inadmin) {
                return true;
            } else {
                return false;
            }
        }
    }

    public static function logout()
    {
        session_unset();
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
            ":desperson"=>$this->getdesperson(),
            ":deslogin"=>$this->getdeslogin(),
            ":despassword"=>$this->getdespassword(),
            ":desemail"=>$this->getdesemail(),
            ":nrphone"=>$this->getnrphone(),
            ":inadmin"=>$this->getinadmin()
        ));

        $this->setData($result[0]);
    }

    public function get($iduser)
    {
        $sql = new Sql();
        $result = $sql->select("SELECT * FROM tb_users a INNER JOIN tb_persons b 
            USING(idperson) WHERE a.iduser = :iduser;", array(
                ":iduser"=>$iduser
        ));

        $this->setData($result[0]);
    }

    public static function getFromSession()
    {
        $user = new User();
        if(
            isset($_SESSION[User::SESSION]) && 
            (int)$_SESSION[User::SESSION]['iduser'] > 0
        ) {
            $user->setData($_SESSION[User::SESSION]);  
        }

        return $user;
    }
    
    public function update()
    {
        $sql = new Sql();

        $result = $sql->select("CALL sp_usersupdate_save(:iduser, :desperson, 
            :deslogin, :despassword, :desemail, :nrphone, :inadmin);", array(
            ":iduser"=>$this->getiduser(),
            ":desperson"=>$this->getdesperson(),
            ":deslogin"=>$this->getdeslogin(),
            ":despassword"=>$this->getdespassword(),
            ":desemail"=>$this->getdesemail(),
            ":nrphone"=>$this->getnrphone(),
            ":inadmin"=>$this->getinadmin()
        ));

        $this->setData($result[0]);
    }

        
    public function delete(){
        $sql = new Sql();

        $sql->query("CALL sp_users_delete(:iduser);", array(
            ":iduser"=>$this->getiduser()
        ));
    }

    public static function getForgot($email) {
        $sql = new Sql();
        $result = $sql->select("SELECT * FROM tb_persons a INNER JOIN 
            tb_users b USING(idperson) WHERE a.desemail = :email;", array(":email"=>$email));
        
        if(count($result) === 0){
            throw new \Exception("Não foi possível recuperar a senha.");
        }
        else {
            $data = $result[0];

            $resultRecovery = $sql->select("CALL sp_userspasswordsrecoveries_create(:iduser, :desip)",
            array(
                ":iduser"=>$data["iduser"],
                ":desip"=>$_SERVER["REMOTE_ADDR"]
            ));

            if(count($resultRecovery) === 0) {
                throw new \Exception("Não foi possível recuperar a senha.");
            } else {
                $dataRecovery = $resultRecovery[0]['idrecovery'];
                $code = openssl_encrypt(base64_encode($dataRecovery), 'aes-128-ctr', User::KEY, 0, User::KEY_IV);

                $link = "http://www.hcodecommerce.com.br/admin/forgot/reset?code=$code";

                $mailer = new Mailer($data['desemail'], 
                    $data['desperson'], 'Redifir senha da Hcode Store', 'forgot', array(
                        "name"=>$data['desperson'],
                        "link"=>$link
                    ));

                $mailer->sendMail();

                header("Location: /admin/forgot/sent");
                exit;

                return $data;

            }
        }
    }

    public static function validForgotDecrypt($code){
        $idrecovery = (int)base64_decode(openssl_decrypt($code, 'aes-128-ctr', User::KEY, 0, User::KEY_IV));
        $query = "SELECT * FROM tb_userspasswordsrecoveries a 
            INNER JOIN tb_users b USING(iduser) 
            INNER JOIN tb_persons c USING(idperson) 
            WHERE 
                a.idrecovery = :idrecovery
                AND a.dtrecovery IS NULL 
                AND DATE_ADD(a.dtregister, INTERVAL 1 HOUR) >= NOW();";



        $sql = new Sql();
        $result = $sql->select($query, array(":idrecovery"=>$idrecovery));

        if(count($result) === 0){
            throw new \Exception("Não foi possível recuperar a senha.");
        } else {
            return $result[0];
        }
    }

    public static function setForgotUsed($idrecovery){
        $sql = new Sql();

        $sql->query("UPDATE tb_userspasswordsrecoveries 
            SET dtrecovery = NOW() 
            WHERE idrecovery = :idrecovery", 
            array(":idrecovery"=>$idrecovery)
        );
    }

    public function setPassword($password){
        $sql = new Sql();
        $sql->query("UPDATE tb_users SET despassword = :despassword WHERE iduser = :iduser;", array(
            ":despassword"=>$password,
            ":iduser"=>$this->getiduser()

            
        ));
    }   
}
