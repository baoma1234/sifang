<?php

namespace Pay\Controller;

use Org\Util\WxH5Pay;

class LuoyunController extends PayController {

    public function __construct() {
        parent::__construct();
    }

    public function Pay($array) {
        $orderid = I("request.pay_orderid");
        $body = I('request.pay_productname');
        $notify_ip = $return['notify_ip'];
        $notifyurl = $this->_site . 'Pay_Luoyun_notifyurl.html'; //异步通知
        $callbackurl = $this->_site . 'Pay_Luoyun_callbackurl.html'; //返回通知

        $parameter = array(
            'code' => 'Luoyun', // 通道名称
            'title' => '落云支付',
            'exchange' => 1, // 金额比例
            'gateway' => '',
            'orderid' => '',
            'out_trade_id' => $orderid,
            'body' => $body,
            'channel' => $array
        );

        // 订单号，可以为空，如果为空，由系统统一的生成
        $return = $this->orderadd($parameter);


        $pay_memberid = "M1654497453";   //商户ID
        $pay_orderid = $return['orderid'];    //订单号
        $pay_amount = $return['amount'];    //交易金额
        $pay_applydate = date("Y-m-d H:i:s");  //订单时间
        $pay_notifyurl = $notifyurl;   //服务端返回地址
        $pay_callbackurl = $callbackurl;  //页面跳转返回地址
        $Md5key = "mckDlVqhEM3WawEiiOJeuieDAiAyg5ayJd7Cn62gBHhcLzEovQBSty9dFaCWcorXNCtmJiypSr4ge3iMwAYoDuTE5tLl99ORoh6xvfjUpriTekW4odCMBesopLwPX0cE";   //密钥
        $tjurl = "https://api.hbeepay.com/api/pay/unifiedOrder";   //提交地址
        $pay_bankcode = $return['gateway'];   //银行编码
        //扫码
        
    list($t1, $t2) = explode(' ', microtime());

    $getMillisecond= (float)sprintf('%.0f',(floatval($t1)+floatval($t2))*1000);
 
       $native = array(
            "mchNo" => $pay_memberid,
            "appId" => '629da0ad900896d27ca5e196',
            "mchOrderNo" => $pay_orderid,
            "wayCode" => "ALI_WAP",//$pay_bankcode,
            "amount" => $pay_amount*100,
            "currency" => 'cny',
            "clientIp"=>$_SERVER['REMOTE_ADDR'],
            "subject" => 'pay',
            "body" => 'pay',
            "notifyUrl" => $pay_notifyurl,
            "returnUrl" => $pay_callbackurl,
            "reqTime" => $getMillisecond,
            "version"=>"1.0",
            "signType" => 'MD5',
        );
        ksort($native);
        $md5str = "";
        foreach ($native as $key => $val) {
            $md5str = $md5str . $key . "=" . $val . "&";
        }
        $sign = strtoupper(md5($md5str . "key=" . $Md5key));
        $native["sign"] = $sign;
  		$postData = http_build_query($native);
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $tjurl);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // stop verifying certificate
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
        curl_setopt($curl, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        $data = curl_exec($curl);
        curl_close($curl);
        $json = json_decode($data,true);
        //var_dump($data);
       // exit;
         if($json['retCode'] =='0'){
 		$url = $json['payUrl'];
        
 		if($url){
 		    if($_REQUEST['type'] == 'json'){
 		      $msg='{"code":"1","msg":"下单成功","pay_amount":"'.$pay_amount.'","pay_orderid":"'.$pay_orderid.'","payUrl":"'.$url.'"}';
 		      exit($msg);
 		    }else{
 		      header("Location:$url");die;
 		    }
		}else{
		     $msg='{"code":"0","msg":"获取支付链接失败","content":""}';
		     exit($msg);
		}
	
       }else{
          
           file_put_contents(dirname( __FILE__ ).'/notifylog/~OrderERROR-log-'. date('Y-m-d',$_SERVER['REQUEST_TIME']) . '.log','系统订单号：'.$pay_orderid.'落云支付错误信息-源串：'.$data.'错误内容:'.$json['msg']."\n", FILE_APPEND);
            $msg='{"code":"0","msg":"下单失败","content":"'.$data.'"}';
		     exit($msg);
       }
 	
    }

    public function callbackurl() {

        $Order = M("Order");
          $orderid=I('request.orderid/s'); 
        $pay_status = $Order->where(['pay_orderid' => $orderid])->getField("pay_status");
        $callbackurl = $Order->where(['pay_orderid' => $orderid])->getField("pay_callbackurl");
        //var_dump($callbackurl);die;
        if ($pay_status <> 0) {
           // $this->EditMoney($_REQUEST['orderid'], 'Ddzzzbj', 1);
            header("location:$callbackurl");
            die;
            exit('交易成功！');
        } else {
            header("location:$callbackurl");
            die;
        }
    }
    //获取真实IP
	public function getIp(){
		if (getenv("REMOTE_ADDR") && strcasecmp(getenv("REMOTE_ADDR"), "unknown")){
			$ip = getenv("REMOTE_ADDR");
		}else if (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], "unknown")){
			$ip = $_SERVER['REMOTE_ADDR'];
		}else{
			$ip = '0.0.0.0';
		}
		return $ip;
	}

    // 服务器点对点返回
    public function notifyurl() {
    	 file_put_contents(dirname( __FILE__ ).'/notifylog/~NOTIFY-log-'. date('Y-m-d',$_SERVER['REQUEST_TIME']) . '.log','落云支付回调：'.http_build_query($_REQUEST)."\n", FILE_APPEND);
	
		$returnArray = array( // 返回字段
            "payOrderId" => $_REQUEST["payOrderId"], // 商户ID
            "mchId" => $_REQUEST["mchId"], // 商户ID
            "appId" => $_REQUEST["appId"], // 商户ID
            "productId" => $_REQUEST["productId"], // 商户ID
            "mchOrderNo" => $_REQUEST["mchOrderNo"], // 商户ID
            "amount" => $_REQUEST["amount"], // 商户ID
            "income" =>  $_REQUEST["income"], // 交易时间
            "status" => $_REQUEST["status"], // 商户ID
            "channelOrderNo" =>  $_REQUEST["channelOrderNo"], // 订单号
            "channelAttach" =>  $_REQUEST["channelAttach"], // 交易金额
           "param2" =>  $_REQUEST["param2"], // 支付流水号
            "paySuccTime" => $_REQUEST["paySuccTime"],
            "reqTime" => $_REQUEST["reqTime"],
            "backType" => $_REQUEST["backType"],
        );
        $Md5key = "LQNLXBCWYOYF3DEAZ9NIWH1FYNFQW6R5XSKKFXS0FE4LK2GACGM1QYPMY2AKJ80TRFY5C68IGS1QRUTBQREPKM4QHYI4FGW7KR4RPRTT4IRCU4UIHGW5BT91QEMSCPAV";   //密钥
        ksort($returnArray);
        $md5str = "";
        foreach ($returnArray as $key => $val) {
           if($val){
           	 $md5str = $md5str . $key . "=" . $val . "&";
           }
        }
        $sign = strtoupper(md5($md5str . "key=" . $Md5key));
          if($_SERVER['REMOTE_ADDR'] == '103.146.100.157'||$_SERVER['REMOTE_ADDR'] == "122.114.8.176"){
        if ($sign == $_REQUEST["sign"]) {
            if ($_REQUEST["status"] == "2") {
                   $this->EditMoney($_REQUEST['mchOrderNo'], 'Luoyun', 0);
                   exit("success");
            }
        }
          }//ip效验
        else{
        	 exit($_SERVER['REMOTE_ADDR']."回调IP不合法,或回调IP不是指定IP");
       }

    }
}

?>