<?php

namespace Pay\Controller;


use Org\Util\EpayCore;

class HuazfController extends PayController {

    public function __construct() {
        parent::__construct();
    }

    public function Pay($array) {
        $orderid = I("request.pay_orderid");
        $body = I('request.pay_productname');
        $notifyurl = $this->_site . 'Pay_Huazf_notifyurl.html'; //异步通知
        $callbackurl = $this->_site . 'Pay_Huazf_callbackurl.html'; //返回通知

        $parameter = array(
            'code' => 'Huazf', // 通道名称
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
        $native = array(
            "gp_mchid" => $pay_memberid,
            "gp_notify" => $pay_notifyurl,
            "gp_order" => $pay_orderid,
            "gp_price" =>$pay_amount,
            "gp_rand" => rand(100,999),
            "gp_type" => "0",
          // "gp_extra" => "pay",
        );
        $native=array(
            "pid" => $pay_memberid,
        	"type" => 'alipay',
        	"notify_url" => $pay_notifyurl,
        	"return_url" => $callbackurl,
        	"out_trade_no" => $pay_orderid,
        	"name" => 'yifeng',
        	"money"	=> $pay_amount,
        	'clientip'=>$_SERVER['REMOTE_ADDR'],
            );
            //   var_dump($native);die();
            //  var_dump($epay_config);die();
            $epay = new EpayCore($epay_config);
$json = $epay->apiPay($native);


 if($json['code']==1){
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
    	 file_put_contents(dirname( __FILE__ ).'/notifylog/~NOTIFY-log-'. date('Y-m-d',$_SERVER['REQUEST_TIME']) . '.log','huazhifu：'.http_build_query($_REQUEST)."\n", FILE_APPEND);
    	 //pid=1004&trade_no=2025011217293037370&out_trade_no=20250112172929579910&type=alipay&name=product&money=10&trade_status=TRADE_SUCCESS&sign=449808d41349e963cd76213faca57c9a&sign_type=MD5
    	$this->safe_WAF($_REQUEST); //回调字符串安全检查，检查有无sql注入命令
    // 	$_REQUEST=explode($_REQUEST,"&");
		$returnArray = array( // 返回字段
            "pid" => $_REQUEST["pid"], // 商户ID
            "money" =>  $_REQUEST["money"], // 交易金额
            "out_trade_no" =>  $_REQUEST["out_trade_no"], // 订单号
            "trade_no" =>  $_REQUEST["trade_no"], //上游订单号
            "type" =>  $_REQUEST["type"], // 金额(分)
            "trade_status" =>  $_REQUEST["trade_status"], // 金额(分)
            "sign_type" =>  $_REQUEST["sign_type"], // 金额(分)
            "sign" =>  $_REQUEST["sign"], // 金额(分)
            'name'=>$_REQUEST["name"], // 金额(分)
        );
          $md5key=$this->getkey($returnArray['out_trade_no']);//获取后台保存的商户密钥

        $sign = $this->getSign($returnArray,$md5key);
        // var_dump($sign);
        if ($sign == $_REQUEST["sign"]) {
        	$this->EditMoney($returnArray['out_trade_no'], 'Huazf', 0);
            exit("success"); 
        }else{
            exit('sign error');
        }
    }
    
    	public function getSign($param,$key){
		ksort($param);
		reset($param);
		$signstr = '';
	
		foreach($param as $k => $v){
			if($k != "sign" && $k != "sign_type" && $v!=''){
				$signstr .= $k.'='.$v.'&';
			}
		}
	
		$signstr = substr($signstr,0,-1);
// 			var_dump($signstr);
		$signstr .= $key;
// 		var_dump($signstr);
		$sign = md5($signstr);
		return $sign;
	}
}



?>