<?php

namespace Pay\Controller;

use Org\Util\WxH5Pay;

class DingdianController extends PayController {

    public function __construct() {
        parent::__construct();
    }

    public function Pay($array) {
        $orderid = I("request.pay_orderid");
        $body = I('request.pay_productname');
        $notifyurl = $this->_site . 'Pay_Dingdian_notifyurl.html'; //异步通知
        $callbackurl = $this->_site . 'Pay_Dingdian_callbackurl.html'; //返回通知

        $parameter = array(
            'code' => 'Dingdian', // 通道名称
            'title' => '顶点免签支付',
            'exchange' => 1, // 金额比例
            'gateway' => '',
            'orderid' => '',
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
        $tjurl = "https://www.pays.top/pay.php";   //提交地址
        $pay_bankcode = $return['gateway'];   //银行编码
        //扫码
        $extra = "";   
        $native = array(
            "gp_mchid" => $pay_memberid,
            "gp_notify" => $pay_notifyurl,
            "gp_order" => $pay_orderid,
            "gp_price" => $pay_amount*100,
            "gp_rand" => rand(100,999),
            "gp_type" => "0",
          // "gp_extra" => "pay",
        );
        ksort($native);
        $md5str = "";
        foreach ($native as $key => $val) {
            $md5str = $md5str . $key . "=" . $val . "&";
        }
        $sign = strtolower(md5($md5str . "key=" . $Md5key));
        $native["gp_sign"] = $sign;
       $postData=http_build_query($native);
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
        if($json['code']=='0'){  //判断有没有成功
            $url=$json['qrcode'];//如果成功了，获取支付URL地址
           if($_REQUEST['pay_attach']=="json" || $_REQUEST['type']=="json"){//如果下游传值过来是json，就给用户返回json串
            $this->json_msg($url,$return,$data);//去转换为统一格式的json串
           }else{
                  header("Location:$url");die;//如果商户没有传type=json值，就直接跳转到支付地址
           }
            
        }else{
          $this->json_msg("",$return,$data);//如果没有下单成功，就给url一个空值，报错误信息给下游
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
    	 file_put_contents(dirname( __FILE__ ).'/notifylog/~NOTIFY-log-'. date('Y-m-d',$_SERVER['REQUEST_TIME']) . '.log','dhdhd1：'.http_build_query($_REQUEST)."\n", FILE_APPEND);
    	$this->safe_WAF($_REQUEST); //回调字符串安全检查，检查有无sql注入命令
		$returnArray = array( // 返回字段
            "gp_mchid" => $_REQUEST["gp_mchid"], // 商户ID
            "gp_money" =>  $_REQUEST["gp_money"], // 交易金额
            "gp_order" =>  $_REQUEST["gp_order"], // 订单号
            "gp_payno" =>  $_REQUEST["gp_payno"], //上游订单号
            "gp_price" =>  $_REQUEST["gp_price"], // 金额(分)
            "gp_rand" =>  $_REQUEST["gp_rand"], // 随机字符串
            "gp_type" => $_REQUEST["gp_type"],//通道编码
        );
        
        $md5key=$this->getkey($returnArray['gp_order']);//获取后台保存的商户密钥
        $md5key=$this->notify_ip($returnArray['gp_order'],$_SERVER['REMOTE_ADDR']);//验证回调IP是否正确
        ksort($returnArray);
        reset($returnArray);
        $md5str = "";
        foreach ($returnArray as $key => $val) {
            $md5str = $md5str . $key . "=" . $val . "&";
        }
        $sign = md5($md5str . "key=" . $md5key);
        
        if ($sign == $_REQUEST["gp_sign"]) {
        	$this->EditMoney($returnArray['gp_order'], 'Dingdian', 0);
            exit("success"); 
        }else{
            exit('sign error');
        }
}
}

?>