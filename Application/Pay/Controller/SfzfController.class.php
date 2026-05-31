<?php

namespace Pay\Controller;


use Org\Util\EpayCore;

class SfzfController extends PayController {

    public function __construct() {
        parent::__construct();
    }

    public function Pay($array) {
        $orderid = I("request.pay_orderid");
        $body = I('request.pay_productname');
        $notifyurl = $this->_site . 'Pay_Sfzf_notifyurl.html'; //异步通知
        $callbackurl = $this->_site . 'Pay_Sfzf_callbackurl.html'; //返回通知

        $parameter = array(
            'code' => 'Sfzf', // 通道名称
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
       
        $native=array(
            "pid" => $pay_memberid,
        	"type" => 'alipay',
        // 	'uid'=>'alipaysl',
        	"notify_url" => $pay_notifyurl,
        	"return_url" => $callbackurl,
        	"out_trade_no" => $pay_orderid,
        	"name" => 'yifeng',
        	'timestamp'=>time(),
        	"money"	=> $pay_amount,
        	'clientip'=>$_SERVER['REMOTE_ADDR'],
            );
            //   var_dump($native);die();
            //  var_dump($epay_config);die();
            $epay = new EpayCore($epay_config);
$json = $epay->pagePay($native);
  var_dump($json);die();

 if($json['code']==0){
     $url=$json['qrcode'];
      header("Location:$url");die;//如果商户没有传type=json值，就直接跳转到支付地址
 }else{
      $this->json_msg("",$return,$json['msg']);//如果没有下单成功，就给url一个空值，报错误信息给下游
 }
echo $html_text;
   
              
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
    	 file_put_contents(dirname( __FILE__ ).'/notifylog/~NOTIFY-log-'. date('Y-m-d',$_SERVER['REQUEST_TIME']) . '.log','sfzf：'.http_build_query($_REQUEST)."\n", FILE_APPEND);
    	
    	$this->safe_WAF($_REQUEST); //回调字符串安全检查，检查有无sql注入命令
    // 	$_REQUEST=explode($_REQUEST,"&");
//：pid=1000&trade_no=2025123103422684065&out_trade_no=20251231034221100555&type=0&name=product&money=501&trade_status=TRADE_SUCCESS
		$returnArray = array( // 返回字段
            "pid" => $_REQUEST["pid"], // 商户ID
            "money" =>  $_REQUEST["money"], // 交易金额
            "out_trade_no" =>  $_REQUEST["out_trade_no"], // 订单号
            "trade_no" =>  $_REQUEST["trade_no"], //上游订单号
            "type" =>  $_REQUEST["type"], // 金额(分)
            "trade_status" =>  $_REQUEST["trade_status"], // 金额(分)
            "sign_type" =>  $_REQUEST["sign_type"], // 金额(分)
            // "sign" =>  $_REQUEST["sign"], // 金额(分)
            'name'=>$_REQUEST["name"], // 金额(分)
        );
          $md5key=$this->getkey($returnArray['out_trade_no']);//获取后台保存的商户密钥
        // var_dump($md5key);
        $sign = $this->getSign($returnArray,$md5key);
        // var_dump($sign);
        if ($sign == $_REQUEST["sign"]) {
        	$this->EditMoney($returnArray['out_trade_no'], 'Sfzf', 0);
            exit("success"); 
        }else{
            exit('sign error');
        }
    }
   
   
    // 服务器点对点返回
    public function notifyurl2() {
    	 file_put_contents(dirname( __FILE__ ).'/notifylog/~NOTIFY-log-'. date('Y-m-d',$_SERVER['REQUEST_TIME']) . '.log','SFZF：'.http_build_query($_REQUEST)."\n", FILE_APPEND);
    	 //pid=1004&trade_no=2025011217293037370&out_trade_no=20250112172929579910&type=alipay&name=product&money=10&trade_status=TRADE_SUCCESS&sign=449808d41349e963cd76213faca57c9a&sign_type=MD5
    	$this->safe_WAF($_REQUEST); //回调字符串安全检查，检查有无sql注入命令
    // 	$_REQUEST=explode($_REQUEST,"&");
		$returnArray=$_REQUEST;
		unset($returnArray['sign']);
		unset($returnArray['sign_type']);
		unset($returnArray['think_language']);
		unset($returnArray['PHPSESSID']);
		var_dump($returnArray);
          $md5key=$this->getkey($returnArray['out_trade_no']);//获取后台保存的商户密钥
$rawPrivateKey = 'MIIEuwIBADANBgkqhkiG9w0BAQEFAASCBKUwggShAgEAAoIBAQCqSHkLJdU4YlE53NOz0sKtxBQdFkRssc+yUUHrI2fBSGBzigFcox0AboikBOLrd5FW1oqrnkck68L6xSncr1IsNVWruDqld59qmq/jZom0LmTGXud1VG38apdETs0fYomzrnBK1P2T6MoaI+RnGQJMW0XWPo4zVByazy43ZB0SpXqVgcd7O4tnz2T9TMBRPgHccKfGkzjDh2B/NG7b4K1xBoED7IegJpnRzagWshR0qyzaw4blSMqMehfqYAJtFA57WlNg+oSbI2e78b9V6wEe55xlTaIUWYugBKxo3hieZ6Z7iX3LTuFoUfP+2oRsvNbhuERgPb5S6dv8zejWlN/1AgMBAAECgf8LTsS6+Mgv9ldugDuOtXA4Gc08IT5p+WTRcpPuCWvaafP9uCxe+nXykWSBf9GR0V7VZWnP+7K9wOoxvOYKwZmzVddaj+FVG5x6d8s2TpjWXj5S7fpw0Cp9mJZy8sisTN7YD71lOr+cEtlY4wlHz2FXsLfygjTvM6ayoQ9wnjVQB5FatmI0qlPW7zzAbt/ZAPHoIw6lq5gW+Zutu0/TZUzLza4BoBKhPFsA4IRzz/hB31BHrVPULxepfKyjPcfisJ9tnlqIBgVx3odrV1gFzxxtKVVzD132q5BdTMwJ3HiIL9nVrV0VZYzN89Ue9TKT26PLkD0PRihYufW0EuKt7a0CgYEA2ippN3LzVB9x1dnoJzMJUsKJOGEE918hLtDLYvWDiRdbPvzIVCGQ+P6hPEK0vTGKad0cX2/ATZ+3MbVRxolKN9NI0BAVU5PRprb9PBta4AxX4PTgXVcUIJ590mNqjW+nIo0Ok0ltUKS70m8GTflUSQvzKDp9UEglA4Akc+P1aksCgYEAx9BJr2Hl1VN6hM4A0SAKoWP3CTx7kEzFGMdpMK6IbMGF+ZkjAnnwM8+sG5LJzq7q/O68H9c+YSBFXMzp0JFn1OCrYvDc3q6dcWFDX6GsO6UL0FtwCCYfW2z84/uJ/btvdIwsotDI9yVua8ApxPoZ/HhbMOHMN4T8d6lVS3z0dr8CgYAe0MjF3UXDhyGELGBfURUrDHFndkTGUDiWrUVdOAKZVaQ81GXThF4+3XCW23E+HAZKB8JfNKC8FihBLDRdz7ydAAoT4YGxqXp+ivBgEhkW9odfLheW95rPNLPfCM3fJns8JSJ+6Ws4bdxdz/LbBrHCE58H+qMCuP4JbYs4l2Vt2wKBgAL6ju6nZfa9LNln/MkhUic/x0IC/dCT5GhPKLlKEMyWQfoLl2MFEFilYupyUMHdB7HHmVRcMBjgk0gj4eRzFnos80EhWBmVvtEe4xM85MVq23c6tbvZXaRORqLbcB4xOiMhp9Sxih1tGG1Qyw6dr998p9ddtl6pg94Azz212isfAoGBAMRaPrmdDhmaNCShbxMZWt1bFOMcJ1iHCF56oDVgljIU2ACjWMHuTw6yqJghBqD8RyBgp5Sm9sTKFfWIMH5fFnvZ6emouI82N6pz6ttIWCM1YIPZg+MCEaXiJ2UrkAyYK1wn8HsdYDZ7fUpJ+Tq1r5LxfqGRamlNXuQO1KECH3u8';

$rawPublicKey = 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA6phsiIDjPlOX03Tj0x6ffp+jEzDTHVpgN8M1PUMQFiA7nx06C0mVqEcAoUbTngfy+Fbu3muDbau+/41Vup4Q7T1013m/784O1qNe6kxW7Jewtpxk8Rrwi2Ghx3MQUFqHsmeDjqZRkbvdUKGGZWKSJOQThdZpoh1xaVVoq2P6gnSXryMI98XOXYA0yNmEtqPGJ2GPPrwE23rw73BU1NdWd45g74QL8+8k4t2Z9tzhjQ65ujahe+lurN+iJhQXlZzNAbFN4sYw84CFTFMGLI1YxAhAtLfZBcjQxccp6MwI95mrJtAtp7XXqVL7Of9R6EWVGK/xGLG0Cl5uNU7wr4PIwQIDAQAB';
        $sign = $this->rsaSha256Verify($returnArray,$_REQUEST["sign"],$rawPublicKey);
        $sign2= $this->rsaSha256Sign($returnArray,$rawPrivateKey);
         var_dump($sign);
         var_dump($sign2);
        if ($sign == $_REQUEST["sign"]) {
        	$this->EditMoney($returnArray['out_trade_no'], 'Sfzf', 0);
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
		$signstr .= $key;
// 		var_dump($signstr);
		$sign = md5($signstr);
// 			var_dump($sign);
		return $sign;
	}
}



?>