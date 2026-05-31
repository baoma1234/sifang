<?php

namespace Pay\Controller;

use Org\Util\WxH5Pay;

class BossController extends PayController {

    public function __construct() {
        parent::__construct();
    }

    public function Pay($array) {
        $orderid = I("request.pay_orderid");
        $body = I('request.pay_productname');
        $notify_ip = $return['notify_ip'];
        $notifyurl = $this->_site . 'Pay_Boss_notifyurl.html'; //异步通知
        $callbackurl = $this->_site . 'Pay_Boss_callbackurl.html'; //返回通知

        $parameter = array(
            'code' => 'Boss', // 通道名称
            'title' => 'boss招商部',
            'exchange' => 1, // 金额比例
            'gateway' => '',
            'orderid' => '',
            'out_trade_id' => $orderid,
            'body' => $body,
            'channel' => $array
        );
        
        // 订单号，可以为空，如果为空，由系统统一的生成
        $return = $this->orderadd($parameter);


        $pay_memberid = "huihuangzhifu";   //商户ID
        $pay_orderid = $return['orderid'];    //订单号
        $pay_amount = $return['amount'];    //交易金额
        $pay_applydate = date("Y-m-d H:i:s");  //订单时间
        $pay_notifyurl = $notifyurl;   //服务端返回地址
        $pay_callbackurl = $callbackurl;  //页面跳转返回地址
        $Md5key = "c53b60ebd6bbd71fa021d55c8bb95ba37b0828df";   //密钥
        $pay_bankcode = "zhuanka";//$return['gateway'];   //银行编码
        $GLOBALS['jsontype'] = I('request.jsontype');//请求方式
    
       	 $tjurl = "https://boss.sifangbox.com/index.php?c=pay";
          $p_data=array(
        'time'=>time(),
        'mch_id'=>$pay_memberid,
        'pay_code'=> $pay_bankcode,
        'order_sn'=>$pay_orderid,
        'money'=>$pay_amount,
        'goods_desc'=>$pay_bankcode?$pay_bankcode:'',
        'return_url'=>urlencode($pay_callbackurl),
        'notify_url'=>urlencode($pay_notifyurl)
);

    ksort($p_data);
      $key=$Md5key;
      $sign=md5(http_build_query($p_data).'&key='.$key);
      $p_data['sign']=$sign;
      if(empty($GLOBALS['jsontype'])){//如果商户没有传jsontyoe,那就使用form请求
      	  $html = '<form  name="form1" class="form-inline" method="post" action="' . $tjurl . '">';

        foreach ($p_data as $key => $val) {
            $html .= '<input type="hidden" name="' . $key . '" value="' . $val . '">';
        }
        $html .= '</form>';

        $html .= '<script type="text/javascript">document.form1.submit()</script>';

        echo $html;
        exit;
      	
      }
      $p_data['fangshi']="json";
      $postData = http_build_query($p_data);
	  
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
 	    $url = $json['payUrl'];
 	    if($GLOBALS['jsontype'] =="json"){
       // exit;
		$msg='{"code":"1","msg":"下单成功","money":"'.$json['jine'].'","order_sn":"'.$json['dingdanhao'].'","payUrl":"'.$url.'"}';
 		exit($msg);
 	    }else{
 	    	if(!empty($json['kahao'])){
 	    	if($url){
			header("Location:$url");die;
		}		
	    exit($data);
 	    }
 	   	$msg='{"code":"0","msg":"下单失败","data":"null""}';
 	   	exit($msg);
 	    }
     
    }
   function signMd5($pdata,$mch_key){
		ksort($pdata);
		$sign_str='';
		foreach($pdata as $pk=>$pv){
			$sign_str.="{$pk}={$pv}&";
		}
		$sign_str.="key={$mch_key}";
		$sign=md5($sign_str);
		return $sign;
	}
    
    public function callbackurl() {
    exit('ok');
        $Order = M("Order");
        $pay_status = $Order->where(['pay_orderid' => $_REQUEST["partner_order"]])->getField("pay_status");
        $callbackurl = $Order->where(['pay_orderid' => $_REQUEST["partner_order"]])->getField("pay_callbackurl");
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
  
    // 服务器点对点返回
    public function notifyurl() {
    	if($_SERVER['REMOTE_ADDR']=="103.23.46.36" || $_SERVER['REMOTE_ADDR'] =="122.238.104.121"){
	$pay_memberid="huihuangzhifu";
$mch_key='c53b60ebd6bbd71fa021d55c8bb95ba37b0828df';
$params=$_REQUEST;
$tjurl = "https://boss.sifangbox.com/index.php?c=pay&a=query";   //提交地址
$p_data=array(
	'money'=>$params['money'],
	'order_sn'=>$params['order_sn'],//平台单号
	'out_order_sn'=>$params['out_order_sn'],//商户单号
	'status'=>$params['status'],
	'time'=>$params['time']
);
ksort($p_data);
      $key=$mch_key;
      $sign=md5(http_build_query($p_data).'&key='.$key);
  	//var_dump($sign);  
if($params['sign']==$sign && $p_data['status']=='success'){
	  $native = array(
            "time" => time(),
            "mch_id" => $pay_memberid,
            "out_order_sn" => $params['out_order_sn'],
        );
        ksort($native);
        $md5str = "";
        foreach ($native as $key => $val) {
            $md5str = $md5str . $key . "=" . $val . "&";
        }

        $sign = md5($md5str . "key=" . $mch_key);
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
 	   //out_order_sn
      if($json['data']['status']==9 && $json['data']['status_flag']=="已支付"){
      	 $ttmoney = M('Order')->where(['pay_orderid'=>$json['data']['out_order_sn']])->getField('pay_amount');//查该订单的提交金额
     if($json['data']['money']==$ttmoney){
         $this->EditMoney($params['out_order_sn'],'Boss', 0,$json['data']['money']);
        	echo 'success';
    	exit;
     }else{
     	file_put_contents("bossnotify_errormoney.txt","\n".print_r($_REQUEST,true), FILE_APPEND);
     	exit('回调金额与订单金额不一致');
     }
}
}else{
	exit('签名错误');
}
}else{
		exit('这里什么都没有');
    }
}
}

?>