<?php

namespace Pay\Controller;


use Org\Util\EpayCore;

class ZxzfController extends PayController {

    public function __construct() {
        parent::__construct();
    }

    public function Pay($array) {
        $orderid = I("request.pay_orderid");
        $body = I('request.pay_productname');
        $notifyurl = $this->_site . 'Pay_Zxzf_notifyurl.html'; //异步通知
        $callbackurl = $this->_site . 'Pay_Zxzf_callbackurl.html'; //返回通知

        $parameter = array(
            'code' => 'Zxzf', // 通道名称
            'title' => 'zfbh5',
            'exchange' => 1, // 金额比例
            'gateway' => '',
            'out_trade_id' => $orderid,
            'body' => $body,
            'channel' => $array
        );
    
        // 订单号，可以为空，如果为空，由系统统一的生成
        $return = $this->orderadd($parameter);
//  var_dump($postData);die();
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
        // merchantId    商户号:商户后台查看
    // orderId       商户订单号:订单长度10-50位;可传字母或数字;应确保订单号唯一性
    // orderAmount   订单金额:单位元,可为整数,也可最多保留2位小数
    // channelType   通道编号:商户后台查看
    // notifyUrl     异步通知地址:订单成功后会通知此地址
    // sign          签名:见公共签名规则
    // <h3>可忽略参数</h3>
    // returnUrl     同步跳转地址:终端玩家完成支付后,会跳转到这个页面,如不传则使用我方默认支付成功页面
    // isForm        请求期待结果:填1,则直接使用form表单跳转至收银台页面完成支付,填2,则返回json数据,里面包含收银台支付链接; 默认值:2
    // payer_ip      终端会员ip
    // payer_id      终端会员编号
    // order_title   订单标题
    // order_body    订单描述
        $native=array(
            "merchantId" => $pay_memberid,
        	"orderId" => $pay_orderid,
        	"orderAmount" => $pay_amount,
        	"channelType" => 35,
        	"notifyUrl" => $pay_notifyurl,
            );
            $native['sign']=$this->getSign($native,$return['signkey']);
              $postData=http_build_query($native);
             
              $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $return['gateway']);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // stop verifying certificate
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
        curl_setopt($curl, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        $data = curl_exec($curl);
        curl_close($curl);
       // echo $data;die();
       $json=json_decode($data,1);
         //    var_dump(json_decode($data));die();
            //  var_dump($epay_config);die();


 if($json['code']==200){
     $url=$json['data']['payUrl'];
      header("Location:$url");die;//如果商户没有传type=json值，就直接跳转到支付地址
 }else{
      $this->json_msg("",$return,$data);//如果没有下单成功，就给url一个空值，报错误信息给下游
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
    	 file_put_contents(dirname( __FILE__ ).'/notifylog/~NOTIFY-log-'. date('Y-m-d',$_SERVER['REQUEST_TIME']) . '.log','Zxzf：'.http_build_query($_REQUEST)."\n", FILE_APPEND);
    	 //pid=1004&trade_no=2025011217293037370&out_trade_no=20250112172929579910&type=alipay&name=product&money=10&trade_status=TRADE_SUCCESS&sign=449808d41349e963cd76213faca57c9a&sign_type=MD5
    	 //  merchantId  商户号
    //orderId     商户订单号
    //amount      订单金额
    //status      订单状态 订单已支付才会回调,此参数值固定为 ok
    //sign        回调签名
    	$this->safe_WAF($_REQUEST); //回调字符串安全检查，检查有无sql注入命令
    // 	$_REQUEST=explode($_REQUEST,"&");
		$returnArray = array( // 返回字段
            "merchantId" => $_REQUEST["merchantId"], // 商户ID
            "orderId" =>  $_REQUEST["orderId"], // 交易金额
            "amount" =>  $_REQUEST["amount"], // 订单号
            "status" =>  $_REQUEST["status"], //上游订单号
        );
          $md5key=$this->getkey($returnArray['orderId']);//获取后台保存的商户密钥

        $sign = $this->getSign($returnArray,$md5key);
        // var_dump($sign);
        if ($sign == $_REQUEST["sign"]) {
        	$this->EditMoney($returnArray['orderId'], 'Zxzf', 0);
            exit("success"); 
        }else{
            exit('sign error');
        }
    }
    
    	public function getSign($data,$signkey){
	 $data = array_filter($data); //去空
        ksort($data); //排序
        $tmp_string = http_build_query($data); //进行键值对排列  a=1&b=2&c=3
        $tmp_string = urldecode($tmp_string); //参数无需进行urlencode ,上一步进行了urlencode,这里还原一下
        return md5( $tmp_string .'&key='. $signkey );  //签名生成
	}
}



?>