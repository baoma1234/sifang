<?php

namespace Pay\Controller;

use Org\Util\WxH5Pay;

class DaController extends PayController {

    public function __construct() {
        parent::__construct();
    }

    public function Pay($array) {
        $orderid = I("request.pay_orderid");
        $body = I('request.pay_productname');
        $notifyurl = $this->_site . 'Pay_Da_notifyurl.html'; //异步通知
        $callbackurl = $this->_site . 'Pay_Da_callbackurl.html'; //返回通知

        $parameter = array(
            'code' => 'Da', // 通道名称
            'title' => 'Da',
            'exchange' => 1, // 金额比例
            'gateway' => '',
            'orderid' => '',
            'out_trade_id' => $orderid,
            'body' => $body,
            'channel' => $array
        );

        // 订单号，可以为空，如果为空，由系统统一的生成
        $return = $this->orderadd($parameter);


           $pay_memberid = "9000003";   //商户ID
        $pay_orderid = $return['orderid'];    //订单号
        $pay_amount = $return['amount'];    //交易金额
        $pay_applydate = date("Y-m-d H:i:s");  //订单时间
        $pay_notifyurl = $notifyurl;   //服务端返回地址
        $pay_callbackurl = $callbackurl;  //页面跳转返回地址
        $Md5key = "c614e2f42cba180c5c604bcac9d75a6c";   //密钥
        $tjurl = "http://47.242.65.254/api/pay";   //提交地址
        $pay_bankcode ="1";
        //$pay_bankcode = $return['gateway'];   //银行编码
         //扫码
        $native = array(
            "mchid" => $pay_memberid,
            "mch_order_id" => $pay_orderid,
            "price" => $pay_amount,
            "paytype" => $pay_bankcode,
             "notify" => $pay_notifyurl,
            "time" => time(),
            "rand" => rand(100000,999999),
           
        );
        $sign = md5($native['mchid'].$native['mch_order_id'].$native['price'].$native['paytype'].$native['notify'].$native['time'].$native['rand'].$Md5key);
            
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
 		$url = $json['data']['url'];

 		if($url){
			header("Location:$url");die;
		}
		exit($data);
   
    	
    }
    public function callbackurl() {
        exit('ok');
        $Order = M("Order");
        $orderid=I('request.mch_order_id/s'); 
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
		$returnArray = array( // 返回字段
            "mchid" => $_REQUEST["mchid"], // 商户ID
            "mch_order_id" =>  $_REQUEST["mch_order_id"], // 订单号
            "pt_order_id" =>  $_REQUEST["pt_order_id"], // 交易金额
            "out_order_id" =>  $_REQUEST["out_order_id"], // 交易金额
            "price" =>  $_REQUEST["price"], // 交易时间
            "paytype" =>  $_REQUEST["paytype"], // 支付流水号
            "status" => $_REQUEST["status"],
        );
      $md5key = "c614e2f42cba180c5c604bcac9d75a6c";
      $sign=md5($returnArray['mchid'].$returnArray['mch_order_id'].$returnArray['pt_order_id'].$returnArray['out_order_id'].$returnArray['price'].$returnArray['paytype'].$returnArray['status'].$md5key); 
          
          
        if ($_REQUEST["status"] == 1 && $_REQUEST["sign"]==$sign) {
            //if ($_REQUEST["merchantorder"] == "433367219804569600") {
                   $this->EditMoney($_REQUEST['mch_order_id'], 'Da', 0);
                   exit("success");
          //  }
        }

    }

}