<?php

namespace Pay\Controller;

use Org\Util\WxH5Pay;

class DahengzkController extends PayController {

    public function __construct() {
        parent::__construct();
    }

    public function Pay($array) {
        $orderid = I("request.pay_orderid");
        $body = I('request.pay_productname');
        $notify_ip = $return['notify_ip'];
        $notifyurl = $this->_site . 'Pay_Dahengzk_notifyurl.html'; //异步通知
        $callbackurl = $this->_site . 'Pay_Dahengzk_callbackurl.html'; //返回通知

        $parameter = array(
            'code' => 'Dahengzk', // 通道名称
            'title' => '大亨用转卡通道',
            'exchange' => 1, // 金额比例
            'gateway' => '',
            'orderid' => '',
            'out_trade_id' => $orderid,
            'body' => $body,
            'channel' => $array
        );

        // 订单号，可以为空，如果为空，由系统统一的生成
        $return = $this->orderadd($parameter);
        $GLOBALS['autth']= I('request.pay_attach');

        $pay_memberid = "1381983703";   //商户ID
        $pay_orderid = $return['orderid'];    //订单号
        $pay_amount = $return['amount'];    //交易金额
        $pay_applydate = date("Y-m-d H:i:s");  //订单时间
        $pay_notifyurl = $notifyurl;   //服务端返回地址
        $pay_callbackurl = $callbackurl;  //页面跳转返回地址
        $Md5key = "W0P9mhHhTMbjB6Tdyrn4YQfS9aRCb0OF";   //密钥
        $tjurl = "http://api.caifupay.vip/v2/user-jobs/create-receive-job";   //提交地址
        $pay_bankcode = $return['gateway'];   //银行编码
        //扫码
        $native = array(
    'app_id'=>$pay_memberid,
	'job_number'=>$pay_orderid,
	'amount'=>$pay_amount,
	'bank_account_type'=>'支付宝',
	'notify_url'=>$pay_notifyurl,
	'return_url'=>$pay_callbackurl,
	'notify_method'=>'POST',
	'handle_success_data'=>'success',   
        );
      
        ksort($native);
        $md5str = "";
        foreach ($native as $key => $val) {
            $md5str = $md5str . $key . "=" . $val . "&";
        }

        $sign = strtoupper(md5($md5str . "key=" . $Md5key));
        $native["sign"] = $sign;
     	$postData = json_encode($native);
        $curl = curl_init();
        $headerArray =array("Content-type:application/json;charset='utf-8'","Accept:application/json");
        curl_setopt($curl, CURLOPT_URL, $tjurl);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // stop verifying certificate
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl,CURLOPT_HTTPHEADER,$headerArray);
       curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
        curl_setopt($curl, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        $data = curl_exec($curl);
        curl_close($curl);
        $json = json_decode($data,true);
        if($GLOBALS['autth']=="json"){
        	echo $data;
        }else{
 		$url = $json['data']['jump_url'];
 		//echo $data;
 	if($url){
			header("Location:$url");die;
		}
		var_dump('<pre>',$data);die;
    }
}
    public function callbackurl() {
       exit('ok');
        $Order = M("Order");
        $pay_status = $Order->where(['pay_orderid' => $_REQUEST["orderid"]])->getField("pay_status");
        $callbackurl = $Order->where(['pay_orderid' => $_REQUEST["orderid"]])->getField("pay_callbackurl");
        //var_dump($callbackurl);die;
        if ($pay_status <> 0) {
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

        if($_SERVER['REMOTE_ADDR'] != "39.109.112.236"){
        	$orderip = M('order')->where(array('pay_orderid'=>$_REQUEST['orderid']))->save(array('yichang'=>'1','yichangip'=>$_SERVER['REMOTE_ADDR']));
        	exit('禁止非法回调！');
        }
        
            if ($_REQUEST["is_finished"] == "1") {
                   $str = "交易成功！订单号：".$_REQUEST["job_number"];
                   file_put_contents("success.txt",$str."\n", FILE_APPEND);
        		$this->EditMoney($_REQUEST['job_number'], 'Dahengzk', 0);
                   exit("OK");
            }
    
  
  
    
}
}

?>