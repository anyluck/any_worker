<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/18
 * Time: 11:19
 */
require_once '../vendor/autoload.php';
use Anyluck\Ether;
$ether=new Ether();
$ret=$ether->driver('personal_newAccount')->gateway('222');
//$ret=$ether->getdriver();
var_dump($ret);