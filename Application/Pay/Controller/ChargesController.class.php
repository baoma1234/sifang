<?php
namespace Pay\Controller;

use Think\Controller;
/**
 * Created by PhpStorm.
 * User: QQ:572125157
 * Date: 2018-10-30
 * Time: 12:00
 */
class ChargesController extends PayController{
     public function index(){
        $mchid= I("mid",0,"intval");
        $userIdx = intval($mchid) - 10000;
        if(!$mchid){
            redirect(U('Pay/Charges/msgshow'));
        }
        $where['id'] = $userIdx;
        $member=M("member")->where($where)->find();
        if(!$member){
            redirect(U('Pay/Charges/msgshow'));
        }
        //支付产品
        $products = M('Product')->where(['isdisplay'=>1, 'status'=>1])->select();
        
      
        
        foreach ($products as $key=>$val){
            
        //支付产品
            $puser = M('product_user')->where(['userid'=>$userIdx ,'pid'=>$val['id'], 'status'=>1])->find();
            if(count($products)>0 && empty($puser)){
                unset($products[$key]);
            }
            
        }
        
        $finalProducts = [];
        if(isMobile()){
            foreach ($products as $key => $product) {
                if(strstr($product['code'], 'SCAN')){
                    continue;
                }

                if(strstr($product['code'], 'WXJSAPI')){
                    continue;
                }
                $product['icon'] = $this->getIcon($product['code']);
                $finalProducts[] = $product; 
            }
        }else if(isMobile()){
            foreach ($products as $key => $product) {
                if(strstr($product['code'], 'WXJSAPI')){
                    $product['icon'] = $this->getIcon($product['code']);
                    $finalProducts[] = $product; 
                    break;
                }
            }
        }else{
            foreach ($products as $key => $product) {
                if(strstr($product['code'], 'WXJSAPI')){
                    continue;
                }
                $product['icon'] = $this->getIcon($product['code']);
                $finalProducts[] = $product; 
            }
        }
        
        $pay_orderid = date("YmdHis").rand(100000,999999);    //订单号
        
        $this->assign('pay_orderid', $pay_orderid);
        $this->assign('products', $finalProducts);
        $this->assign("cache",$member);
        $this->assign("mchid",$mchid);
        $this->assign("posturl",$this->_site."Pay_Charges_checkout.html");
        if(isMobile()) {
            $this->display();
        } else {
            $this->display('index_pc');
        }
    }


  
   protected function getIcon($product_code)
    {
        $weixin = ['WXSCAN', 'WXJSAPI'];
        $alipay = ['ALIWAP', 'ALISCAN'];
        $qq     = ['QQWAP'];
        if(in_array($product_code, $weixin)){
            $icon = 'wechat-icon';
        }
        if(in_array($product_code, $alipay)){
            $icon = 'alipay-icon';
        }
        if(in_array($product_code, $qq)){
            $icon = 'qq-icon';
        }
        return $icon;
    }
  
    public function checkout(){
        if(IS_POST){
            $pay_amount =  I("amount");    //交易金额
            if($pay_amount<=0){
                exit("交易金额不正确");
            }
            $mchid= I("mchid",0,"intval");

            if(!$mchid){
                exit("缺少商户号");
            }
            $where['id'] = intval($mchid) - 10000;
            $member=M("member")->where($where)->find();
            if(!$member){
                exit("商户不存在");
            }
            if($member['groupid'] == 4 && !$member['open_charge']) {
                exit("商户未开通充值功能");
            }
            $pay_memberid = ($member["id"]+10000);   //商户ID
            $pay_orderid = 'C'.date("YmdHis").rand(100000,999999);    //订单号


            $pay_bankcode =I("bankcode");   //银行编码
            if(empty($pay_memberid)||empty($pay_amount)||empty($pay_bankcode)){
                die("信息不完整！");
            }

            $pay_applydate = date("Y-m-d H:i:s");  //订单时间
            $pay_notifyurl = $this->_site."Pay_Charges_notify.html";   //服务端返回地址
            $pay_callbackurl = $this->_site."Pay_Charges_callback.html";  //页面跳转返回地址
            $Md5key = $member["apikey"];   //密钥
            $tjurl = $this->_site."Pay_AddOrder";   //提交地址



            $native = array(
                "pay_memberid" => $pay_memberid,
                "pay_orderid" => $pay_orderid,
                "pay_amount" => $pay_amount,
                "pay_applydate" => $pay_applydate,
                "pay_bankcode" => $pay_bankcode,
                "pay_notifyurl" => $pay_notifyurl,
                "pay_callbackurl" => $pay_callbackurl,
            );
            ksort($native);
            $md5str = "";
            foreach ($native as $key => $val) {
                $md5str = $md5str . $key . "=" . $val . "&";
            }

            $sign = strtoupper(md5($md5str . "key=" . $Md5key));
            $native["pay_md5sign"] = $sign;
            $native['pay_attach'] = $_POST["remarks"];
            if($_POST["remarks"]=="json"){
                 $native['type'] ==$_POST["remarks"];
            }
            $native['pay_productname'] ='团购商品';
            $native['ddlx'] = 1;

            $this->setHtml($tjurl,$native);
        }
    }

    public function notify()
    {
         $returnArray = array( // 返回字段
            "memberid" => $_REQUEST["memberid"], // 商户ID
            "orderid" =>  $_REQUEST["orderid"], // 订单号
            "amount" =>  $_REQUEST["amount"], // 交易金额
            "true_amount" =>  $_REQUEST["true_amount"], //实付金额
            "datetime" =>  $_REQUEST["datetime"], // 交易时间
            "transaction_id" =>  $_REQUEST["transaction_id"], // 支付流水号
            "returncode" => $_REQUEST["returncode"],
        );
        if(!$returnArray["memberid"]){
            die;
        }
        $where['id'] = intval($returnArray["memberid"]) - 10000;
        $member=M("member")->where($where)->find();
      
        if(!$member){
            die;
        }
        $Md5key =$member["apikey"];
        ksort($returnArray);
        reset($returnArray);
        $md5str = "";
        foreach ($returnArray as $key => $val) {
            $md5str = $md5str . $key . "=" . $val . "&";
        }
         $sign = strtoupper(md5($md5str . "key=" . $Md5key));
        if ($sign == $_REQUEST["sign"]) {
            if ($_REQUEST["returncode"] == "00") {
                exit("OK");
            }
        }
    }

    public function callback()
    {
        $ReturnArray = array( // 返回字段
            "memberid" => $_REQUEST["memberid"], // 商户ID
            "orderid" =>  $_REQUEST["orderid"], // 订单号
            "amount" =>  $_REQUEST["amount"], // 交易金额
            "datetime" =>  $_REQUEST["datetime"], // 交易时间
            "transaction_id" =>  $_REQUEST["transaction_id"], // 流水号
            "returncode" => $_REQUEST["returncode"]
        );

        if(!$ReturnArray["memberid"]){
            die;
        }

        $where['id'] = intval($ReturnArray["memberid"]) - 10000;
        $member=M("member")->where($where)->find();
        if(!$member){
            die;
        }
        $Md5key =$member["apikey"];

        ksort($ReturnArray);
        reset($ReturnArray);
        $md5str = "";
        foreach ($ReturnArray as $key => $val) {
            $md5str = $md5str . $key . "=" . $val . "&";
        }
        $sign = strtoupper(md5($md5str . "key=" . $Md5key));

        if ($sign == $_REQUEST["sign"]) {
            if ($_REQUEST["returncode"] == "00") {
                $this->assign("cache",$ReturnArray);
                $this->assign("goback",U('Pay/Charges/index',array('mchid'=>$ReturnArray["memberid"])));
               $this->display("success");

            }
        }
    }
    public function msgshow(){
        $msg=I("msg")?I("msg"):"非法操作";
        $this->assign("msg",$msg);
        $this->display();
    }
}
