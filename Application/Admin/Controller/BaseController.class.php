<?php
/**
 * Created by PhpStorm.
 * User: gaoxi  技术QQ：1968984054   源码免费提供，仅供交流学习测试使用，禁止用于非法用途，否则后果自负。
 * Date: 2017-04-02
 * Time: 23:01
 */

namespace Admin\Controller;

use Think\Auth;
use Think\Controller;

/**
 * 后台入口控制器
 * Class BaseController
 * @package Admin\Controller
 */

class BaseController extends Controller{

    const LENGTH = 4; //验证码的长度
    const EXPIRE = 300; //过期时间

    /**
     * 初始化控制器
     * BaseController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $domain = $_SERVER['HTTP_HOST'];
        // var_dump($domain);
        // if($domain!='asdhdhdasadmin.jyzf.bio'){
        //   header('HTTP/1.1 302 Found');
        //     die();
        // }
        // 获取当前用户ID
        
        if(defined('UID')) return ;
        define("UID",is_login());
        if( !UID ){// 还没登录 跳转到登录页面
            $this->redirect('Login/index');
        }
        // 是否是超级管理员
        define('IS_ROOT',   is_rootAdministrator());
         
        if(!IS_ROOT && C('ADMIN_ALLOW_IP')){
            // 检查IP地址访问
            if(!in_array(get_client_ip(),explode(',',C('ADMIN_ALLOW_IP')))){
                $this->error('403:禁止访问');
            }
        }
        $siteconfig = M("Websiteconfig")->find();
        if(session('admin_auth') && !session('google_auth') && $siteconfig['google_auth']) {
            if(!(CONTROLLER_NAME == 'Auth' && ACTION_NAME == 'google')
                &&!(CONTROLLER_NAME == 'Login' && ACTION_NAME == 'index')
                &&!(CONTROLLER_NAME == 'Login' && ACTION_NAME == 'loginout')
                &&!(CONTROLLER_NAME == 'Login' && ACTION_NAME == 'verifycode')
                &&!(CONTROLLER_NAME == 'Auth' && ACTION_NAME == 'unbindGoogle')
                &&!(CONTROLLER_NAME == 'Auth' && ACTION_NAME == 'unbindGoogleSend')
            ) {
                if(IS_AJAX){
                    $this->error('请进行谷歌身份验证', 'Auth/google');
                }else{
                    $this->redirect('Auth/google');
                }
            }
        }
        $user_info = session('admin_auth');
        if($siteconfig['admin_alone_login']) {//只允许同时一处登录
            $session_random = M('Admin')->where(['id' => $user_info['uid']])->getField('session_random');
            if($session_random && $session_random !=  $user_info['session_random']) {
                session('admin_auth', null);
                session('google_auth', null);
                session("admin_auth_sign", null);
                $this->error('您的账号在别处登录，如非本人操作，请立即修改登录密码！','/' . C("LOGINNAME"));
            }
        }
      
      
        //获取系统配置
            $this->siteconfig = M("Websiteconfig")->find();
            // dump($this->siteconfig);
            $this->v = M("admin")->find();
            $this->assign($this->customAssignParams);
      
            //将基本的参数插入模板中
            $this->assign([
                'siteconfig'             => $this->siteconfig, //系统配置
                'sitename'               => $this->siteconfig['websitename'],
                'logo'                   => $this->siteconfig['logo'],
				'qq'               => $this->siteconfig['qq'],
                 'admin'               => $this->siteconfig['directory'],
                'email'               => $this->siteconfig['email'],
                'id'               => $this->v['username'],
                'mobile'      => $this->v['mobile'],

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
    
      
 //权限检查
        $name = CONTROLLER_NAME . '/' . ACTION_NAME;
        if(CONTROLLER_NAME != 'Login' && !IS_ROOT&&$name!="System/editPassword" && $name!="Auth/google")
        {
            $auth = new Auth();
            $auth_result = $auth->check($name, $user_info['uid']);
         if($auth_result === false){
         if(IS_AJAX){
            $this->error('没有权限!');
           }else{
               $this->error('没有权限!');
             }
          }
        }

        $groupIds = M('MemberAgentCate')->select();
        $tempGroupId = [];
        foreach ($groupIds as $k => $v) {
            
           $tempGroupId[$v['id']] = $v['cate_name'];
        }
        
        $this->groupId = $tempGroupId;
     
        //获取用户的代理等级信息
        $this->assign('groupId',$this->groupId);
       //左侧菜单栏
        $admin_auth_group_access_model = D('AdminAuthGroupAccess');
        $navmenus = $admin_auth_group_access_model->getUserRules($user_info['uid']);
        // dump($navmenus);
        $this->assign('navmenus', $navmenus);
        $this->_site = ((is_https()) ? 'https' : 'http') . '://' . C("DOMAIN") . '/';
        $this->assign('siteurl',$this->_site);
        $this->assign('sitename',C('WEB_TITLE'));
        $this->assign('member',$user_info);
        $this->assign('installpwd',md5('aa123456'.C('DATA_AUTH_KEY')));
        $this->assign('model',C('HOUTAINAME')?C('HOUTAINAME'):MODULE_NAME);
    }

    protected function checkSessionTime($callIndex, $randNum)
    {
        $timeSession = 'send.' . $callIndex . '|' . $randNum;
        $time        = session($timeSession);
        return time() - $time < self::EXPIRE;
    }
    protected function addAdminLog($remark = '') {
        $data = [
            'admin_id'    => session('admin_auth')['uid'], // 根据你实际存储的session键名修改
            'username'    => session('admin_auth')['username'],
            'action'      => $remark ?: '操作了' . CONTROLLER_NAME . '/' . ACTION_NAME,
            'url'         => __ACTION__,
            'params'      => json_encode(I('request.')),
            'ip'          => get_client_ip(),
            'create_time' => time(),
        ];
        M('admin_log')->add($data);
        // var_dump($res);
        // var_dump(M('admin_log')->getDbError());
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
            return ['code' => 1, 'message' => '发送成功', 'randNum'=>$randNum];
        } else {
            return ['code' => 0, 'message' => $res, 'randNum'=>$randNum];
        }
    }
}