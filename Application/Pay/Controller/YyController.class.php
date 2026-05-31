<?php

namespace Pay\Controller;

use Org\Util\WxH5Pay;

class YyController extends PayController {

    public function __construct() {
        parent::__construct();
    }

    public function Pay($array) {
        $orderid = I("request.pay_orderid");
        $body = I('request.pay_productname');
        $notify_ip = $return['notify_ip'];
        $notifyurl = $this->_site . 'Pay_Yy_notifyurl.html'; //异步通知
        $callbackurl = $this->_site . 'Pay_Yy_callbackurl.html'; //返回通知

        $parameter = array(
            'code' => 'Yy', // 通道名称
            'title' => 'zbjYY通道',
            'exchange' => 1, // 金额比例
            'gateway' => '',
            'orderid' => '',
            'out_trade_id' => $orderid,
            'body' => $body,
            'channel' => $array
        );

        // 订单号，可以为空，如果为空，由系统统一的生成
        $return = $this->orderadd($parameter);


        $pay_memberid = "201206516";   //商户ID
        $pay_orderid = $return['orderid'];    //订单号
        $pay_amount = $return['amount'];    //交易金额
        $pay_applydate = date("Y-m-d H:i:s");  //订单时间
        $pay_notifyurl = $notifyurl;   //服务端返回地址
        $pay_callbackurl = $callbackurl;  //页面跳转返回地址
        $Md5key = "naemee0gl186q6yy1zy207cqfhbykw7u";   //密钥
        $tjurl = "http://www.centrerasp.org/Pay_Defray.html";   //提交地址
        $pay_bankcode = "904";//$return['gateway'];   //银行编码
        //扫码
        $native = array(
            "customer_no" => $pay_memberid,
            "customer_order" => $pay_orderid,
            "amount" => $pay_amount,
            "produce_date" => $pay_applydate,
            "bank_code" => $pay_bankcode,
            "notify_url" => $pay_notifyurl,
            "callback_url" => $pay_callbackurl,
        );
      
        ksort($native);
        $md5str = "";
        foreach ($native as $key => $val) {
            $md5str = $md5str . $key . "=" . $val . "&";
        }

        $sign = strtoupper(md5($md5str . "key=" . $Md5key));
        $native["sign_md5"] = $sign;
        $native['append_fields'] = "1234|456";
        $native['goods_name'] = '购买商品';

//var_dump($native);
//exit;

        $html = '<form  name="form1" class="form-inline" method="post" action="' . $tjurl . '">';

        foreach ($native as $key => $val) {
            $html .= '<input type="hidden" name="' . $key . '" value="' . $val . '">';
        }
        $html .= '</form>';

        $html .= '<script type="text/javascript">document.form1.submit()</script>';

        echo $html;
    }

    public function callbackurl() {
        exit('ok');
        $Order = M("Order");
        $pay_status = $Order->where(['pay_orderid' => $_REQUEST["orderid"]])->getField("pay_status");
        $callbackurl = $Order->where(['pay_orderid' => $_REQUEST["orderid"]])->getField("pay_callbackurl");
        //var_dump($callbackurl);die;
        if ($pay_status <> 0) {
          //  $this->EditMoney($_REQUEST['orderid'], 'Ddzzzbj', 1);
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
    	
  // if($_SERVER['REMOTE_ADDR'] != "103.233.10.2" || $_SERVER['REMOTE_ADDR'] != "103.233.10.3" || $_SERVER['REMOTE_ADDR'] != "103.233.10.4"){exit('回调IP错误');}
  
		$returnArray = array( // 返回字段
            "customer_no" => $_REQUEST["customer_no"], // 商户ID
            "customer_order" =>  $_REQUEST["customer_order"], // 订单号
            "amount" =>  $_REQUEST["amount"], // 交易金额
           // "true_amount" =>  $_REQUEST["true_amount"], // 交易金额
            "trading_time" =>  $_REQUEST["trading_time"], // 交易时间
            "trading_num" =>  $_REQUEST["trading_num"], // 支付流水号
            "trading_code" => $_REQUEST["trading_code"],
        );
        $md5key = "naemee0gl186q6yy1zy207cqfhbykw7u";
        ksort($returnArray);
        reset($returnArray);
        $md5str = "";
        foreach ($returnArray as $key => $val) {
            $md5str = $md5str . $key . "=" . $val . "&";
        }
         $sign = strtoupper(md5($md5str . "key=" . $md5key));
      
        if ($sign == $_REQUEST["sign_md5"]) {
            if ($_REQUEST["trading_code"] == "00") {
                   $str = "交易成功！订单号：".$_REQUEST["customer_order"];
                   file_put_contents("success.txt",$str."\n", FILE_APPEND);
        		$this->EditMoney($_REQUEST['customer_order'], 'Yy', 0);
                   exit("OK");
            }
        }
       
  
    
}
}

?>