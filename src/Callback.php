<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/18
 * Time: 16:25
 */

namespace Anyluck;


class Callback
{
    function __invoke($error,$result){
        if ($error !== null) {
            $this->fail=$error->getMessage();
        }else{
            $this->result=$result;
        }
    }
}