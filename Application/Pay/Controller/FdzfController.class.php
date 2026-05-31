<?php

namespace Pay\Controller;


use Org\Util\EpayCore;

class FdzfController extends PayController
{

    public function __construct()
    {
        parent::__construct();
    }

    public function Pay($array)
    {
        $orderid = I("request.pay_orderid");
        $body = I('request.pay_productname');
        $notifyurl = $this->_site . 'Pay_Fdzf_notifyurl.html'; //异步通知
        $callbackurl = $this->_site . 'Pay_Fdzf_callbackurl.html'; //返回通知
        // var_dump($array);die();
        $parameter = array(
            'code' => 'Fdzf', // 通道名称
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

      
        //扫码
        $extra = "";
        $timestamp = round(microtime(true) * 1000);
        /*
        merchantId    商户号:商户后台查看
    orderId       商户订单号:订单长度10-50位;可传字母或数字;应确保订单号唯一性
    orderAmount   订单金额:单位元,可为整数,也可最多保留2位小数
    channelType   通道编号:商户后台查看
    notifyUrl     异步通知地址:订单成功后会通知此地址
    sign          签名:见公共签名规则
        */
        $native = array(
            "merchantId" => $pay_memberid,
            "orderId" => $pay_orderid,
            "orderAmount" => $pay_amount,
            "channelType" => $return['syid'],
            "notifyUrl" => $pay_notifyurl,


        );
     
        $native['sign'] = $this->getSign($native, $return['signkey']);
        $postData = json_encode($native);

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $return['gateway']);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // stop verifying certificate
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($curl, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        $data = curl_exec($curl);
        curl_close($curl);
        // echo $data;die();
        $json = json_decode($data, 1);
//   var_dump($json);die();
                $logName = dirname(__FILE__) . '/paylog/Fdzf~NOTIFY-log-' . date('Y-m-d', $_SERVER['REQUEST_TIME']) . '.log';
        file_put_contents(dirname(__FILE__) . '/paylog/Fdzf~NOTIFY-log-' . date('Y-m-d', $_SERVER['REQUEST_TIME']) . '.log', 'Fdzf：' . json_encode($json) . "\n", FILE_APPEND);
        if ($json['code'] == 200) {
            $url = $json['data']['payUrl'];

            if ($_REQUEST['type'] == 'json') {
                $msg = '{"status":"1","msg":"下单成功","pay_amount":"' . $pay_amount . '","pay_orderid":"' . $pay_orderid . '","payUrl":"' . $url . '"}';
                exit($msg);
            } else {
                header("Location:$url");
                die;
            }
        } else {
            $this->json_msg("", $return, $json['msg']);//如果没有下单成功，就给url一个空值，报错误信息给下游
        }


    }

    public function callbackurl()
    {

        $Order = M("Order");
        $orderid = I('request.gp_order/s');
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
    public function notifyurl()
    {
        $json = file_get_contents('php://input');
        // $json="merchantId=10083&orderId=20260321213559102515&amount=100&status=ok&sign=9bfe00bb726fd00ffc9b83c1406ad088";
        $logName = dirname(__FILE__) . '/notifylog/Fdzf~NOTIFY-log-' . date('Y-m-d', $_SERVER['REQUEST_TIME']) . '.log';
        file_put_contents(dirname(__FILE__) . '/notifylog/Fdzf~NOTIFY-log-' . date('Y-m-d', $_SERVER['REQUEST_TIME']) . '.log', 'Fdzf：' . $json . "\n", FILE_APPEND);
        // 将JSON字符串转换为PHP对象或数组
        // $data = explode('&',$json);
        $data = array(); // PHP 5.6 也支持 []，这里用 array() 更兼容极低版本

        // 按 & 拆分所有键值对
        $keyValuePairs = explode('&', $json);
        
        foreach ($keyValuePairs as $pair) {
            // 只拆分第一个 =（避免 value 含 = 导致解析错误，PHP 5.6 支持该参数）
            $parts = explode('=', $pair, 2);
            if (count($parts) == 2) {
                $key = trim($parts[0]);
                $value = trim($parts[1]);
                // PHP 5.6 支持 urldecode，若字符串含 URL 编码需解码
                $data[$key] = urldecode($value);
            }
        }

        


        $this->safe_WAF($data); //回调字符串安全检查，检查有无sql注入命令
        /* merchantId  商户号
            orderId     商户订单号
            amount      订单金额
            status      订单状态 订单已支付才会回调,此参数值固定为 ok
            sign        回调签名
        */

        $returnArray = array( // 返回字段
            "merchantId" => $data["merchantId"], // 商户ID
            "orderId" => $data["orderId"], // 订单号
            "amount" => $data["amount"], // 订单号
            "status" => $data["status"], // 交易金额

        );
        $md5key = $this->getkey($returnArray['orderId']);//获取后台保存的商户密钥

        $sign = $this->getSign($returnArray, $md5key);
        //  var_dump($sign);
        // var_dump($md5key);die();
        if ($sign == $data["sign"]) {
             $this->EditMoney($returnArray['orderId'], 'Fdzf', 0);
            exit("success");
        } else {
            exit('sign error');
        }
    }


    /**
     * 使用RSA私钥对字符串进行SHA256WithRSA签名
     * @param string $privateKey 商户私钥（PEM格式字符串）
     * @param string $data 待签名的原始字符串
     * @return string|null 签名结果（Base64编码），失败返回null
     * @throws Exception 私钥无效或签名失败时抛出异常
     */


    public function getSign($data, $signkey)
    {
        $data = array_filter($data); //去空
        ksort($data); //排序
        $tmp_string = http_build_query($data); //进行键值对排列  a=1&b=2&c=3
        $tmp_string = urldecode($tmp_string); //参数无需进行urlencode ,上一步进行了urlencode,这里还原一下
        return md5($tmp_string . '&key=' . $signkey);  //签名生成
    }
}


?>