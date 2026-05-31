<?php

namespace Pay\Controller;

class CeshiController extends PayController
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 支付入口：
     * 1. 按金额判断通道
     * 2. 检查同金额是否已有未完成订单
     * 3. 从二维码池取当前有效二维码
     * 4. 创建 pay_order
     * 5. 跳转展示页
     */
    public function Pay($array)
    {
        // $moneyMap = [
        //     0 => 10,
        //     1 => 13,
        //     2 => 18,
        //     3 => 20,
        //     4 => 25,
        // ];
        
        // // 获取提交的金额
        // $payAmount = I('pay_amount'); 
        
        // // 判断 $payAmount 是否在 $moneyMap 的值中
        // if (in_array($payAmount, $moneyMap)) {
        //     // 金额合法，继续执行下单逻辑
        // } else {
        //     // 金额不合法，提示错误
        //     $this->error('不合法的支付金额！');
        // }
         $existOrder = M('Order')->where([
            'pay_tongdao' => 'ceshi',
            'pay_amount'  => I('request.pay_amount'),
            'pay_status'  => ['in', '0'],
            'isdel'       => 0,
        ])->find();
         
        if ($existOrder) {
            $this->error('通道繁忙');
        }
        $orderid = I("request.pay_orderid");
        $body = I('request.pay_productname');
        $notifyurl = $this->_site . 'Pay_Huazf_notifyurl.html'; //异步通知
        $callbackurl = $this->_site . 'Pay_Huazf_callbackurl.html'; //返回通知
        $parameter = array(
            'code' => 'Huazf', // 通道名称
            'title' => 'zfbh5',
            'exchange' => 1, // 金额比例
            'gateway' => '',
            'out_trade_id' => $orderid,
            'body' => $body,
            'channel' => $array
        );
        
        // 订单号，可以为空，如果为空，由系统统一的生成
        $return = $this->orderadd($parameter);
    
        $money = $return['amount'];    //交易金额
        $outTradeId = $return['orderid'];
        if ($money <= 0) {
            $this->error('金额不正确');
        }

        // 同金额未完成订单拦截（按 pay_order 表字段）
       

        $channelNo = $this->getChannelNoByMoney($money);
        if (!$channelNo) {
            $this->error('未找到对应通道');
        }

        // 从二维码池读取当前有效二维码
        $qrcodeRow = M('qrcode_pool')->where([
            'channel_no'  => $channelNo,
            'money'       => $money,
            'status'      => 1,
            'expire_time' => ['gt', time()],
        ])->order('id desc')->find();
     
        if (empty($qrcodeRow)) {
            $this->error('二维码不存在，请稍后再试');
        }

        if ($_REQUEST['type'] == 'json') {

        $domain = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'];
        
        // 手动拼接标准的兼容模式 URL，这样参数绝对不会丢，且任何服务器环境（Nginx/Apache）都能完美识别
        $url = $domain . __APP__ . '/Pay_Ceshi_showQrcode?' . 
               '&orderid=' . urlencode($orderid) . 
               '&money=' . urlencode($money) . 
               '&body=' . urlencode($body);
                
            $data = [
                "status"     => "1",
                "msg"        => "下单成功",
                "pay_amount" => $money,
                "pay_orderid"=> $orderid,
                "payUrl"     => $url // 此时 $url 是完整的 http://... 地址
            ];
            
            $this->ajaxReturn($data);
        
        }else{
            // 跳转到中转页，避免直接 display 导致支付页无法正常触发
            $jumpUrl = U('Pay/Ceshi/showQrcode', [
                'orderid' => $orderid,
                'money'   => $money,
                'body'    => $body,
            ]);

            $this->redirect($jumpUrl);
        }
        

    }

    /**
     * 中转展示页
     */
public function showQrcode()
    {
        $orderid = I('request.orderid', '', 'trim');
        $money   = I('request.money', 0, 'intval');
        $body    = I('request.body', '', 'trim');
        $qrcode  = I('request.qrcode', '', 'trim');
        $expire  = I('request.expire', 0, 'intval');

        if (empty($orderid) || $money <= 0) {
            $this->error('参数错误');
        }

        $orderInfo = M('Order')->where(['out_trade_id' => $orderid])->find();
        if (empty($orderInfo)) {
            $this->error('订单不存在');
        }

        if (in_array(intval($orderInfo['pay_status']), [1, 2], true)) {
            $callbackurl = isset($orderInfo['pay_callbackurl']) ? $orderInfo['pay_callbackurl'] : '';
            if ($callbackurl) {
                header('location:' . $callbackurl);
                exit;
            }
            exit('交易成功！');
        }

        if (intval($orderInfo['pay_status']) === 3) {
            $this->error('订单已超时封单');
        }

        if (empty($qrcode)) {
            $qrcodeRow = M('qrcode_pool')->where([
                'money'       => $money,
                'status'      => 1,
                'expire_time' => ['gt', time()],
            ])->order('id desc')->find();

            if (empty($qrcodeRow)) {
                $this->error('二维码不存在，请稍后再试');
            }

            $qrcode = $qrcodeRow['qrcode_url'];
            $expire = intval($qrcodeRow['expire_time']);
        }

        $this->assign('data', [
            'qrcode' => $qrcode,
            'money'  => $money,
            'orderid'=> $orderid,
            'body'   => $body,
            'expire' => $expire,
        ]);

        $this->display('showQrcode');
    }

    /**
     * 订单回调页面跳转
     */
    public function callbackurl()
    {
        $orderid = I('request.orderid', '', 'trim');
        if (empty($orderid)) {
            $orderid = I('request.out_trade_no', '', 'trim');
        }

        $Order = M('Order');
        $pay_status = $Order->where(['pay_orderid' => $orderid])->getField('pay_status');
        $callbackurl = $Order->where(['pay_orderid' => $orderid])->getField('pay_callbackurl');

        if ($pay_status <> 0) {
            if ($callbackurl) {
                header("location:$callbackurl");
                die;
            }
            exit('交易成功！');
        }

        if ($callbackurl) {
            header("location:$callbackurl");
            die;
        }
        exit('交易处理中！');
    }

    /**
     * 服务器点对点返回
     */
    public function notifyurl()
    {
        if ($_SERVER['REMOTE_ADDR'] != '39.109.112.236') {
            M('Order')->where(['pay_orderid' => $_REQUEST['orderid']])->save([
                'yichang'   => '1',
                'yichangip' => $_SERVER['REMOTE_ADDR'],
            ]);
            exit('禁止非法回调！');
        }

        if ($_REQUEST['is_finished'] == '1') {
            $orderid = $_REQUEST['job_number'];
            $str = '交易成功！订单号：' . $orderid;
            file_put_contents('success.txt', $str . "\n", FILE_APPEND);

            M('Order')->where(['pay_orderid' => $orderid])->save([
                'pay_status'      => 2,
                'pay_successdate' => time(),
                'lock_status'     => 0,
                'notify_msg'      => '支付成功',
            ]);

            exit('OK');
        }

        exit('FAIL');
    }

    /**
     * 120 秒未支付订单自动取消
     * URL 示例：/index.php/Pay/Ceshi/timeoutCancel?key=abc123
     */
    public function timeoutCancel()
    {
        $key = I('get.key', '', 'trim');
        if ($key !== 'abc123') {
            exit('forbidden');
        }

        $limitTime = time() - 120;

        $lists = M('Order')->where([
            'pay_tongdao'   => 'ceshi',
            'pay_status'    => 0,
            'pay_applydate' => ['lt', $limitTime],
            'isdel'         => 0,
        ])->select();

        if (empty($lists)) {
            echo 'no order';
            exit;
        }

        foreach ($lists as $v) {
            M('Order')->where(['id' => $v['id']])->save([
                'pay_status'  => 3,
                'lock_status' => 0,
                'notify_msg'  => '超时未支付自动取消',
            ]);
        }

        echo 'done:' . count($lists);
        exit;
    }

    /**
     * 清理过期二维码
     * URL 示例：/index.php/Pay/Ceshi/clearExpiredQrcode?key=abc123
     */
    public function clearExpiredQrcode()
    {
        $key = I('get.key', '', 'trim');
        if ($key !== 'abc123') {
            exit('forbidden');
        }

        M('qrcode_pool')->where([
            'status'      => 1,
            'expire_time' => ['lt', time()],
        ])->save([
            'status'      => 0,
            'orderid'     => '',
            'update_time' => time(),
        ]);

        echo 'qrcode cleared';
        exit;
    }

    /**
     * 每分钟刷新二维码池
     * URL 示例：/index.php/Pay/Ceshi/refreshQrcodePool?key=abc123
     */
      public function refreshQrcodePool()
    {
          
        $key = I('get.key', '', 'trim');
       
        if ($key !== 'abc123') {
            exit('forbidden');
        }

        // 只请求一次，接口返回 5 个通道二维码
        $deviceCode = $this->getDeviceCode();
        //  var_dump($deviceCode);die();
        if (empty($deviceCode)) {
            exit('device code missing');
        }

        $result = $this->fetchDeviceData($deviceCode);
        if (!is_array($result) || !isset($result['code']) || intval($result['code']) !== 0) {
            exit('fetch failed');
        }

        $data = isset($result['rows']) ? $result['rows'] : [];
        if (empty($data) || !is_array($data)) {
            exit('empty data');
        }

        // 你的返回结构里 data 是 0-4 共 5 个通道
        $moneyMap = [
            0 => 10,
            1 => 13,
            2 => 18,
            3 => 20,
            4 => 25,
        ];
      
        $success = 0;
        foreach ($data as $idx => $item) {
            if (!isset($moneyMap[$idx])) {
                continue;
            }

            $channelNo = $idx + 1;
            $money = $moneyMap[$idx];
            $qrcode = isset($item['scanCode']) ? $item['scanCode'] : '';
            if (empty($qrcode)) {
                continue;
            }
          // var_dump($qrcode);die();
            $now = time();
            $expire = $now + 60;
            $row = [
                'channel_no'  => $channelNo,
                'money'       => $money,
                'qrcode_url'  => $qrcode,
                'qrcode_data' => json_encode($item, JSON_UNESCAPED_UNICODE),
                'status'      => 1,
                'orderid'     => '',
                'create_time' => $now,
                'expire_time' => $expire,
                'update_time' => $now,
            ];

            $exist = M('qrcode_pool')->where([
                'channel_no' => $channelNo,
                'money'      => $money,
            ])->find();

            if ($exist) {
                M('qrcode_pool')->where(['id' => $exist['id']])->save($row);
            } else {
                M('qrcode_pool')->add($row);
            }

            $success++;
        }

        echo 'ok:' . $success;
        exit;
    }

    /**
     * 金额 -> 通道号
     */
    private function getChannelNoByMoney($money)
    {
        $map = [
            10 => 1,
            13 => 2,
            18 => 3,
            20 => 4,
            25 => 5,
        ];

        $money = intval($money);
        return isset($map[$money]) ? $map[$money] : 0;
    }
     private function getDeviceCode()
    {
        return '867272085449731';
    }

    /**
     * 通道号 -> 设备码
     */
    private function getDeviceCodeByChannel($channelNo)
    {
        $deviceMap = [
            1 => '867272085449731',
            2 => '通道2设备码',
            3 => '通道3设备码',
            4 => '通道4设备码',
            5 => '通道5设备码',
        ];

        return isset($deviceMap[$channelNo]) ? $deviceMap[$channelNo] : '';
    }

    /**
     * 请求三方接口获取二维码数据
     */
    private function fetchDeviceData($deviceCode)
    {

        $cookieFile = RUNTIME_PATH . 'jiuaigou_cookie.txt';
        
        // 1. 定义请求的目标 URL（扫码批量创建接口）
$targetUrl = 'https://jiuaigou.net/bs/biz/cargo/batch/create/scanCode';

// 2. 组装 POST 数据（对应 --data-raw）
// 注意：ids 里面的逗号直接写在数组里，http_build_query 会自动帮我们编码为 %2C
$postData = [
    'ids' => '29046,30608,30609,30610,30611'
];

// 转换为标准表单字符串：ids=29046%2C30608%2C30609%2C30610%2C30611
$postString = http_build_query($postData);

// 3. 复刻请求头 Headers
$headers = [
    'Accept: application/json, text/javascript, */*; q=0.01',
    'Accept-Language: zh-CN,zh;q=0.9,zh-HK;q=0.8',
    'Connection: keep-alive',
    'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
    'Origin: https://jiuaigou.net',
    'Referer: https://jiuaigou.net/bs/biz/cargo?deviceId=4351&time=1780132991540',
    'Sec-Fetch-Dest: empty',
    'Sec-Fetch-Mode: cors',
    'Sec-Fetch-Site: same-origin',
    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36',
    'X-Requested-With: XMLHttpRequest',
    'sec-ch-ua: "Google Chrome";v="147", "Not.A/Brand";v="8", "Chromium";v="147"',
    'sec-ch-ua-mobile: ?0',
    'sec-ch-ua-platform: "Windows"'
];

// 4. 初始化 cURL
$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, $targetUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postString);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);

// 绕过 SSL 安全证书本地验证
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

// 5. 执行请求并获取响应
$response = curl_exec($ch);

// 6. 异常排查
if (curl_errno($ch)) {
    $errorMsg = curl_error($ch);
    curl_close($ch);
    $this->error('cURL 批量扫码接口请求失败: ' . $errorMsg);
}

curl_close($ch);


      // 1. 定义请求的目标 URL
        $targetUrl = 'https://jiuaigou.net/bs/biz/cargo/list';
        
        // 2. 组装 POST 表单数据（对应 --data-raw）
        $postData = [
            'pageSize'      => 10,
            'pageNum'       => 1,
            'orderByColumn' => 'number',
            'isAsc'         => 'asc',
            'deviceId'      => 4351,
            'useStatus'     => '',
            'status'        => ''
        ];
        
        // 将数组转换为 application/x-www-form-urlencoded 标准的字符串格式
        $postString = http_build_query($postData);
        
        // 3. 完美复刻原 cURL 中的所有请求头
        $headers = [
            'Accept: application/json, text/javascript, */*; q=0.01',
            'Accept-Language: zh-CN,zh;q=0.9,zh-HK;q=0.8',
            'Connection: keep-alive',
            'Content-Type: application/x-www-form-urlencoded',
            'Origin: https://jiuaigou.net',
            'Referer: https://jiuaigou.net/bs/biz/cargo?deviceId=4351&time=1780127875990',
            'Sec-Fetch-Dest: empty',
            'Sec-Fetch-Mode: cors',
            'Sec-Fetch-Site: same-origin',
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36',
            'X-Requested-With: XMLHttpRequest',
            'sec-ch-ua: "Google Chrome";v="147", "Not.A/Brand";v="8", "Chromium";v="147"',
            'sec-ch-ua-mobile: ?0',
            'sec-ch-ua-platform: "Windows"'
        ];
        
        // 4. 初始化 cURL 并配置参数
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $targetUrl);
        curl_setopt($ch, CURLOPT_POST, true);                 // 声明为 POST 请求
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postString);     // 注入表单数据
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);       // 注入 Headers
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);        // 将返回结果保存到变量，而不是直接直出打印
        
        // 核心参数：完美带上 JSESSIONID 的 Cookie 会话
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
        
        // 绕过 SSL 证书检测（防止服务器因为找不到本地 CA 证书直接报错导致抓取失败）
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        
        // 5. 执行请求并获取结果
        $response = curl_exec($ch);
        
        // 6. 异常与错漏检测
        if (curl_errno($ch)) {
            $errorMsg = curl_error($ch);
            curl_close($ch);
            // 在 ThinkPHP 中返回错误提示
            $this->error('cURL 请求失败: ' . $errorMsg);
        }
        
        curl_close($ch);
        
        // 7. 处理返回数据
        // 此时 $response 是对方返回的原始 JSON 字符串，如果是做接口，可以直接输出
       
        return json_decode($response, true);
    }

    /**
     * 获取真实IP
     */
    public function getIp()
    {
        if (getenv('REMOTE_ADDR') && strcasecmp(getenv('REMOTE_ADDR'), 'unknown')) {
            $ip = getenv('REMOTE_ADDR');
        } elseif (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], 'unknown')) {
            $ip = $_SERVER['REMOTE_ADDR'];
        } else {
            $ip = '0.0.0.0';
        }

        return $ip;
    }
}
