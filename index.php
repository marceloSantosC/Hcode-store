<?php 

require_once("vendor/autoload.php");

use \Slim\Slim;

session_start();

$app = new Slim();
$app->config('debug', true);

$files = scandir("routes/");
foreach($files as $file){
    if(!in_array($file, array(".", ".."))) {
        require_once("routes/$file");
    }
}

$app->run();