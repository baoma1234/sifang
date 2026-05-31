<?php
/**
 * Created by PhpStorm.
 * Date: 2019-04-03
 * Time: 12:00
 */
namespace Pay\Controller;

/**
 * 码支付接口通道支付
 * 文档地址：https://codepay.fateqq.com/apiword/
 */
class MymapayController extends PayController
{
    protected $http_type = '';
    public function __construct()
    {
        $this->http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
        parent::__construct();
    }

    //支付
    public function Pay($array)
    {
        $orderid     = I("request.pay_orderid");
        $body        = I('request.pay_productname');

        $parameter = array(
            'code'         => 'Mymapay', // 通道名称
            'title'        => '码支付通道',
            'exchange'     => 1, // 金额比例
            'gateway'      => '',
            'orderid'      => '',
            'out_trade_id' => $orderid,
            'body'         => $body,
            'channel'      => $array,
        );

        // 订单号，可以为空，如果为空，由系统统一的生成
        $return = $this->orderadd($parameter);
        $return['subject'] = $body;

        //报备参数
//        print_r($return);exit;
//         $return
        //Array
        //(
        //    [mch_id] => 180120080
        //    [signkey] => 4jwwc6i4kvma33hq9bju1l5hew98968r
        //    [appid] => 901
        //    [appsecret] => http://pay.baofupay.cn/Pay_Index.html
        //    [gateway] =>
        //    [notifyurl] => http://www.momofupay.com/Pay_RedcloudWxscan_notifyurl.html
        //    [callbackurl] => http://www.momofupay.com/Pay_RedcloudWxscan_callbackurl.html
        //    [unlockdomain] =>
        //    [amount] => 1
        //    [bankcode] => 901
        //    [code] => Wxh5
        //    [orderid] => 20190404222417491005
        //    [out_trade_id] => E2019040422241331978
        //    [subject] => 测试商品
        //    [datetime] => 2019-04-04 22:24:17
        //    [status] => success
        //)

        if($return['unlockdomain']) {
            $rpay_url = $return['unlockdomain'];
        } else {
            $rpay_url = $this->_site;
        }
        $notifyurl   = $rpay_url . 'Pay_'.$parameter['code'].'_notifyurl.html'; //异步通知
        $callbackurl = $rpay_url . 'Pay_'.$parameter['code'].'_callbackurl.html'; //返回通知

        $notifyUrl = $notifyurl; //异步通知
        $returnUrl = $callbackurl; //返回通知

        $url = $return['appsecret'];        // 支付发起连接
        $merchant = $return['mch_id'];  // 商户编号 商户后台API管理获取
        $pay_code = $return['appid'];   // 支付类型
        $Md5key = $return['signkey'];          // 商户APIKEY 商户后台API管理获取

        $amount = number_format(floatval($return['amount']),2, '.', '');//订单金额（元，两位小数）


        //↓↓↓↓↓↓↓↓↓↓请在这里配置您的基本信息↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓
        //codepay平台的ID，由纯数字组成的字符串，查看地址：https://codepay.fateqq.com/admin/#/dataSet.html
        error_reporting(E_ALL & ~E_NOTICE); //过滤脚本提醒
        date_default_timezone_set('PRC'); //时区设置 解决某些机器报错
        $codepay_config['id'] = $merchant;
        /**
         * MD5密钥，安全检验码，由数字和字母组成字符串，需要跟服务端一致
         * 设置地址：https://codepay.fateqq.com/admin/#/dataSet.html
         * 该值非常重要 请不要泄露 否则会影响支付的安全。 如泄露请重新到云端设置
         */
        $codepay_config['key'] = $Md5key;

        //字符编码格式 目前支持 gbk GB2312 或 utf-8 保证跟文档编码一致 建议使用utf-8
        $codepay_config['chart'] = strtolower('utf-8');
        header('Content-type: text/html; charset=' . $codepay_config['chart']);

        //是否启用免挂机模式 1为启用. 未开通请勿更改否则资金无法及时到账
        $codepay_config['act'] = '0'; //认证版则开启 一般情况都为0

        /**订单支付页面显示方式
         * 3：自定义开发模式 (默认 复杂 需要一定开发能力  codepay.php修改收银台代码)
         * 4：高级模式(复杂 需要较强的开发能力   codepay.php修改收银台代码)
         */
        $codepay_config['page'] = 4; //支付页面展示方式

        //支付页面风格样式 仅针对$codepay_config['page'] 参数为 1或2 才会有用。
        $codepay_config['style'] = 1; //暂时保留的功能 后期会生效 留意官网发布的风格编号


        //二维码超时设置  单位：秒
        $codepay_config['outTime'] = 360;//360秒=6分钟 最小值60  不建议太长 否则会影响其他人支付

        //最低金额限制
        $codepay_config['min'] = 0.01;

        //启用支付宝官方接口 会员版授权后生效
        $codepay_config['pay_type'] = 1;

        $codepay_config['user'] = 'admin'; //这是默认的充值用户 因为我们演示的数据库充值 只有该用户名 如正式使用请为空
        $codepay_config['userOff'] = false; //这里设置是否显示出来用户输入用户名 除非你知道了如何获取到用户 否则不要更改
        define('HTTPS', false);  //是否HTTPS站点 false为HTTP true为HTTPS

        $codepay_config['gateway'] = '';  //设置支付网关
        $codepay_config['host'] = $this->http_type . $_SERVER['HTTP_HOST']; //获取域名
        $codepay_config['path'] = $codepay_config['host'] . dirname($_SERVER['REQUEST_URI']); //API安装路径 最终为http://域名/codepay
        $codepay_config['path'] = '/Public/Pay/mapay';

        //二维码本地实现 传入http://baidu.com 会加载http://baidu.com/?money=1&tag=0&type=1
        // qrcode.php 为我们的演示控制二维码程序 删除下行注释【//】可以启用本地二维码 二维码上传至qr 目录下的1 2 3
        // $codepay_config['qrcode_url'] = $codepay_config['path'].'/qrcode.php';

        /**
         * 同步通知设置：
         * 同步通知用户关闭网页后则不会通知 通知地址公开的
         * 返回的参数通过MD5加密处理 返回target参数为get 则为同步通知数据
         * 设置通知地址不能附带任何参数，否则您需要自行验证签名或自行在验证数据签名前将$_GET['同步地址中的参数名']去掉
         * 以下为设置同步地址：(绝对路径)
         * http://你的域名/codepay/return.php
         */
        $codepay_config['return_url'] = $returnUrl; //自动生成跳转地址

        //设置默认通知页面 3秒后跳转到首页
        $codepay_config['go_time'] = 3; //3秒跳转页面 默认为首页

        //可以删除下面【//】改成自己的  以下为跳转到百度的例子
        $codepay_config['go_url'] = $returnUrl;

        /**
         * 异步通知设置：
         * 设置异步通知也可以到软件或者码支付云端设置。 如留空则会启用云端默认的通知地址
         * (异步通知可保证通知的可靠性及保密性 不传入仅在云端设置后则仅您与云端知道 安全级别更高)
         */

        $codepay_config['notify_url'] = $notifyUrl; //自动生成通知地址 优先级最高不传入则为系统设置里设置


        /**
         * 接收表单的数据 无需改动
         * 需要注意：pay_id 云端限制字符长度为100；太长会返回too long错误
         */

        $user = $return['orderid']; // 平台订单号 //提交的用户  也可以去session中取登录用户 $_SESSION['uid']
        $pay_id = $user; //网站唯一标识 需要充值的用户名，用户ID或者自行创建订单 建议传递用户的ID
        $price = (float)$return['amount']; // 订单金额
        $param = empty($return['subject'])?'测试产品':trim($return['subject']); //自定义参数  可以留空 传递什么返回什么 用于区分游戏分区或用户身份

//        if ($price <= 0) $price = (float)$_GET['price']; // 订单金额

        $type = $pay_code; // 支付类型(1支付宝 2QQ钱包 3微信)

        $codepay_path = $codepay_config['path']; //设置codepay静态资源使用路径

        /**
         *
         * 增加付款记录到数据库的方法演示代码
         * require_once("includes/MysqliDb.class.php");//导入mysqli连接
         * require_once("includes/M.class.php");//导入mysqli操作类
         * $m=new M(); //创建连接数据库类
         *
         * //以下4个参数是为了演示向数据库插入记录
         * $pay_no=time();
         * $pay_time=time();
         * $creat_time=time();
         * $status=0;
         *
         * $sql="INSERT INTO `codepay_order` (`pay_id`, `money`, `price`, `type`, `pay_no`, `param`, `pay_time`, `pay_tag`, `status`, `creat_time`)values(?,?,?,?,?,?,?,?,?,?)";
         * $rs = $m->execSQL( $sql, array('sddissisii', $pay_id, $price, $price, $type, $pay_no, $param, $pay_time, $pay_tag, $status, $creat_time), true); //$rs返回是否执行成功 true表示返回是否成功
         *
         *
         * 查询1条信息演示代码
         * $order_id=$_GET['order_id'];
         * $sql="select * from `codepay_order` where pay_no=?";
         * $rs = $m->getOne( $sql, array('s', $order_id), false); //$rs返回查询到的结果 没有结果则返回false
         *
         *
         */

        if ($price < $codepay_config['min'])
            exit('最低限制' . $codepay_config['min'] . '元'); //检查最低限制
        $price = number_format($price, 2, '.', '');// 四舍五入只保留2位小数。
        if (empty($codepay_config) || (int)$codepay_config['id'] <= 1) {
            exit('请修改添加配置帐号和密钥');
        }

        /**
         * 一些防攻击的方法 需要先session_start();
         * 3秒内禁止刷新页面
         * 验证表单是否合法
         */

        //$_SESSION["codepay_count"] += 1;
        //if ($_SESSION["codepay_count"] > 20 || ($_SESSION["codepay_endTime"] + 3) > time()) {
        //    $_SESSION["codepay_count"] = 0;
        //    exit("您的操作太频繁请 <a href='javascript:history.go(-1);'>返回重试</a><script>setTimeout(function() {
        //  history.back(-1)
        //},3000);</script>");
        //}
        //$_SESSION["endTime"] = time();
        //
        //$salt = $_POST["salt"]; //验证表单合法性的参数
        //
        //if ($salt <> md5($_SESSION["uuid"])) exit('表单验证失败 请重新提交'); //防止跨站攻击

        if (empty($pay_id))
            exit('需要充值的用户不能为空'); //唯一用户标识 不能为空 如果是登录状态可以直接取session中的ID或用户名
        if ($codepay_config['pay_type'] == 1 && $type == 1) $codepay_config["qrcode_url"] = ''; //支付宝默认不走本地化二维码


        /**
         * 这里可以自行创建站内订单将用户提交的数据保存到数据库生成订单号
         *
         * 嫌麻烦pay_id直接传送用户ID或用户名(中文用户名请确认编码一致)
         * 我们支持GBK,gb2312,utf-8 如发送中文遇到编码困扰无法解决 可以尽量使用UTF-8
         * 万能解决方法：base64或者urlencode加密后发送我们. 处理业务的时候转回来
         */
        //构造要请求的参数数组，无需改动
        $parameter = array(
            "id" => (int)$codepay_config['id'],//平台ID号
            "type" => $type,//支付方式
            "price" => (float)$price,//原价
            "pay_id" => $pay_id, //可以是用户ID,站内商户订单号,用户名
            "param" => $param,//自定义参数
            "act" => (int)$codepay_config['act'],//此参数即将弃用
            "outTime" => (int)$codepay_config['outTime'],//二维码超时设置
            "page" => (int)$codepay_config['page'],//订单创建返回JS 或者JSON
            "return_url" => $codepay_config["return_url"],//付款后附带加密参数跳转到该页面
            "notify_url" => $codepay_config["notify_url"],//付款后通知该页面处理业务
            "style" => (int)$codepay_config['style'],//付款页面风格
            "pay_type" => $codepay_config['pay_type'],//支付宝使用官方接口
            "user_ip" => $this->getIp(),//付款人IP
            "qrcode_url" => $codepay_config['qrcode_url'],//本地化二维码
            "chart" => trim(strtolower($codepay_config['chart']))//字符编码方式
            //其他业务参数根据在线开发文档，添加参数.文档地址:https://codepay.fateqq.com/apiword/
            //如"参数名"=>"参数值"
        );

        //简单的创建订单方式
        //ksort($parameter); //重新排序$data数组
        //reset($parameter); //内部指针指向数组中的第一个元素
        //
        //$sign = ''; //初始化需要签名的字符为空
        //$urls = ''; //初始化URL参数为空
        //
        //foreach ($parameter AS $key => $val) { //遍历需要传递的参数
        //    if ($val == ''||$key == 'sign') continue; //跳过这些不参数签名
        //    if ($sign != '') { //后面追加&拼接URL
        //        $sign .= "&";
        //        $urls .= "&";
        //    }
        //    $sign .= "$key=$val"; //拼接为url参数形式
        //    $urls .= "$key=" . urlencode($val); //拼接为url参数形式并URL编码参数值
        //
        //}
        //$query = $urls . '&sign=' . md5($sign .$codepay_config['key']); //创建订单所需的参数
        //$url = "http://api2.fateqq.com:52888/creat_order/?{$query}"; //支付页面
        //
        //header("Location:{$url}"); //跳转到支付页面


        $back = $this->create_link($parameter, $codepay_config['key']);


        switch ((int)$type) {
            case 1:
                $typeName = '支付宝';
                break;
            case 2:
                $typeName = 'QQ';
                break;
            default:
                $typeName = '微信';
        }

        //准备传给前端输出的JSON
        $user_data = array("return_url" => $parameter["return_url"],
            "type" => $parameter['type'], "outTime" => $parameter["outTime"], "codePay_id" => $parameter["id"], "logoShowTime" => 2);

        $user_data["qrcode_url"] = $codepay_config["qrcode_url"]; //本地二维码控制器

        //中间那logo 默认为2秒后隐藏
        //改为自己的替换img目录下的use_开头的图片 你要保证你的二维码遮挡不会影响扫码
        //二维码容错率决定你能遮挡多少部分
        $user_data["logoShowTime"] = 2;


        /**
         * 高级模式 云端创建订单。(注意不要外泄密钥key)
         * 可自行根据订单返回的参数做一些高级功能。 以下demo只是简单的功能 其他需要自行开发
         * 比如根据money type 参数调用本地的二维码图片。
         * 比如根据云端订单状态创建失败 展示自定义转账的二维码。
         * 比如可自行开发付款后的同步通知实现。
         * 比如可自行开发软件端某个支付方式掉线。 自动停用该付款方式。
         * 如使用云端同步通知  请附带必要的参数 码支付的用户id,pay_id,type,money,order_id,tag,notiry_key
         * 必须将notiry_key参数返回 因为该参数为服务解密参数(会随时变化)。否则影响云端同步通知
         */

        if ($parameter['page'] != 3) { //只要不为3 返回JS 就去服务器加载资源
            $parameter['page'] = "4"; //设置返回JSON
            $back = $this->create_link($parameter, $codepay_config['key'],$codepay_config['gateway']); //生成支付URL
            if (function_exists('file_get_contents')) { //如果开启了获取远程HTML函数 file_get_contents
                $codepay_json = file_get_contents($back['url']); //获取远程HTML
            } else if (function_exists('curl_init')) {
                $ch = curl_init(); //使用curl请求
                $timeout = 5;
                curl_setopt($ch, CURLOPT_URL, $back['url']);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
                $codepay_json = curl_exec($ch);
                curl_close($ch);
            }
        }

        if (empty($codepay_json)) { //如果没有获取到远程HTML 则走JS创建订单
            $parameter['call'] = "callback";
            $parameter['page'] = "3";
            $back = $this->create_link($parameter, $codepay_config['key'],'http://api2.xiuxiu888.com/creat_order/?');
            $codepay_html = '<script src="' . $back['url'] . '"></script>'; //JS数据
        } else { //获取到了JSON
            $codepay_data = json_decode($codepay_json);
            $qr = $codepay_data ? $codepay_data->qrcode : '';
            $codepay_html = "<script>callback({$codepay_json})</script>"; //JSON数据
        }

        if(!empty($qr)){
            // 将连接保存到本地
            $name = 'mazhifu/qrcode_'.$return['orderid'].'.jpg';
            $qr_old = $qr;
            file_put_contents($name,file_get_contents($qr_old));
            $qr = $codepay_config['host'].'/'.$name;
            $codepay_html = str_replace($qr_old,$qr,$codepay_html);
        }

        $this->assign('codepay_config',$codepay_config);
        $this->assign('typeName',$typeName);
        $this->assign('codepay_path',$codepay_path);
        $this->assign('type',$type);
        $this->assign('price',$price);
        $this->assign('qr',$qr);
        $this->assign('codepay_html',$codepay_html);
        $this->assign('user_data_json',json_encode($user_data));

        $this->display("WeiXin/mapay");
    }


    //同步通知
    public function callbackurl()
    {
        $fileName = 'Mymapay';
        $logName = 'logname.html';
// 向后追加，回调记录数据
        file_put_contents($logName, '<br/>'.$fileName.'['.date('Y-m-d H:i:s',time()).']REQUEST[callback]'.json_encode($_REQUEST).'<br/>',FILE_APPEND);
        file_put_contents($logName, '<br/>'.$fileName.'['.date('Y-m-d H:i:s',time()).']get[callback]'.json_encode($_GET).'<br/>',FILE_APPEND);
        file_put_contents($logName, '<br/>'.$fileName.'['.date('Y-m-d H:i:s',time()).']post[callback]'.json_encode($_POST).'<br/>',FILE_APPEND);

        $orderNo = $_REQUEST['pay_id'];// isset($_REQUEST['pay_id']) ? $_REQUEST['pay_id'] : ''; //订单号

        $Order      = M("Order");
        $pay_status = $Order->where(['pay_orderid' => $orderNo])->getField("pay_status");
          $callbackurl = $Order->where(['pay_orderid' => $orderNo])->getField("pay_callbackurl");
       // var_dump($pay_status);
        if ($pay_status != 0) {
        //  $this->EditMoney($orderNo, $fileName, 2); //第三个参数为1时，页面会跳转到订单信息里面的 pay_callbackurl
          echo '支付成功，即将跳转至播放页。。。';
            header("location:$callbackurl");
        } else {
            exit("error");
            
        }
    }

    //异步通知
    public function notifyurl()
    {
    	
    
        $fileName = 'Mymapay';
        $logName = 'logname.html';
// 向后追加，回调记录数据
        file_put_contents($logName, '<br/>'.$fileName.'['.date('Y-m-d H:i:s',time()).']REQUEST[notify]'.json_encode($_REQUEST).'<br/>',FILE_APPEND);
        file_put_contents($logName, '<br/>'.$fileName.'['.date('Y-m-d H:i:s',time()).']get[notify]'.json_encode($_GET).'<br/>',FILE_APPEND);
        file_put_contents($logName, '<br/>'.$fileName.'['.date('Y-m-d H:i:s',time()).']post[notify]'.json_encode($_POST).'<br/>',FILE_APPEND);

        if (empty($_POST)) { //如果GET访问
            $_POST = $_GET;  //POST访问 为服务器或软件异步通知  不需要返回HTML
        }
        if (empty($_POST)) { //如果GET访问
            if(!empty($_REQUEST) && !empty($_REQUEST['PHPSESSID'])){
                unset($_REQUEST['PHPSESSID']);
            }
            if(!empty($_REQUEST) && !empty($_REQUEST['think_language'])){
                unset($_REQUEST['think_language']);
            }
            $_POST = $_REQUEST;
        }
        ksort($_POST); //排序post参数
        reset($_POST); //内部指针指向数组中的第一个元素


        $sign = ''; //加密字符串初始化

        foreach ($_POST AS $key => $val) {
            if ($val == '' || $key == 'sign') continue; //跳过这些不签名
            if ($sign) $sign .= '&'; //第一个字符串签名不加& 其他加&连接起来参数
            $sign .= "$key=$val"; //拼接为url参数形式
        }
        $pay_id = $_POST['pay_id']; //需要充值的ID 或订单号 或用户名
//        $money = (float)$_POST['money']; //实际付款金额
//        $price = (float)$_POST['price']; //订单的原价
//        $param = $_POST['param']; //自定义参数
//        $type = (int)$_POST['type']; //支付方式
        $pay_no = $_POST['pay_no'];//流水号

        $codepay_key = getKey($pay_id); // 商户号、key，API平台提供
  
        if (!$_POST['pay_no'] || md5($sign . $codepay_key) != $_POST['sign']) { //不合法的数据
            exit('signerror');
        } else { //合法的数据
            //业务处理
            if (!empty($pay_no)) { //返回的是业务处理完成
                $name = 'mazhifu/qrcode_'.$pay_id.'.jpg';
                if(file_exists($name)) {
                    unlink($name);
                }

                $this->EditMoney($pay_id, $fileName, 0);
                exit('success');
            } else {
                exit('fail');
            }
        }
    }

    /**
     * 加密函数
     * @param $params 需要加密的数组
     * @param $codepay_key //码支付密钥
     * @param string $host //使用哪个域名
     * @return array
     */
    public function create_link($params, $codepay_key, $host = "")
    {
        ksort($params); //重新排序$data数组
        reset($params); //内部指针指向数组中的第一个元素
        $sign = '';
        $urls = '';
        foreach ($params AS $key => $val) {
            if ($val == '') continue;
            if ($key != 'sign') {
                if ($sign != '') {
                    $sign .= "&";
                    $urls .= "&";
                }
                $sign .= "$key=$val"; //拼接为url参数形式
                $urls .= "$key=" . urlencode($val); //拼接为url参数形式
            }
        }

        $key = md5($sign . $codepay_key);//开始加密
        $query = $urls . '&sign=' . $key; //创建订单所需的参数
        $apiHost = ($host ? $host : "http://api2.fateqq.com:52888/creat_order/?"); //网关
        $url = $apiHost . $query; //生成的地址
        return array("url" => $url, "query" => $query, "sign" => $sign, "param" => $urls);
    }

    //获取客户端IP地址
    public function getIp()
    { //取IP函数
        static $realip;
        if (isset($_SERVER)) {
            if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $realip = $_SERVER['HTTP_X_FORWARDED_FOR'];
            } else {
                $realip = isset($_SERVER['HTTP_CLIENT_IP']) ? $_SERVER['HTTP_CLIENT_IP'] : $_SERVER['REMOTE_ADDR'];
            }
        } else {
            if (getenv('HTTP_X_FORWARDED_FOR')) {
                $realip = getenv('HTTP_X_FORWARDED_FOR');
            } else {
                $realip = getenv('HTTP_CLIENT_IP') ? getenv('HTTP_CLIENT_IP') : getenv('REMOTE_ADDR');
            }
        }
        return $realip;
    }
}
