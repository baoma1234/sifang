<?php

namespace Pay\Controller;


use Org\Util\EpayCore;

class XxhController extends PayController {

    public function __construct() {
        parent::__construct();
    }

    public function Pay($array) {
        $orderid = I("request.pay_orderid");
        $body = I('request.pay_productname');
        $notifyurl = $this->_site . 'Pay_Xxh_notifyurl.html'; //异步通知
        $callbackurl = $this->_site . 'Pay_Xxh_callbackurl.html'; //返回通知

        $parameter = array(
            'code' => 'Xxh', // 通道名称
            'title' => 'zfbh5',
            'exchange' => 1, // 金额比例
            'gateway' => '',
            'out_trade_id' => $orderid,
            'body' => $body,
            'channel' => $array
        );
    
        // 订单号，可以为空，如果为空，由系统统一的生成
        $return = $this->orderadd($parameter);

        $pay_memberid = $return['mch_id'];   //商户ID
        $pay_orderid = $return['orderid'];    //订单号
        $pay_amount = $return['amount'];    //交易金额
        $pay_applydate = date("Y-m-d H:i:s");  //订单时间
        $pay_notifyurl = $notifyurl;   //服务端返回地址
        $pay_callbackurl = $callbackurl;  //页面跳转返回地址
        $Md5key = $return['signkey'];   //密钥
        // $tjurl = "https://hzf88888.cc/submit.php";   //提交地址
        $pay_bankcode = $return['gateway'];   //银行编码
        
        $epay_config=array();
        //支付接口地址
$epay_config['apiurl'] =$return['gateway'];

//商户ID
$epay_config['pid'] = $pay_memberid;

//商户密钥
$epay_config['key'] = $return['signkey'];
        //扫码
        $extra = "";   
       $timestamp = round(microtime(true) * 1000);
       $pid=$return['syid'];
       
        $native=array(
            "mchId" => $pay_memberid,
        	"wayCode" => $pid,
        	"subject" => '支付宝全层原生',
        	"outTradeNo" => $pay_orderid,
        	"amount"	=> $pay_amount*100,
        	'clientIp'=>$_SERVER['REMOTE_ADDR'],
        	"notifyUrl" => $pay_notifyurl,
        	'reqTime'=>$timestamp,
        	
        	
            );
            //   var_dump($native);die();
            //  var_dump($epay_config);die();
            $native['sign']=$this->getSign($native,$return['signkey']);
              $postData=json_encode($native);
             
              $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $return['gateway']);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // stop verifying certificate
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($curl, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        $data = curl_exec($curl);
        curl_close($curl);
       // echo $data;die();
       $json=json_decode($data,1);
//   var_dump($json);die();

 if($json['code']==0){
     $url=$json['data']['payUrl'];
     
     if($_REQUEST['type'] == 'json'){
            $msg='{"status":"1","msg":"下单成功","pay_amount":"'.$pay_amount.'","pay_orderid":"'.$pay_orderid.'","payUrl":"'.$url.'"}';
 		    exit($msg);
      }else{
           header("Location:$url");die;
      }
 }else{
      $this->json_msg("",$return,$json['message']);//如果没有下单成功，就给url一个空值，报错误信息给下游
 }

              
    }

    public function callbackurl() {

        $Order = M("Order");
          $orderid=I('request.gp_order/s'); 
        $pay_status = $Order->where(['pay_orderid' => $orderid])->getField("pay_status");
        $callbackurl = $Order->where(['pay_orderid' => $orderid])->getField("pay_callbackurl");
        if ($pay_status <> 0) {
            header("location:$callbackurl");
            die;
            exit('交易成功！');
        } else {
            header("location:$callbackurl");
            die;
        }
    }
   
    // 服务器点对点返回
    public function notifyurl() {
        $json = file_get_contents('php://input');
    // 将JSON字符串转换为PHP对象或数组
    $data = json_decode($json, true);
        $logName=dirname( __FILE__ ).'/notifylog/~NOTIFY-log-'. date('Y-m-d',$_SERVER['REQUEST_TIME']) . '.log';
    	 file_put_contents(dirname( __FILE__ ).'/notifylog/~NOTIFY-log-'. date('Y-m-d',$_SERVER['REQUEST_TIME']) . '.log','Xxh：'.json_encode($data)."\n", FILE_APPEND);


    	
    	$this->safe_WAF($data); //回调字符串安全检查，检查有无sql注入命令
    // 	$_REQUEST=explode($_REQUEST,"&");
//：{"mchId":"M1772268609","tradeNo":"XH20260301154204445665691","outTradeNo":"20260301154204995250","originTradeNo":1,"amount":1000,"subject":"\u652f\u4ed8\u5b9d\u5168\u5c42\u539f\u751f","notifyTime":1772351134227,"state":1,"sign":"4dd20a48ca533c5a946393dbc7330af5"}
// $json='{"mchId":"M1772268609","tradeNo":"XH20260301154204445665691","outTradeNo":"20260301154204995250","originTradeNo":1,"amount":1000,"subject":"\u652f\u4ed8\u5b9d\u5168\u5c42\u539f\u751f","notifyTime":1772351134227,"state":1,"sign":"4dd20a48ca533c5a946393dbc7330af5"}';
//     $data = json_decode($json, true);
//     var_dump($data);


		$returnArray = array( // 返回字段
            "mchId" => $data["mchId"], // 商户ID
             "tradeNo" =>  $data["tradeNo"], // 订单号
             "outTradeNo" =>  $data["outTradeNo"], // 订单号
            "amount" =>  $data["amount"], // 交易金额
           'subject'=>$data["subject"], // 金额(分)
            "state" =>  $data["state"], // 金额(分)
            "originTradeNo" =>  $data["originTradeNo"], //上游订单号
            "notifyTime" =>  $data["notifyTime"], // 金额(分)
            
        );
          $md5key=$this->getkey($returnArray['outTradeNo']);//获取后台保存的商户密钥
         
        $sign = $this->getSign($returnArray,$md5key);
        //  var_dump($sign);
        // var_dump($md5key);die();
        if ($sign == $data["sign"]) {
        	$this->EditMoney($returnArray['outTradeNo'], 'Xxh', 0);
            exit("success"); 
        }else{
            exit('sign error');
        }
    }
   

    public function rsaSha256Verify($param, $sign, $rawPublicKey) {
        	ksort($param);
		reset($param);
		$signstr = '';
	
		foreach($param as $k => $v){
			if($k != "sign" && $k != "sign_type" && $v!=''){
				$signstr .= $k.'='.$v.'&';
			}
		}
		$signstr = substr($signstr,0,-1);
    // 1. 格式化公钥：补全PEM头/尾 + 64字符换行
    $publicKey = "-----BEGIN PUBLIC KEY-----\n" . wordwrap($rawPublicKey, 64, "\n", true) . "\n-----END PUBLIC KEY-----";
    
    // 2. 加载公钥
    $keyResource = openssl_pkey_get_public($publicKey);
    if (!$keyResource) {
        throw new Exception("公钥加载失败：" . openssl_error_string());
    }
    
    // 3. 对签名进行Base64解码（因为签名是Base64编码的）
    $signature = base64_decode($sign);
    if ($signature === false) {
        throw new Exception("签名格式错误，无法进行Base64解码");
    }
    
    // 4. 执行SHA256WithRSA验签
    $verifyResult = openssl_verify(
        $signstr,                  // 原始待验签字符串
        $sign,             // 解码后的二进制签名
        $keyResource,           // 公钥资源
        OPENSSL_ALGO_SHA256     // SHA256算法
    );
    
    // 5. 释放公钥资源
    openssl_free_key($keyResource);
    // var_dump($verifyResult);
    // 6. 判断验签结果
    if ($verifyResult === 1) {
        return true; // 验签通过
    } elseif ($verifyResult === 0) {
        return false; // 验签失败（签名不匹配）
    } else {
        // -1表示验签过程出错
        throw new Exception("验签过程出错：" . openssl_error_string());
    }
}
    
    public function rsaSha256Sign($param, $rawPrivateKey) {
    		ksort($param);
		reset($param);
		$signstr = '';
	
		foreach($param as $k => $v){
			if($k != "sign" && $k != "sign_type" && $v!=''){
				$signstr .= $k.'='.$v.'&';
			}
		}
		$signstr = substr($signstr,0,-1);
    // 1. 格式化私钥：补全PEM头/尾 + 64字符换行
    $privateKey = "-----BEGIN PRIVATE KEY-----\n" . wordwrap($rawPrivateKey, 64, "\n", true) . "\n-----END PRIVATE KEY-----";
    // var_dump($privateKey);die();
    // 2. 加载私钥
    $keyResource = openssl_pkey_get_private($privateKey);
    if (!$keyResource) {
        throw new Exception("私钥加载失败：" . openssl_error_string());
    }
    
    // 3. 执行SHA256WithRSA签名
    $signature = '';
    $signSuccess = openssl_sign(
        $signstr,                  // 待签名数据
        $signature,             // 输出签名（二进制）
        $keyResource,           // 私钥资源
        OPENSSL_ALGO_SHA256     // SHA256算法
    );
    
    // 4. 释放私钥资源
    openssl_free_key($keyResource);
    
    if (!$signSuccess) {
        throw new Exception("签名计算失败：" . openssl_error_string());
    }
    
    // 5. 二进制签名转Base64编码（通用传输格式）
    return base64_encode($signature);
}

/**
 * 使用RSA私钥对字符串进行SHA256WithRSA签名
 * @param string $privateKey 商户私钥（PEM格式字符串）
 * @param string $data 待签名的原始字符串
 * @return string|null 签名结果（Base64编码），失败返回null
 * @throws Exception 私钥无效或签名失败时抛出异常
 */



    	private function getSign($param,$key){
uksort($param, function($keyA, $keyB) {
    $len = max(strlen($keyA), strlen($keyB));
    // 逐个字符比较键的ASCII码
    for ($i = 0; $i < $len; $i++) {
        $asciiA = isset($keyA[$i]) ? ord($keyA[$i]) : 0;
        $asciiB = isset($keyB[$i]) ? ord($keyB[$i]) : 0;
        if ($asciiA != $asciiB) {
            return $asciiA - $asciiB; // 升序：小ASCII在前
        }
    }
    return 0;
});

		$signstr = '';
	
		foreach($param as $k => $v){
			if($k != "sign" && $k != "sign_type" && $v!=''){
				$signstr .= $k.'='.$v.'&';
			}
		}
		$signstr = substr($signstr,0,-1);
		$signstr .= '&key='.$key;
// 		var_dump($signstr);
		$sign = md5($signstr);
// 			var_dump($sign);
		return $sign;
	}
}



?>