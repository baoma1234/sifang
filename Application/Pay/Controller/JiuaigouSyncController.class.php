<?php

namespace Pay\Controller;

class JiuaigouSyncController extends PayController
{
    protected $cookieFile;
    protected $baseUrl = 'https://jiuaigou.net';
    protected $accountId = 0;

    public function __construct()
    {
        parent::__construct();
        $this->resolveCookieFile();
    }

    /**
     * 定时同步入口
     * URL 示例：/index.php/Pay/JiuaigouSync/syncOrderList?key=abc123&aid=1
     */
    public function syncOrderList()
    {
        $key = I('get.key', '', 'trim');
        if ($key !== 'abc123') {
            exit('forbidden');
        }

        $aid = I('get.aid', 0, 'intval');
        if ($aid <= 0) {
            exit('missing aid');
        }
        $this->accountId = $aid;
        $this->resolveCookieFile();

        $pageNum  = I('get.pageNum', 1, 'intval');
        $pageSize = I('get.pageSize', 10, 'intval');
        $saved    = 0;
        $matched  = 0;
        $callback = 0;
        $failed   = 0;

        $hasPending = M('Order')->where(array(
            'account_id' => $this->accountId,
            'pay_status' => 0,
            'isdel' => 0,
        ))->count();
        if (intval($hasPending) <= 0) {
            echo 'no pending order';
            exit;
        }

        while (true) {
            $hasPending = M('Order')->where(array(
                'account_id' => $this->accountId,
                'pay_status' => 0,
                'isdel' => 0,
            ))->count();
            if (intval($hasPending) <= 0) {
                break;
            }

            $result = $this->fetchOrderList($pageNum, $pageSize);
            if (!is_array($result) || !isset($result['code']) || intval($result['code']) !== 0) {
                exit(isset($result['msg']) ? $result['msg'] : '获取订单失败');
            }

            $rows = array();
            if (isset($result['rows']) && is_array($result['rows'])) {
                $rows = $result['rows'];
            } elseif (isset($result['data']['rows']) && is_array($result['data']['rows'])) {
                $rows = $result['data']['rows'];
            } elseif (isset($result['data']) && is_array($result['data'])) {
                $rows = $result['data'];
            }

            if (empty($rows)) {
                break;
            }

            foreach ($rows as $item) {
                $syncRes = $this->saveRemoteOrder($item);
                if ($syncRes['saved']) {
                    $saved++;
                } else {
                    $failed++;
                }
                if ($syncRes['matched']) {
                    $matched++;
                }
                if ($syncRes['callback']) {
                    $callback++;
                }
                if (!empty($syncRes['log'])) {
                    $this->writeJiuaigouLog($syncRes['log']);
                }
            }

            if (count($rows) < $pageSize) {
                break;
            }

            $pageNum++;
        }

        echo 'sync ok:' . $saved . ', matched:' . $matched . ', callback:' . $callback . ', failed:' . $failed;
        exit;
    }

    /**
     * 抓取订单列表
     */
    public function fetchOrderList($pageNum = 1, $pageSize = 10, $filters = array())
    {
        $cookie = $this->getJiuaigouCookieContent($this->accountId);
        if ($cookie === '') {
            return array('code' => 401, 'msg' => '请先登录', 'data' => array());
        }

        $targetUrl = $this->baseUrl . '/bs/biz/order/list';
        $postData = array_merge(array(
            'pageSize' => intval($pageSize),
            'pageNum' => intval($pageNum),
            'orderByColumn' => 'id',
            'isAsc' => 'desc',
            'tenantId' => '',
            'status' => '',
            'orderNo' => '',
            'tranNo' => '',
            'customerId' => '',
            'huifuId' => '',
            'deviceSn' => '',
            'payType' => '',
            'shareStatus' => '',
            'payStatus' => '',
            'source' => '',
            'params[beginCreateTime]' => '',
            'params[endCreateTime]' => '',
        ), $filters);

        $ch = curl_init($targetUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $cookieFile = $this->getJiuaigouCookiePath($this->accountId);
        if (!file_exists($cookieFile)) {
            @file_put_contents($cookieFile, $cookie);
        }
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Accept: application/json, text/javascript, */*; q=0.01',
            'Accept-Language: zh-CN,zh;q=0.9,zh-HK;q=0.8',
            'Connection: keep-alive',
            'Content-Type: application/x-www-form-urlencoded',
            'Origin: ' . $this->baseUrl,
            'Referer: ' . $this->baseUrl . '/bs/biz/order',
            'Sec-Fetch-Dest: empty',
            'Sec-Fetch-Mode: cors',
            'Sec-Fetch-Site: same-origin',
            'X-Requested-With: XMLHttpRequest',
        ));
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return array('code' => 500, 'msg' => $error, 'data' => array());
        }

        $resArr = json_decode($response, true);
        if (!is_array($resArr)) {
            return array('code' => 500, 'msg' => '返回不是JSON', 'raw' => $response, 'data' => array());
        }

        return $resArr;
    }

    

    /**
     * 保存单条订单
     */
    private function saveRemoteOrder($item)
    {
        if (!is_array($item)) {
            return array('saved' => false, 'matched' => false, 'callback' => false);
        }

        $orderNo = isset($item['orderNo']) ? trim($item['orderNo']) : '';
        if ($orderNo === '') {
            return array('saved' => false, 'matched' => false, 'callback' => false);
        }

        $remoteCreateTs = $this->normalizeTime(isset($item['createTime']) ? $item['createTime'] : '');
        $money = isset($item['amount']) ? floatval($item['amount']) : 0;
        $status = isset($item['status']) ? $item['status'] : '';
        $payStatus = isset($item['payStatus']) ? $item['payStatus'] : '';
        $callbackReason = '';

        $data = array(
            'remote_order_no' => $orderNo,
            'remote_id'       => isset($item['id']) ? intval($item['id']) : 0,
            'amount'          => $money,
            'cost_amt'        => isset($item['costAmt']) ? $item['costAmt'] : 0,
            'coupon_amount'   => isset($item['couponAmount']) ? $item['couponAmount'] : 0,
            'coupon_id'       => isset($item['couponId']) ? intval($item['couponId']) : 0,
            'coupon_title'    => isset($item['couponTitle']) ? $item['couponTitle'] : '',
            'create_time'     => $remoteCreateTs,
            'customer_account'=> isset($item['customerAccount']) ? $item['customerAccount'] : '',
            'customer_id'     => isset($item['customerId']) ? intval($item['customerId']) : 0,
            'customer_phone'  => isset($item['customerPhone']) ? $item['customerPhone'] : '',
            'device_discount' => isset($item['deviceDiscount']) ? $item['deviceDiscount'] : 0,
            'device_id'       => isset($item['deviceId']) ? intval($item['deviceId']) : 0,
            'device_sn'       => isset($item['deviceSn']) ? $item['deviceSn'] : '',
            'device_type'     => isset($item['deviceType']) ? $item['deviceType'] : '',
            'enable'          => isset($item['enable']) ? $item['enable'] : '',
            'fee_amt'         => isset($item['feeAmt']) ? $item['feeAmt'] : 0,
            'huifu_id'        => isset($item['huifuId']) ? $item['huifuId'] : '',
            'items_json'      => isset($item['items']) ? json_encode($item['items'], JSON_UNESCAPED_UNICODE) : '',
            'num'             => isset($item['num']) ? intval($item['num']) : 0,
            'pay_amount'      => isset($item['payAmount']) ? $item['payAmount'] : 0,
            'pay_status'      => $payStatus,
            'pay_time'        => !empty($item['payTime']) ? $this->normalizeTime($item['payTime']) : 0,
            'pay_type'        => isset($item['payType']) ? $item['payType'] : '',
            'refund_amount'   => isset($item['refundAmount']) ? $item['refundAmount'] : 0,
            'remark'          => isset($item['remark']) ? $item['remark'] : '',
            'share_status'    => isset($item['shareStatus']) ? $item['shareStatus'] : '',
            'site_id'         => isset($item['siteId']) ? intval($item['siteId']) : 0,
            'site_name'       => isset($item['siteName']) ? $item['siteName'] : '',
            'site_position'   => isset($item['sitePosition']) ? $item['sitePosition'] : '',
            'source'          => isset($item['source']) ? $item['source'] : '',
            'status'          => $status,
            'success_time'    => !empty($item['successTime']) ? $this->normalizeTime($item['successTime']) : 0,
            'tenant_id'       => isset($item['tenantId']) ? intval($item['tenantId']) : 0,
            'tran_no'         => isset($item['tranNo']) ? $item['tranNo'] : '',
            'type'            => isset($item['type']) ? $item['type'] : '',
            'update_time'     => !empty($item['updateTime']) ? $this->normalizeTime($item['updateTime']) : time(),
            'raw_json'        => json_encode($item, JSON_UNESCAPED_UNICODE),
            'sync_time'       => time(),
        );

        $data['sync_aid'] = intval($this->accountId);

        $table = M('jiaigou_order');
        $existing = $table->where(array('remote_order_no' => $orderNo))->find();
        if ($existing) {
            $saved = $table->where(array('remote_order_no' => $orderNo))->save($data) !== false;
        } else {
            $saved = $table->add($data) ? true : false;
        }

        $matched = false;
        $callback = false;
        $log = array();
        if ($saved) {
            $matchOrder = $this->matchBackendOrder($data);
            $matched = !empty($matchOrder);
            if ($matched) {
                $log[] = '[' . date('Y-m-d H:i:s') . '] matched aid=' . $this->accountId . ' remote=' . $orderNo . ' backend=' . $matchOrder['pay_orderid'] . ' amount=' . $data['amount'] . ' remoteCreate=' . $data['create_time'] . ' backendCreate=' . $matchOrder['pay_applydate'];
                if ($this->shouldCallback($data)) {
                    $callback = $this->callbackBackendOrder($matchOrder);
                    if ($callback) {
                        $log[] = '[' . date('Y-m-d H:i:s') . '] callback success backend=' . $matchOrder['pay_orderid'];
                    } else {
                        $log[] = '[' . date('Y-m-d H:i:s') . '] callback failed backend=' . $matchOrder['pay_orderid'] . ' reason=completeOrder_return_false';
                    }
                } else {
                    $log[] = '[' . date('Y-m-d H:i:s') . '] skip callback remote=' . $orderNo . ' reason=pay_status_timeoutClose';
                }
            } else {
                $log[] = '[' . date('Y-m-d H:i:s') . '] unmatched remote=' . $orderNo . ' amount=' . $data['amount'] . ' create=' . $data['create_time'];
            }
        } else {
            $log[] = '[' . date('Y-m-d H:i:s') . '] save failed remote=' . $orderNo;
        }

        return array('saved' => $saved, 'matched' => $matched, 'callback' => $callback, 'log' => $log);
    }

    private function normalizeTime($value)
    {
        if (empty($value)) {
            return 0;
        }
        if (is_numeric($value)) {
            return intval($value);
        }
        $ts = strtotime($value);
        return $ts ? $ts : 0;
    }

    private function matchBackendOrder($remote)
    {
        $remoteMoney = isset($remote['amount']) ? floatval($remote['amount']) : 0;
        $remoteCreate = isset($remote['create_time']) ? intval($remote['create_time']) : 0;
        if ($remoteMoney <= 0 || $remoteCreate <= 0) {
            return array();
        }

        $start = $remoteCreate - 120;
        $end = $remoteCreate;
        $where = array(
            'pay_amount' => $remoteMoney,
            'pay_applydate' => array('between', array($start, $end)),
            'pay_status' => 0,
            'isdel' => 0,
        );
        if ($this->accountId) {
            $where['account_id'] = intval($this->accountId);
        }
        $order = M('Order')->where($where)->order('id asc')->find();

        return $order ? $order : array();
    }

    private function shouldCallback($remote)
    {
        return isset($remote['pay_status']) && $remote['pay_status'] !== 'timeoutClose';
    }

    private function callbackBackendOrder($order)
    {
        if (empty($order) || empty($order['pay_orderid'])) {
            return false;
        }
        $payModel = D('Pay');
        if (!$payModel) {
            return false;
        }

        try {
            if (!empty($order['pay_status']) && intval($order['pay_status']) != 0) {
                $this->writeJiuaigouLog('[' . date('Y-m-d H:i:s') . '] callback skip backend=' . $order['pay_orderid'] . ' reason=already_processed status=' . $order['pay_status']);
                return false;
            }

            $res = $payModel->completeOrder($order['pay_orderid'], '', 0);
            if ($res) {
                M('Order')->where(array('pay_orderid' => $order['pay_orderid']))->save(array(
                    'notify_msg' => '久爱购同步回调成功',
                ));
            }
            return $res ? true : false;
        } catch (\Exception $e) {
            $this->writeJiuaigouLog('[' . date('Y-m-d H:i:s') . '] callback exception backend=' . $order['pay_orderid'] . ' msg=' . $e->getMessage());
            return false;
        }
    }

    private function resolveCookieFile()
    {
        if ($this->accountId) {
            $this->cookieFile = $this->getJiuaigouCookiePath($this->accountId);
            return $this->cookieFile;
        }

        $aid = I('get.aid', 0, 'intval');
        if ($aid) {
            $this->accountId = $aid;
            $this->cookieFile = $this->getJiuaigouCookiePath($aid);
            return $this->cookieFile;
        }

        $this->cookieFile = RUNTIME_PATH . 'jiuaigou_cookie.txt';
        return $this->cookieFile;
    }

    private function getJiuaigouCookiePath($aid)
    {
        return RUNTIME_PATH . 'jiuaigou_cookie_' . intval($aid) . '.txt';
    }

    private function getJiuaigouCookieContent($aid)
    {
        $path = $this->getJiuaigouCookiePath($aid);
        if (file_exists($path) && filesize($path) > 0) {
            return trim((string)@file_get_contents($path));
        }

        $account = M('channel_account')->where(array('id' => intval($aid)))->find();
        if (!empty($account) && !empty($account['cookie'])) {
            @file_put_contents($path, trim((string)$account['cookie']));
            return trim((string)$account['cookie']);
        }

        return '';
    }

    private function writeJiuaigouLog($lines)
    {
        $logFile = RUNTIME_PATH . 'Logs/jiuaigou_sync.log';
        if (!is_array($lines)) {
            $lines = array($lines);
        }
        $content = implode(PHP_EOL, $lines) . PHP_EOL;
        file_put_contents($logFile, $content, FILE_APPEND);
    }
}
