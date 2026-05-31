<?php
namespace Pay\Controller;
/**
 * Created by PhpStorm.
 * User: QQ:572125157
 * Date: 2018-10-30
 * Time: 12:00
 */

class AddOrderController extends PayController
{
    protected $channel; //

    protected $memberid; //商户ID

    protected $pay_amount; //交易金额

    protected $bankcode; //银行码

    protected $orderid; //订单号

    public function __construct()
    {
        parent::__construct();
        if (empty($_POST)) {
            $this->showmessage('no data!没有收到post数据');
        }
       
       //var_dump($_POST);

        $this->firstCheckParams(); //初步验证参数 ，设置memberid，pay_amount，bankcode属性

        $this->judgeRepeatOrder(); //验证是否可以提交重复订单

        $this->userRiskcontrol(); //用户风控检测

        $this->productIsOpen(); //判断通道是否开启

        $this->setChannelApiControl(); //判断是否开启支付渠道 ，获取并设置支付通api的id和通道风控
    }

    public function index()
    {
        

    	 /*    	//咕噜科技：2020-08-21  更新功能
 //查询数据库黑名单列表       
 $heimd = M('channel')->where(['id'=>$this->channel['api']])->getField('iphei');
 $heiip = explode(',',$heimd);//将黑名单列表转换为数组
 if(!empty($heimd)){              //如果黑名单列表不是空的//执行排查 
 //然后判断黑名单IP地址带不带*号通配符
 $heimingdanarr = explode(',',substr($heimd,0,strrpos($heimd ,"*")).'*');
 //排查客户端IP，是不是在黑名单网段
 $daipaichaIP = $this->getClientIP();  //获取客户端IP
 $result1 = substr($daipaichaIP,0,strrpos($daipaichaIP,"."));//把客户端IP第3个.后面的部分删除
 //$clientip = substr($result1,0,strrpos($result1 ,"."));//然后再把第二个点后面的内容删除
 $clientip = $result1;
 $clientip =$clientip . '.*';//给客户端IP后面加上.*两个符号，使它与黑名单列表里面的格式相同
 

 if(in_array($clientip,$heimingdanarr)){ //然后判断客户端IP的网段，192.168.*，这种格式和黑名单列表进行逐一匹配，如果匹配到了则执行

 exit($_SERVER['REMOTE_ADDR'].'<p style="color:ff0000;">您的IP网段已被封禁.禁止恶意提交,请联系管理员解封</p>');//退出，并给出提示。
 }else{   //如果IP网段没匹配到，则执行对单个精准IP的封禁！

 if(in_array($this->getClientIP(),$heiip)){//判断客户端IP是否等于黑名单列表里的IP，如果等于，就退出并给出提示，如果不等于则执行正常下单操作
  
     exit($_SERVER['REMOTE_ADDR'].'<p style="color:ff0000;">您的IP已被封禁.禁止恶意提交,请联系管理员解封</p>');
 }
 }
 }   */
 
    	 $heimd = M('channel')->where(['id'=>$this->channel['api']])->getField('iphei');
    	 $xzdz = explode(',',$heimd);
    	 
    	if(!empty($heimd)){
   if(in_array($_SERVER['REMOTE_ADDR'],$xzdz)){ 
    
   $this->showmessage($_SERVER['REMOTE_ADDR'].'<p style="color:ff0000;">您的IP已被封禁.禁止恶意提交,请联系管理员解封</p>');
   }	
     }
        //进入支付
        if ($this->channel['api']) {
            $info = M('Channel')->where(['id' => $this->channel['api'], 'status' => 1])->find();
            //是否存在通道文件
            if (!is_file(APP_PATH . '/' . MODULE_NAME . '/Controller/' . $info['code'] . 'Controller.class.php')) {
                $this->showmessage('支付通道不存在', ['pay_bankcode' => $this->channel['api']]);
            }
            if (R($info['code'] . '/Pay', [$this->channel]) === false) {
                $this->showmessage('服务器维护中,请稍后再试...');
            }
        } else {
            $this->showmessage("抱歉..该商户未分配可用通道");
        }
    }

    //======================================辅助方法===================================

    /**
     * [初步判断提交的参数是否合法并设置为属性]
     */
    protected function firstCheckParams()
    {
        
        file_put_contents(dirname( __FILE__ ).'/paylog/~NOTIFY-log-'. date('Y-m-d',$_SERVER['REQUEST_TIME']) . '.log','zf：'.http_build_query($_REQUEST)."\n", FILE_APPEND);
        $this->memberid = I("request.pay_memberid", 0, 'intval') - 10000;
		
        // 商户编号不能为空
        if (empty($this->memberid) || $this->memberid <= 0) {
            $this->showmessage("不存在的商户编号!");
        }
        $this->cid= I('post.pay_cid', 0);
        // if (empty($this->cid) || $this->cid <= 0) {
        //     $this->showmessage("不存在的通道!");
        // }

        $this->pay_amount = I('post.pay_amount', 0);
        if ($this->pay_amount == 0) {
            $this->showmessage('金额不能为空');
        }
        
        //银行编码
        $this->bankcode = I('request.pay_bankcode', 0, 'intval');
        if ($this->bankcode == 0) {
            $this->showmessage('不存在的通道编码!', ['pay_banckcode' => $this->bankcode]);
        }

        $this->orderid = I('post.pay_orderid', '');
        if (!$this->orderid) {
            $this->showmessage('订单号不合法！');
        }

    }

    /**
     * [用户风控]
     */
    protected function userRiskcontrol()
    {
        $l_UserRiskcontrol = new \Pay\Logic\UserRiskcontrolLogic($this->pay_amount, $this->memberid); //用户风控类
        $error_msg         = $l_UserRiskcontrol->monitoringData();
        if ($error_msg !== true) {
            $this->showmessage('商户：' . $error_msg);
        }
    }

    /**
     * [productIsOpen 判断通道是否开启，并分配]
     * @return [type] [description]
     */
    protected function productIsOpen()
    {
        $count = M('Product')->where(['id' => $this->bankcode, 'status' => 1])->count();
        //通道关闭
        if (!$count) {
            $this->showmessage('错误通知：通道关闭中，暂时无法连接，或通道编码填写错误!');
        }
        $this->channel = M('ProductUser')->where(['pid' => $this->bankcode, 'userid' => $this->memberid, 'status' => 1])->find();
        //用户未分配
        if (!$this->channel) {
            $this->showmessage('用户未分配通道,暂时无法发起支付请求!');
        }
    }

    /**
     * [判断是否开启支付渠道 ，获取并设置支付通api的id---->轮询+风控]
     */
    protected function setChannelApiControl()
    {
        $l_ChannelRiskcontrol = new \Pay\Logic\ChannelRiskcontrolLogic($this->pay_amount); //支付渠道风控类
        $m_Channel            = M('Channel');

        if ($this->channel['polling'] == 1 && $this->channel['weight']) {

            /***********************多渠道,轮询，权重随机*********************/
            $weight_item  = [];
            $error_msg    = '该通道维护中，请稍后再试';
            $temp_weights = explode('|', $this->channel['weight']);
            foreach ($temp_weights as $k => $v) {
                if($this->cid<1){
                     list($pid, $weight) = explode(':', $v);
                }else{
                    $pid=$this->cid;
                }
                //list($pid, $weight) = explode(':', $v);
                //检查是否开通
                $temp_info = $m_Channel->where(['id' => $pid, 'status' => 1])->find();

                //判断通道是否开启风控并上线
                if ($temp_info['offline_status'] == 1 && $temp_info['control_status'] == 1) {

                    //-------------------------进行风控-----------------
                    $l_ChannelRiskcontrol->setConfigInfo($temp_info); //设置配置属性
                    $error_msg = $l_ChannelRiskcontrol->monitoringData();
                    if ($error_msg === true) {
                        $weight_item[] = ['pid' => $pid, 'weight' => $weight];

                    }

                } else if ($temp_info['control_status'] == 0) {
                    $weight_item[] = ['pid' => $pid, 'weight' => $weight];
                }

            }

            //如果所有通道风控，提示最后一个消息
            if ($weight_item == []) {
                $this->showmessage('通道:' . $error_msg);
            }
            $weight_item          = getWeight($weight_item);
            $this->channel['api'] = $pid;

        } else {
            /***********************单渠道,没有轮询*********************/

            //查询通道信息
            
            
             if($this->cid<1){
                    $pid          = $this->channel['channel'];
                }else{
                    $pid=$this->cid;
                }
            $channel_info = $m_Channel->where(['id' => $pid])->find();

            //通道风控
            $l_ChannelRiskcontrol->setConfigInfo($channel_info); //设置配置属性
            $error_msg = $l_ChannelRiskcontrol->monitoringData();

            if ($error_msg !== true) {
                $this->showmessage('通道:' . $error_msg);
            }
            $this->channel['api'] = $pid;
        }
    }
    
    /**
     * 判断是否可以重复提交订单
     * @return [type] [description]
     */
    public function judgeRepeatOrder()
    {
        $is_repeat_order = M('Websiteconfig')->getField('is_repeat_order');
        if (!$is_repeat_order) {
            //不允许同一个用户提交重复订单
            $pay_memberid = $this->memberid + 10000;
            $count = M('Order')->where(['pay_memberid' => $pay_memberid, 'out_trade_id' => $this->orderid])->count();
            if($count){
                $this->showmessage('重复订单！请尝试重新提交订单');
            }
        }
    }

}
