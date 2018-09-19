<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/18
 * Time: 13:50
 */

namespace Any_ether;


class Retun
{
    public   function Success($data='',$mag='成功',$code=1)
    {
        $array['data']=$data;
        $array['msg']=$mag;
        $array['code']=$code;
        return $array;
    }
    public  function Error($mag='失败',$data='',$code=2)
    {
        $array['data']=$data;
        $array['msg']=$mag;
        $array['code']=$code;
        return $array;
    }
}