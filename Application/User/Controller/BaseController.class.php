<?php
/**
 * Created by PhpStorm.
 * User: gaoxi  技术QQ：1968984054   源码免费提供，仅供交流学习测试使用，禁止用于非法用途，否则后果自负。
 * Date: 2017-04-03
 * Time: 1:56
 */

namespace User\Controller;

use Think\Controller;

class BaseController extends Controller
{
    public $_site;
    public $siteconfig;
    const LENGTH = 6; //验证码的长度
    const EXPIRE = 300; //过期时间
    public function __construct()
    {
        parent::__construct();
        //$this->_site = ((is_https()) ? 'https' : 'http') . '://' . C("DOMAIN") . '/';
        //$this->assign('siteurl', $this->_site);
        //$this->assign('sitename', C('WEB_TITLE'));
        //获取系统配置
        $this->siteconfig = M("Websiteconfig")->find();
        $this->assign('siteconfig', $this->siteconfig);
      //将基本的参数插入模板中
            $this->assign([

                'siteconfig'             => $this->siteconfig, //系统配置
                 'siteurl'               =>  ((is_https()) ? 'https' : 'http') . '://' . $this->siteconfig['domain'],
                'sitename'               => $this->siteconfig['websitename'],
                'logo'                   => $this->siteconfig['logo'],
				'qq'               => $this->siteconfig['qq'],

                'user_login'             => U('Home/Index/userLogin'), //用户登录页面
                'user_checklogin'        => '/User_Login_checklogin', //用户登录检测地址

                'agent_login'            => U('Home/Index/agentLogin'), //代理登录页面
                'agent_checklogin'       => '/Agent_Login_checklogin', //代理登录检测地址

                'register'               => U('Home/Index/register'), //用户或代理注册检测地址
                'register_checkRegister' => U('Agent/Login/checkRegister'), //用户或代理注册检测地址
                'agentregister_checkRegister' => U('Agent/Login/checkRegister'), //代理注册检测地址
                'checkuser'              => U('User/Login/checkuser'), //检测是已存在商户
                'checkemail'             => U('User/Login/checkemail'), //邮箱检测地址
                'checkinvitecode'        => U('User/Login/checkinvitecode'), //邀请码检测地址

                'verifycode'             => U('Agent/Login/verifycode'), //验证码
				
				'products'               => U('Home/Index/products'), //产品介绍 
                'sdk'               => U('Home/Index/sdk'), //sdk下载页 
               'document'               => U('Home/Index/document'), //api开发文档 
                'demo'                   => U('Home/Index/demo'), //demo地址
                'introduce'              => U('Home/Index/introduce'), //产品介绍地址

                'faq'                    => U('Home/Index/faq'), //常见问题
                'safe'                   => U('Home/Index/safe'), //安全无忧
                'contact'                => U('Home/Index/contact'), //联系我们
                'about'                => U('Home/Index/about'), //关于我们
                'download_demo'          => 'Uploads/demo.zip',//demo下载地址
            ]);
            //将开发者自定义参数插入模板中
    }

    /**
     * 发送验证码
     * @param  [type] $callInde 要调用的模板代码
     * @param  [type] $mobile 手机号码
     * @param  [type] $product 模板的$product参数
     * @return [type]          [description]
     */
    protected function send($callIndex, $mobile, $product)
    {

        //验证码的长度
        $length = self::LENGTH;
        //生成随机验证码
        $num = range(0, 9);
        shuffle($num);
        $randNum      = substr(implode('', $num), 0, $length);
        $templeData   = getSmsTemplateCode($callIndex);
        $templateCode = $templeData['template_code'];
        session('send', null);
        //记录验证码
        $sessionCode = 'send.' . $callIndex;
        $timeSession = 'send.' . $callIndex . '|' . $randNum;
        session($timeSession, time()); //存入当前生成验证码的时间
        session($sessionCode, $randNum);

        if ($callIndex == 'loginWarning') {
            $templeContent = ['time' => time()];
        } else {
            //查看模板变量的个数，如果是1个是新模板，2个是旧模板
            $count = substr_count($templeData['template_content'], '$');
            //模板参数
            $templeContent = $count >= 2 ? ['code' => $randNum, 'opration' => $product] : ['code' => $randNum];
        }

        //发送验证码
        $res = sendSMS($mobile, $templateCode, $templeContent);
        if ($res === true) {
            return ['code' => 1, 'message' => '发送成功'];
        } else {
            return ['code' => 0, 'message' => $res];
        }
    }

    /**
     * 发送文本信息
     * @param  [type] $callIndex [description]
     * @param  [type] $mobile    [description]
     * @param  [type] $product   [description]
     * @return [type]            [description]
     */
    protected function sendStr($callIndex, $mobile, $product)
    {
        $templeData    = getSmsTemplateCode($callIndex);
        $templateCode  = $templeData['template_code'];
        $templeContent = ['time' => $product['time'], 'address' => $product['address']];
        //发送
        $res = sendSMS($mobile, $templateCode, $templeContent);
        if ($res === true) {
            return ['code' => 1, 'message' => '发送成功'];
        } else {
            return ['code' => 0, 'message' =>  $res];
        }
    }

    protected function checkSessionTime($callIndex, $randNum)
    {
        $timeSession = 'send.' . $callIndex . '|' . $randNum;
        $time        = session($timeSession);
        return time() - $time < self::EXPIRE;
    }
}
