<?php
namespace Hcode;

use Rain\Tpl;

class Mailer{
    const USERNAME = '';
    const PASSWORD = '';
    const NAME_FROM = 'Hcode Store';

    private $mail;

    public function __construct($toAddres, $toName, $subject, $tplName, $data = array()){
        $config = array(
            "tpl_dir"       => $_SERVER["DOCUMENT_ROOT"]."/views/email/",
            "cache_dir"     => $_SERVER["DOCUMENT_ROOT"]."/views/cache/",
            "debug"         => false,
        );

        Tpl::configure( $config );

        $tpl = new Tpl();

        foreach($data as $key => $value){
            $tpl->assign($key, $value);
        }
        $html = $tpl->draw($tplName, true);

        // server config
        $this->mail= new \PHPMailer();
        $this->mail->isSMTP();
        $this->mail->SMTPDebug = 0;
        $this->mail->Debugoutput = 'html';
        $this->mail->Host = 'smtp.gmail.com';
        $this->mail->Port = 587;
        $this->mail->SMTPSecure = 'tls';
        $this->mail->SMTPAuth = true;

        // email params
        $this->mail->Username = Mailer::USERNAME;
        $this->mail->Password = Mailer::PASSWORD;
        $this->mail->setFrom(Mailer::USERNAME, Mailer::NAME_FROM);
        $this->mail->addAddress($toAddres, $toName);
        $this->mail->Subject = $subject;
        $this->mail->msgHTML($html);
        $this->mail->AltBody = 'This is a plain-text message body';
    }

    public function sendMail(){
        return $this->mail->send();
    }
}
