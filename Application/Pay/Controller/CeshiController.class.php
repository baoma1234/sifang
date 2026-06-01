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
        $requestMoney = floatval(I('request.pay_amount'));
        if ($requestMoney <= 0) {
            $this->error('金额不正确');
        }

        $merchant = $this->pickFreeMerchantAccount($requestMoney);
        if (empty($merchant)) {
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

        $this->reserveMerchantForOrder($merchant['id'], $money, $outTradeId);
        $qrcodeRow = $this->fetchMerchantQrcode($merchant, $money, $outTradeId);
        if (empty($qrcodeRow) || empty($qrcodeRow['qrcode'])) {
            $this->releaseMerchantByMoney($merchant['id'], $money);
            $this->error('二维码不存在，请稍后再试');
        }
        M('Order')->where(array('pay_orderid' => $outTradeId))->save(array(
            'account_id' => intval($merchant['id']),
            'qrcode_url' => $qrcodeRow['qrcode'],
            'bind_money' => $money,
        ));

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

        if (empty($qrcode)) {
            $orderInfo = M('Order')->where(['pay_orderid' => $orderid])->find();
            if (!empty($orderInfo) && !empty($orderInfo['account_id'])) {
                $merchant = M('channel_account')->where(['id' => intval($orderInfo['account_id'])])->find();
                if (!empty($merchant)) {
                    $qrcodeRow = $this->fetchMerchantQrcode($merchant, floatval($orderInfo['bind_money']), $orderid);
                    if (!empty($qrcodeRow) && !empty($qrcodeRow['qrcode'])) {
                        $qrcode = $qrcodeRow['qrcode'];
                        $expire = $qrcodeRow['expire'];
                    }
                }
            }
        }

        $orderInfo = M('Order')->where(['pay_orderid' => $orderid])->find();
        if (empty($orderInfo)) {
            $this->error('订单不存在');
        }

        $merchantId = intval($orderInfo['account_id']);
        if (!$merchantId) {
            $this->error('商户信息缺失');
        }
        $merchant = M('channel_account')->where(['id' => $merchantId])->find();
        if (empty($merchant)) {
            $this->error('商户不存在');
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

        $this->assign('data', [
            'qrcode' => '',
            'money'  => $money,
            'orderid'=> $orderid,
            'body'   => $body,
            'expire' => time() + 60,
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

            $orderInfo = M('Order')->where(['pay_orderid' => $orderid])->find();
            M('Order')->where(['pay_orderid' => $orderid])->save([
                'pay_status'      => 2,
                'pay_successdate' => time(),
                'lock_status'     => 0,
                'notify_msg'      => '支付成功',
            ]);
            if (!empty($orderInfo)) {
                $this->releaseMerchantByMoney(intval($orderInfo['account_id']), floatval($orderInfo['pay_amount']));
            }

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
            $this->releaseMerchantByMoney(intval($v['account_id']), floatval($v['pay_amount']));
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

        $expired = M('qrcode_pool')->where([
            'status'      => 1,
            'expire_time' => ['lt', time()],
        ])->select();
        M('qrcode_pool')->where([
            'status'      => 1,
            'expire_time' => ['lt', time()],
        ])->save([
            'status'      => 0,
            'orderid'     => '',
            'update_time' => time(),
        ]);
        if (!empty($expired)) {
            foreach ($expired as $row) {
                $this->releaseMerchantByMoney(intval($row['account_id']), floatval($row['money']));
            }
        }

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

        $moneyMap = array(10, 13, 18, 20, 25);
        $success = 0;
        $skip = 0;

        foreach ($moneyMap as $money) {
            $account = $this->pickFreeMerchantAccount($money);
            if (empty($account)) {
                $skip++;
                continue;
            }

            $result = $this->fetchMerchantQrcode($account, $money, 'pool_' . $money . '_' . time());
            if (empty($result) || empty($result['qrcode'])) {
                $skip++;
                continue;
            }

            $this->markMerchantBusy($account['id'], $money, $result['qrcode']);
            $success++;
        }

        echo 'ok:' . $success . ',skip:' . $skip;
        exit;
    }

    /**
     * 金额 -> 通道号
     */
    protected function parseAmountPool($value)
    {
        $value = trim((string)$value);
        if ($value === '') {
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

    protected function getChannelNoByMoney($money)
    {
        return intval($money);
    }

    private function pickFreeMerchantAccount($money)
    {
        $accounts = M('channel_account')->where(array(
            'status' => 1,
            'offline_status' => 1,
            'cookie_status' => 1,
        ))->order('last_paying_time asc,weight desc,id asc')->select();
        if (empty($accounts)) {
            return array();
        }

        $amount = (string)intval(round($money));
        $matched = array();
        foreach ($accounts as $account) {
            $pool = $this->parseAmountPool(isset($account['gudingmoney']) ? $account['gudingmoney'] : '');
            if (!empty($pool) && in_array($amount, $pool)) {
                $matched[] = $account;
            }
        }

        if (empty($matched)) {
            foreach ($accounts as $account) {
                $pool = $this->parseAmountPool(isset($account['gudingmoney']) ? $account['gudingmoney'] : '');
                if (empty($pool)) {
                    $matched[] = $account;
                }
            }
        }

        foreach ($matched as $account) {
            if ($this->isMerchantFree($account['id'])) {
                return $account;
            }
        }

        // 同金额轮询：如果没有空闲但存在占用中的同金额商户，按更新时间最早的优先尝试
        if (!empty($matched)) {
            usort($matched, function ($a, $b) {
                $at = isset($a['last_paying_time']) ? intval($a['last_paying_time']) : 0;
                $bt = isset($b['last_paying_time']) ? intval($b['last_paying_time']) : 0;
                if ($at === $bt) {
                    return intval($a['id']) - intval($b['id']);
                }
                return $at - $bt;
            });
        }

        return !empty($matched) ? $matched[0] : array();
    }

    private function isMerchantFree($accountId)
    {
        $row = M('channel_account')->where(array('id' => intval($accountId)))->find();
        if (empty($row)) {
            return false;
        }
        return floatval($row['paying_money']) <= 0;
    }

    private function reserveMerchantForOrder($accountId, $money, $orderId)
    {
        return M('channel_account')->where(array(
            'id' => intval($accountId),
            'paying_money' => array('elt', 0),
        ))->save(array(
            'paying_money' => $money,
            'last_paying_time' => time(),
            'current_orderid' => $orderId,
        ));
    }

    private function markMerchantBusy($accountId, $money, $qrcode = '')
    {
        return M('channel_account')->where(array(
            'id' => intval($accountId),
            'paying_money' => array('elt', 0),
        ))->save(array(
            'paying_money' => $money,
            'last_paying_time' => time(),
            'current_orderid' => '',
            'current_qrcode_url' => $qrcode,
        ));
    }

    private function releaseMerchantByMoney($accountId, $money)
    {
        $accountId = intval($accountId);
        if (!$accountId) {
            return false;
        }

        $row = M('channel_account')->where(['id' => $accountId])->find();
        if (empty($row)) {
            return false;
        }

        $pool = $this->parseAmountPool(isset($row['gudingmoney']) ? $row['gudingmoney'] : '');
        if (!empty($pool) && !in_array((string)intval(round($money)), $pool)) {
            return true;
        }

        return M('channel_account')->where(['id' => $accountId])->save(array(
            'paying_money' => 0,
            'last_paying_time' => time(),
            'current_orderid' => '',
            'current_qrcode_url' => '',
        ));
    }

    private function fetchMerchantQrcode($account, $money, $orderId)
    {
        $cookieFile = $this->getMerchantCookieFile($account);
        if (!file_exists($cookieFile) || filesize($cookieFile) == 0) {
            $cookie = trim((string)$account['cookie']);
            if ($cookie !== '') {
                @file_put_contents($cookieFile, $cookie);
            }
        }

        $this->fetchDeviceData($cookieFile);

        // 再拉列表，取最新的二维码
        $listUrl = 'https://jiuaigou.net/bs/biz/cargo/list';
        $listPost = http_build_query(array(
            'pageSize' => 10,
            'pageNum' => 1,
            'orderByColumn' => 'number',
            'isAsc' => 'asc',
            'deviceId' => 4351,
            'useStatus' => '',
            'status' => ''
        ));
        $listHeaders = array(
            'Accept: application/json, text/javascript, */*; q=0.01',
            'Accept-Language: zh-CN,zh;q=0.9,zh-HK;q=0.8',
            'Connection: keep-alive',
            'Content-Type: application/x-www-form-urlencoded',
            'Origin: https://jiuaigou.net',
            'Referer: https://jiuaigou.net/bs/biz/cargo?deviceId=4351&time=' . (time() * 1000),
            'Sec-Fetch-Dest: empty',
            'Sec-Fetch-Mode: cors',
            'Sec-Fetch-Site: same-origin',
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36',
            'X-Requested-With: XMLHttpRequest',
            'sec-ch-ua: "Google Chrome";v="147", "Not.A/Brand";v="8", "Chromium";v="147"',
            'sec-ch-ua-mobile: ?0',
            'sec-ch-ua-platform: "Windows"',
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $listUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $listPost);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $listHeaders);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            curl_close($ch);
            return array();
        }
        curl_close($ch);

        $result = json_decode($response, true);
        if (!is_array($result) || !isset($result['code']) || intval($result['code']) !== 0 || empty($result['rows'])) {
            M('channel_account')->where(['id' => intval($account['id'])])->save(array(
                'cookie_status' => 0,
                'paying_money' => 0,
                'last_paying_time' => time(),
                'current_orderid' => '',
                'current_qrcode_url' => '',
            ));
            return array();
        }

        $qrcode = '';
        foreach ($result['rows'] as $item) {
            if (!empty($item['scanCode'])) {
                $qrcode = $item['scanCode'];
                break;
            }
        }

        if ($qrcode === '') {
            M('channel_account')->where(['id' => intval($account['id'])])->save(array(
                'cookie_status' => 0,
                'paying_money' => 0,
                'last_paying_time' => time(),
                'current_orderid' => '',
                'current_qrcode_url' => '',
            ));
            return array();
        }

        return array(
            'qrcode' => $qrcode,
            'expire' => time() + 60,
            'raw' => $result,
        );
    }

    public function ajaxQrcode()
    {
        $orderid = I('post.orderid', '', 'trim');
        if (empty($orderid)) {
            $this->ajaxReturn(array('status' => 0, 'msg' => '参数错误'));
        }

        $orderInfo = M('Order')->where(['pay_orderid' => $orderid])->find();
        if (empty($orderInfo) || empty($orderInfo['account_id'])) {
            $this->ajaxReturn(array('status' => 0, 'msg' => '订单不存在'));
        }

        $merchant = M('channel_account')->where(['id' => intval($orderInfo['account_id'])])->find();
        if (empty($merchant)) {
            $this->ajaxReturn(array('status' => 0, 'msg' => '商户不存在'));
        }

        $qrcodeRow = $this->fetchMerchantQrcode($merchant, floatval($orderInfo['pay_amount']), $orderid);
        if (empty($qrcodeRow) || empty($qrcodeRow['qrcode'])) {
            $this->releaseMerchantByMoney(intval($merchant['id']), floatval($orderInfo['pay_amount']));
            $this->ajaxReturn(array('status' => 0, 'msg' => '二维码不存在，请稍后再试'));
        }

        $this->ajaxReturn(array(
            'status' => 1,
            'data' => array(
                'qrcode' => $qrcodeRow['qrcode'],
                'expire' => $qrcodeRow['expire'],
            ),
        ));
    }

    private function getMerchantCookieFile($account)
    {
        return RUNTIME_PATH . 'jiuaigou_cookie_' . intval($account['id']) . '.txt';
    }

    private function fetchDeviceData($cookieFile)
    {
        $targetUrl = 'https://jiuaigou.net/bs/biz/cargo/batch/create/scanCode';
        $postData = array(
            'ids' => '29046,30608,30609,30610,30611'
        );
        $postString = http_build_query($postData);
        $headers = array(
            'Accept: application/json, text/javascript, */*; q=0.01',
            'Accept-Language: zh-CN,zh;q=0.9,zh-HK;q=0.8',
            'Connection: keep-alive',
            'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
            'Origin: https://jiuaigou.net',
            'Referer: https://jiuaigou.net/bs/biz/cargo?deviceId=4351&time=' . time(),
            'Sec-Fetch-Dest: empty',
            'Sec-Fetch-Mode: cors',
            'Sec-Fetch-Site: same-origin',
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36',
            'X-Requested-With: XMLHttpRequest',
            'sec-ch-ua: "Google Chrome";v="147", "Not.A/Brand";v="8", "Chromium";v="147"',
            'sec-ch-ua-mobile: ?0',
            'sec-ch-ua-platform: "Windows"'
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $targetUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postString);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $response = curl_exec($ch);
        curl_close($ch);

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
