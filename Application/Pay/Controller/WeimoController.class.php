<?php

namespace Pay\Controller;

use Org\Util\WxH5Pay;

class WeimoController extends PayController {

    public function __construct() {
        parent::__construct();
    }

    public function Pay($array) {
        $orderid = I("request.pay_orderid");
        $body = I('request.pay_productname');
        $notifyurl = $this->_site . 'Pay_Weimo_notifyurl.html'; //异步通知
        $callbackurl = $this->_site . 'Pay_Weimo_callbackurl.html'; //返回通知

        $parameter = array(
            'code' => 'Weimo', // 通道名称
            'title' => 'Weimo',
            'exchange' => 1, // 金额比例
            'gateway' => '',
            'orderid' => '',
            'out_trade_id' => $orderid,
            'body' => $body,
            'channel' => $array
        );

        // 订单号，可以为空，如果为空，由系统统一的生成
        $return = $this->orderadd($parameter);


           $pay_memberid = "11880008";   //商户ID
        $pay_orderid = $return['orderid'];    //订单号
        $pay_amount = $return['amount'];    //交易金额
        $pay_applydate = date("Y-m-d H:i:s");  //订单时间
        $pay_notifyurl = $notifyurl;   //服务端返回地址
        $pay_callbackurl = $callbackurl;  //页面跳转返回地址
        $Md5key = "46a50e8ab47247d120b5291fac03ebf3";   //密钥
        $tjurl = "http://api.jimupay.cc:5151/web-inf/Pay.jsp";   //提交地址
        $pay_bankcode ="15";
        //$pay_bankcode = $return['gateway'];   //银行编码
         //扫码
        $native = array(
            "merchantid" => $pay_memberid,
            "wayid" => $pay_bankcode,
            "waytype" => 'IOS',
            "merchantorder" => $pay_orderid,
             "userid" => date("YmdHis"),
            "money" => $pay_amount*100,
           // "pay_applydate" => $pay_applydate,
            "notifyurl" => $pay_notifyurl,
            "backurl" => $pay_callbackurl,
             "extparam" => '',
             "clientip" => '127.0.0.1',
             "signtype" => 'Md5',
             
        );
        ksort($native);
        $md5str = "";
        foreach ($native as $key => $val) {
            $md5str = $md5str . $key . "=" . $val . "&";
        }
       // $newstr = substr($md5str,0,strlen($md5str)-1); 
             $sign = md5($md5str . "key=" . $Md5key);
        $native["sign"] = $sign;
        $native['payformat'] = "json";
        //$native['pay_productname'] = '购买商品';

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
 		$url = $json['data']['payurl'];
 	
 		if($url){
			header("Location:$url");die;
		}
		var_dump('<pre>',$json);die;
   
    	
    }
    public function callbackurl() {
  exit('ok');
        $Order = M("Order");
        $orderid=I('request.merchantorder/s'); 
        $pay_status = $Order->where(['pay_orderid' => $orderid])->getField("pay_status");
        $callbackurl = $Order->where(['pay_orderid' => $orderid])->getField("pay_callbackurl");
        //var_dump($callbackurl);die;
        if ($pay_status <> 0) {
           // $this->EditMoney($_REQUEST['outTradeNo'], 'Weimo', 1);
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
          
        if ($_REQUEST["code"] == 1) {
            //if ($_REQUEST["merchantorder"] == "433367219804569600") {
                   $this->EditMoney($_REQUEST['merchantorder'], 'Weimo', 0);
                   exit("Success");
          //  }
        }

    }

}