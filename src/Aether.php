<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/18
 * Time: 14:20
 */

namespace Any_ether;

use Web3\Utils;

class Aether
{
    private $value;
    public $Retuns;
    public $cb;
    private $parameter;
    private $web3;
    /**
     * 当前接口方法
     * @return string
     */
    public function __construct($web3,$cb,$method,$parameter)
    {
        $this->Retuns=new Retun();
        $this->cb=$cb;
        $this->parameter=$parameter;
        $this->web3=$web3;
    }

    /**
     * @return array 提供能调用的方法
     */
    public function getMethod()
    {$method=[
            'personal_newAccount',     // 创建新地址
            'eth_getBalance',     //查看余额
            'eth_accounts',       // 获取所以账户
            'eth_blockNumber',    // 获取区块高度
            'eth_estimateGas',    // 获取gas
            'eth_gasPrice',        // 获取price
            'eth_localTransaction',// 获取本地未交易完成
            'eth_getBlockByNumber',// 获取区块信息
            'eth_getTransactionByHash',// 交易hash 获取详情
            'eth_getBlockByHash',       //  区块高度交易详情
            'eth_getTransactionReceipt',//
            'eth_sendTransaction',       //
            'eth_changePassword',        //
        ];
        return $method;
    }
    /**
     *  获取当前节点所以地址
     */
    public function eth_accounts()
    {
        $data='{"jsonrpc":"2.0","method":"eth_accounts","params":[],"id":1}';
        $post=$this->post($data);
        $eqr= json_decode($post,true);
        if (isset($eqr['error'])){
            return $this->Retuns->Error($eqr['error']['message']);
        }
        return $this->Retuns->Success($eqr['result']);
    }
    /**
     * 修改密码
     */
    public function eth_changePassword()
    {
        if (!$this->Jsons($this->parameter))return $this->Retuns->Error('不是标准的json');
        $json=json_decode($this->parameter,true);
        if (!isset($json['from'])||empty($json['from'])) return $this->Retuns->Error('没有修改地址: from');
        if (!isset($json['newpwd'])||empty($json['newpwd'])) return $this->Retuns->Error('没有修改新密码: newpwd');
        if (!isset($json['oldpwd'])||empty($json['oldpwd'])) return $this->Retuns->Error('没有修改原密码: oldpwd');
        $da='{"method":"parity_changePassword","params":["'.$json['from'].'","'.$json['oldpwd'].'","'.$json['newpwd'].'"],"id":1,"jsonrpc":"2.0"}';
        $post=$this->post($da);
        $eqr= json_decode($post,true);
        if (isset($eqr['error'])){
            return $this->Retuns->Error($eqr['error']['message']);
        }
        return $this->Retuns->Success($eqr['result']);
    }

    /**
     *  发起交易
     */
    public function eth_sendTransaction()
    {
        if (!$this->Jsons($this->parameter))return $this->Retuns->Error('不是标准的json');
        $json=json_decode($this->parameter,true);
        if (isset($json['from'])&& empty($json['from'])){$data['from']=$json['from'];}else{return $this->Retuns->Error('发送地址');}
        if (isset($json['to'])&& empty($json['to'])){$data['to']=$json['to'];}else{return $this->Retuns->Error('收货地址');}
        if (isset($json['value'])&& empty($json['value'])){$value=$json['value'];}else{return $this->Retuns->Error('发送数量');}
        if (isset($json['pay'])&& empty($json['pay'])){$pay=$json['pay'];}else{return $this->Retuns->Error('发送地址密码为空');}

        if (isset($json['gas']) && !empty($json['gas'])) $gas='0x'.base_convert($json['gas'],10,16); $data['gas']=$gas;
        if (isset($json['gasPrice'])&& !empty($json['gasPrice'])) $gasPrice='0x'.base_convert($json['gasPrice'],10,16); $data['gasPrice']=$gasPrice;
// 获取发送者余额
        $balance=$this->eth_getBalance(true);
        $tru=($balance>$value)? true :false;
        if (!$tru)return $this->Retuns->Error('账户数量不足');
        $money =  bcmul($value, '1000000000000000000');
        $data['value']='0x'.base_convert($money,10,16);
        if (!$this->unlock($data['from'],$pay)) return $this->Retuns->Error('发送地址密码错误');
        $json_array=json_encode($data);
        $dat='{"method":"parity_composeTransaction","params":['.$json_array.'],"id":1,"jsonrpc":"2.0"}';
        $ret=$this->post($dat);  //签名
        $eqr= json_decode($ret,true);
        $qian['from']=$eqr['result']['from'];
        $qian['to']=$eqr['result']['to'];
        $qian['gas']=$eqr['result']['gas'];
        $qian['gasPrice']=$eqr['result']['gasPrice'];
        $qian['nonce']=$eqr['result']['nonce'];
        $qian['value']=$eqr['result']['value'];
        $qian_json=json_encode($qian);
        $qian_data='{"method":"personal_sendTransaction","params":['.$qian_json.',"'.$pay.'"],"id":1,"jsonrpc":"2.0"}';
        $ret_data=$this->post($qian_data);  //发起交易
        $json_data= json_decode($ret_data,true);
        if (!isset($json_data['result']))return $this->Retuns->Error($json_data['error']['message']);
        return $this->Retuns->Success($json_data['result']);
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
     * @return mixed 按事务哈希返回事务的接收
     */
    public function eth_getTransactionReceipt()
    {
        if (!$this->parameter)return $this->Retuns->Error('没有提交事务hash');
        $this->web3->eth->getTransactionReceipt($this->parameter,$this->cb);
        if (!empty($this->cb))return $this->Retuns->Error($this->cb->error);
        return $this->Retuns->Success($this->cb->result);
        }
    /**
     * @return mixed 根据区块hash获取交易数据
     */
    public function eth_getBlockByHash()
    {
        if (!$this->parameter)return $this->Retuns->Error('参数不全:blockHash');
        if (!Utils::isHex($this->parameter))return $this->Retuns->Error('不是标准hash');
        $this->web3->eth->getBlockByHash($this->parameter,true,$this->cb);
        if (!empty($this->cb))return $this->Retuns->Error($this->cb->error);
        return $this->Retuns->Success($this->cb->result);
        }

    /***
     * @return mixed  根据hash获取交易信息
     */
    public function eth_getTransactionByHash()
    {
        if (!$this->parameter) return $this->Retuns->Error('参数不全:hash');
        if (!Utils::isHex($this->parameter)) return $this->Retuns->Error('不是标准hash');
        $this->web3->eth->getTransactionByHash($this->parameter,$this->cb);
        if (!empty($this->cb))return $this->Retuns->Error($this->cb->error);
        return $this->Retuns->Success($this->cb->result);
    }
    /**
     * @return mixed  根据区块高度获取交易数据
     */
    public function eth_getBlockByNumber()
    {
        if (!$this->parameter) return $this->Retuns->Error('参数不全:blockNumber');
        if (!is_int((int)$this->parameter)) return $this->Retuns->Error('区块请输入数字:blockNumber');
        $number='0x'.base_convert($this->parameter,10,16);
        $this->web3->eth->getBlockByNumber($number,true,$this->cb);
        if (!empty($this->cb))return $this->Retuns->Error($this->cb->error);
        return $this->Retuns->Success($this->cb->result);
    }

    /**
     *  获取本地未完成的交易数据
     */
    public function eth_localTransaction()
    {
        $data = '{"method":"parity_localTransactions","params":[],"id":1,"jsonrpc":"2.0"}';
        $re=$this->post($data);
        $json=json_decode($re,true);
        return $this->Retuns->Success($json);
    }
    /**
     * @return mixed 获取price
     */
    public function eth_gasPrice()
    {
        $this->web3->eth->gasPrice($this->cb);
        if (!empty($this->cb))return $this->Retuns->Error($this->cb->error);
        return $this->Retuns->Success($this->cb->result);
    }
    /**
     *  获取gas
     */
    public function eth_estimateGas()
    {
        if (!$this->Jsons($this->parameter))return $this->Retuns->Error('不是标准的json');
        $datas='{"method":"eth_estimateGas","params":['.$this->parameter.',"latest"],"id":1,"jsonrpc":"2.0"}';
        $ret=$this->post($datas);
        $json=json_decode($ret,true);
        if (isset($json['result'])){
            $number= base_convert(substr($json['result'],2),16,10);
            return $this->Retuns->Success($number);
        }else{
            return $this->Retuns->Error();
        }
    }
    /**
     * @return string 创建地址
     */
    public function eth_newAccount()
    {
        if (!$this->parameter) return $this->Retuns->Error('没有输入密码');
        if (!is_string($this->parameter)) return $this->Retuns->Error('参数不是字符串');
        $this->web3->personal->newAccount($this->parameter,$this->cb);
        if (!empty($this->cb))return $this->Retuns->Error($this->cb->error);
        return $this->Retuns->Success($this->cb->result);
    }

    /**
     *  获取账户余额
     */
    public function eth_getBalance($true=false)
    {
        if (!$this->parameter) return $this->Retuns->Error('没有输入地址');
        if (!is_string($this->parameter)) return $this->Retuns->Error('参数不是字符串');
        if (!Utils::isAddress($this->parameter)) return $this->Retuns->Error('不是标准地址');
        $this->web3->eth->getBalance($this->parameter,$this->cb);
        if (!empty($this->cb->error)){
            if ($true){return false;}else{return $this->Retuns->Error($this->cb->error);}
        }
        $num=$this->cb->result->toString();
        $number=bcdiv($num,'1000000000000000000',18);
        if ($true){
            return $number;
        }else{
            return $this->Retuns->Success($number);
        }
    }
    /**
     * 获取区块高度
     */
    public function eth_blockNumber()
    {
        $this->web3->eth->blockNumber($this->cb);
        if (!empty($this->cb))return $this->Retuns->Error($this->cb->error);
        return $this->Retuns->Success($this->cb->result);
    }
    public function get()
    {
        return $this->value;
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