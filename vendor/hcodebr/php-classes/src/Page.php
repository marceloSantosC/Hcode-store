<?php
namespace Hcode;

use Rain\Tpl;

class Page{

    private $tpl;
    private $config = [];
    private $defaultConfig = [
        "header"=>true,
        "footer"=>true,
        "data"=>[]
    ];


    public function __construct($config = array(), $tpl_dir = '/views/'){
        $this->config = array_merge($this->defaultConfig, $config);

        $config = array(
            "tpl_dir"       => $_SERVER["DOCUMENT_ROOT"].$tpl_dir,
            "cache_dir"     => $_SERVER["DOCUMENT_ROOT"]."/views/cache/",
            "debug"         => false,
        );

        Tpl::configure( $config );

        $this->tpl = new Tpl();

        $this->setconfig($this->config["data"]);

        if($this->config["header"]) $this->tpl->draw("header");
    }

    private function setconfig($config = array()){
        foreach($config as $key => $value ){
            $this->tpl->assign($key, $value);
        }
    }

    public function setTpl($name, $config = array(), $returnHTML = false){
        $this->setconfig($config);

        return $this->tpl->draw($name, $returnHTML);
    }

    public function __destruct(){
        if($this->config["footer"]) $this->tpl->draw("footer");
    }
}
