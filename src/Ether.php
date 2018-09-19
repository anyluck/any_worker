<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/18
 * Time: 11:16
 */

namespace Anyluck\ether;
use Web3\Providers\HttpProvider;
use Web3\RequestManagers\HttpRequestManager;
use Web3\Web3;

class Ether
{
    private $http='http://127.0.0.1:8545';
    private $outTime=5;
    private $Web3;
    private $Contract;
    private $drivers='';
    private $gateways;
    public $Retun;
    public $cb;

    public function __construct($http='',$outTime='')
    {

        if ($http)$this->http=$http;
        if ($outTime)$this->outTime=$outTime;
        $this->Retun=new Retun();
        $this->cb=new Callback();
        $this->Web3=new Web3(new HttpProvider(new HttpRequestManager($this->http,$this->outTime)));
    }
    /**
     * 指定驱动器
     * @param string $driver
     * @return $this
     */
    public  function driver($driver)
    {
        $this->drivers = $driver;
        return $this;
    }
    /**
     * @param $data
     * @return $this  提交参数
     */
    public function gateway($data)
    {
      if (!$this->drivers) return  $this->Retun->Error('没有调用参数');
       $this->gateways=$data;
       return $this->setAether();
    }

    /**
     * @return mixed 调用参数方法
     */
    public function setAether()
    {
        $gateway=__NAMESPACE__.'\\Aether';
        $gateways= new $gateway($this->Web3,$this->cb,$this->drivers,$this->gateways);
        $drivers=$this->getdriver();
        $method=$gateways->getMethod();
        if (!in_array($drivers,$method)) return $this->Retun->Error('方法不存在');
        return $gateways->$drivers();
    }

    /**
     * @return mixed 获取设置的方法名称
     */
    public function getdriver()
    {
        return $this->drivers;
    }

    /**
     * @return mixed 获取提交的参数
     */
    public function getgateway()
    {
        return $this->gateways;
    }





}