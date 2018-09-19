<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/19
 * Time: 13:19
 */

namespace Anyluck;


use Web3\Utils;

class Contract extends Retun
{
    public $contract;
    public $token='';
    public $address='';
    public $testAbi='';
    public $web3;
    public $cb;
    public $json='';
    public $path='';
    public function __construct($web3,$cb,$method,$parameter)
    {
        $this->web3=$web3;
        $this->cb=$cb;
        if (!$this->Jsons($parameter))return $this->Error('不是标准的json');
        if ($method!='addToken'){
            $this->json=json_decode($parameter,true);
            if (!isset($this->json['name']))return $this->Error('没有提交token名称');
            if (!isset($this->json['path']))return $this->Error('没有提交token路径');
            $this->token=$this->json['name'];
            $this->path=$this->json['path'];
            $this->getToken();//获取代币信息
        }
    }
    /**
     * @return array 提供能调用的方法
     */
    public function getMethod()
    {$method=[
        'token_BalanceOf',     // 查看余额
        'addToken',       // 添加token
        'token_decimals',    // 获取代币小数位
        'token_totalSupply',    // 获取合约方法
        'token_Transfer',        // 转账
    ];
        return $method;
    }
    /**
     * @return mixed 交易
     */
    public function token_Transfer()
    {
        if (!isset($this->json['from'])&&empty($this->json['from']))return $this->Error('没有提交发送地址');
        if (!isset($this->json['to'])&&empty($this->json['to']))return $this->Error('没有提交收货地址');
        if (!isset($this->json['pay'])&&empty($this->json['pay']))return $this->Error('没有提交支付密码');
        if (!isset($this->json['value'])&&empty($this->json['value']))return $this->Error('没有提交发送数量');
        if (!Utils::isAddress($this->json['from'])) return $this->Error('不是标准的发送地址');
        if (!Utils::isAddress($this->json['to'])) return $this->Error('不是标准的收货地址');
        if (isset($this->json['gas'])&& !empty($this->json['gas']))  $gas='0x'.base_convert($this->json['gas'],10,16) ;$data['gas']=$gas;
        if (isset($this->json['gasPrice'])&& !empty($this->json['gasPrice']))  $gasPrice='0x'.base_convert($this->json['gasPrice'],10,16) ;$data['gasPrice']=$gasPrice;
        $data['from']=$this->json['from'];
        $to=$this->json['to'];
        $pay=$this->json['pay'];
        $value=$this->json['value'];
        $balance=$this->token_BalanceOf(true);
        if ($value>$balance) return $this->Error('账户token不足');
        // 获取token 小数点
        $decimals=$this->token_decimals(true);
        $num=bcmul($value,bcpow('10',$decimals));
        $number='0x'.base_convert($num,10,16);
        if (!$this->unlock( $data['from'],$pay)) return $this->Error('发送地址密码错误');
        $opts=json_encode($data);
        $end=json_decode($opts,true);
        $this->contract->send('transfer', $to, $number, $end, function ($eer, $account) use (&$data) {
            if ($eer != null) {
                $data['err'] = $eer->getMessage();
            }
            $data['account'] = $account;
        });
        if (isset($data['err'])) return $this->Error($data['err']);
        $this->Success($data['account']);
    }

    /**
     * @param $account
     * @param $pay
     * @return mixed 解锁账户
     */
    public function unlock($account,$pay)
    {
        $data='{"method":"personal_unlockAccount","params":["'.$account.'","'.$pay.'",null],"id":1,"jsonrpc":"2.0"}';
        $ret=$this->post($data);
        $josn=json_decode($ret,true);
        if (isset($josn['error'])){
            return $josn['error'];
        }else{
            return true;
        }
    }
    /**
     * @param $cb 获取token方法
     */
    public function totalSupply()
    {
        $ret=$this->contract->getFunctions();
       return $this->Success($ret);
    }
    /**
     * @param bool $true
     * @return mixed 获取代币小数位
     */
    public function token_decimals($true=false)
    {
        $opts = []; //不需要消耗gas
       $this->contract->call('decimals',$opts,$this->cb);
        $js=json_encode($this->cb->result[0],true);
        $shu=json_decode($js,true);
        if ($true){
            return $shu['value'] ;
        }else{
          return  $this->Success($shu['value']);
        }
    }

    /**
     * @return mixed 查询余额
     */
    public function token_BalanceOf($true=false)
    {
        if (!isset($this->json['from'])&&!empty($this->json['from']))return $this->Error('没有提交地址');
        if (!Utils::isAddress($this->json['from']))return $this->Error('不是标准的地址');
        $opts = [];
        $this->contract->call('balanceOf',$this->json['from'],$opts,$this->cb);

        $value=$this->cb->result['balance']->value;
        // 获取token 小数点
        $decimals=$this->token_decimals(true);
        $number=bcdiv($value,bcpow('10',$decimals),$decimals);
        if ($true){
            return $number;
        }else{
            $this->Success($number);
        }
    }
    /**
     * 添加代币种类
     */
    public function addToken()
    {
        if (!$this->Jsons($this->json['abi'])) return $this->Error('不是标准json格式的abi');
        if (!Utils::isAddress($this->json['from']))return$this->Error('不是合约地址');
        if (!file_exists($this->json['path']))return$this->Error('文件路径不存在'.$this->json['path']);
        $paths = $this->json['path'].'/'.$this->json['name'].'_contra.json' ;
        $files = file_exists($paths);
        if ($files){
            return $this->Error('token已存在');
        }
        $data=[
            $this->json['name']=>[
                $this->json['name'] .'_abi'=>$this->json['abi'],
               $this->json['name'].'_address'=>$this->json['from']
            ],
        ];
        if(!is_writable($paths))return $this->Error('没有写入权限'.$paths);
        $myfile = fopen($paths, "w") or die("Unable to open file!");
        fwrite($myfile, json_encode($data));
        fclose($myfile);
        return $this->Success();
    }

    /**
     * @param $name
     * @return bool 获取对应token
     */
    protected function getToken()
    {
        $path = $this->path . '/' . $this->token . '_contra.json';
        $files = file_exists($path);
        if (!$files) $this->Error('没有添加该token');
        $json_string = file_get_contents($path);
        $data = json_decode($json_string, true);
        $this->testAbi = json_encode(json_decode($data[$this->token][$this->token . '_abi'], true));
        $this->address = $data[$this->token][$this->token . '_address'];
    }
        /**
     * 合约执行
     */
    public function initial()
    {
        $this->contract=new \Web3\Contract($this->web3->provider,$this->testAbi);
        $this->contract->at($this->address);
    }
    /**
     * @param $string
     * @return bool 验证是否标准abi
     */
    public  function Jsons($string) {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }
    /**
     * @param $data
     * @return mixed post 请求数据
     */
    public  function post($data)
    {
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL,'http://127.0.0.1:8545');
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }
}