<?php

namespace Admin\Controller;

use Think\Page;

class JiuaigouOrderController extends BaseController
{
    public function index()
    {
        $where = array();

        $orderNo = I('get.order_no', '', 'trim');
        if ($orderNo !== '') {
            $where['remote_order_no'] = array('like', '%' . $orderNo . '%');
        }
        $this->assign('order_no', $orderNo);

        $customerPhone = I('get.customer_phone', '', 'trim');
        if ($customerPhone !== '') {
            $where['customer_phone'] = array('like', '%' . $customerPhone . '%');
        }
        $this->assign('customer_phone', $customerPhone);

        $deviceSn = I('get.device_sn', '', 'trim');
        if ($deviceSn !== '') {
            $where['device_sn'] = array('like', '%' . $deviceSn . '%');
        }
        $this->assign('device_sn', $deviceSn);

        $tranNo = I('get.tran_no', '', 'trim');
        if ($tranNo !== '') {
            $where['tran_no'] = array('like', '%' . $tranNo . '%');
        }
        $this->assign('tran_no', $tranNo);

        $status = I('get.status', '', 'trim');
        if ($status !== '') {
            $where['status'] = array('eq', $status);
        }
        $this->assign('status', $status);

        $payStatus = I('get.pay_status', '', 'trim');
        if ($payStatus !== '') {
            $where['pay_status'] = array('eq', $payStatus);
        }
        $this->assign('pay_status', $payStatus);

        $statusOptions = array(
            '' => '全部订单状态',
            'success' => '成功',
            'timeoutClose' => '超时关闭',
            'close' => '已关闭',
            'fail' => '失败',
            'pending' => '待处理',
            'paying' => '处理中',
        );
        $payStatusOptions = array(
            '' => '全部支付状态',
            'success' => '已支付',
            'timeoutClose' => '超时关闭',
            'close' => '已关闭',
            'fail' => '支付失败',
            'pending' => '待支付',
            'paying' => '支付中',
        );
        $payTypeOptions = array(
            '' => '全部支付方式',
            'alipayQr' => '支付宝二维码',
            'wechatQr' => '微信二维码',
            'bankCard' => '银行卡',
            'alipay' => '支付宝',
            'wechat' => '微信',
        );
        $this->assign('statusOptions', $statusOptions);
        $this->assign('payStatusOptions', $payStatusOptions);
        $this->assign('payTypeOptions', $payTypeOptions);

        $count = M('jiaigou_order')->where($where)->count();
        $rows = I('get.rows', 20, 'intval');
        if (!$rows) {
            $rows = 20;
        }
        $page = new Page($count, $rows);

        $list = M('jiaigou_order')
            ->where($where)
            ->order('create_time desc')
            ->limit($page->firstRow . ',' . $page->listRows)
            ->select();

        foreach ($list as &$item) {
            $item['pay_status_text'] = $this->formatPayStatus($item['pay_status']);
            $item['status_text'] = $this->formatOrderStatus($item['status']);
            $item['pay_type_text'] = $this->formatPayType($item['pay_type']);
            $item['items_text'] = $this->formatItems($item['items_json']);
            $item['pay_time_text'] = $this->formatTime($item['pay_time']);
            $item['create_time_text'] = $this->formatTime($item['create_time']);
            $item['success_time_text'] = $this->formatTime($item['success_time']);
        }
        unset($item);

        $this->assign('list', $list);
        $this->assign('page', $page->show());
        $this->assign('rows', $rows);
        $this->display();
    }

    private function formatPayStatus($value)
    {
        $map = array(
            'success' => '已支付',
            'timeoutClose' => '超时关闭',
            'close' => '已关闭',
            'fail' => '支付失败',
            'pending' => '待支付',
            'paying' => '支付中',
        );
        return isset($map[$value]) ? $map[$value] : $value;
    }

    private function formatOrderStatus($value)
    {
        $map = array(
            'success' => '成功',
            'timeoutClose' => '超时关闭',
            'close' => '已关闭',
            'fail' => '失败',
            'pending' => '待处理',
            'paying' => '处理中',
        );
        return isset($map[$value]) ? $map[$value] : $value;
    }

    private function formatPayType($value)
    {
        $map = array(
            'alipayQr' => '支付宝二维码',
            'wechatQr' => '微信二维码',
            'bankCard' => '银行卡',
            'alipay' => '支付宝',
            'wechat' => '微信',
        );
        return isset($map[$value]) ? $map[$value] : $value;
    }

    private function formatItems($json)
    {
        if (empty($json)) {
            return '-';
        }
        $arr = json_decode($json, true);
        if (!is_array($arr)) {
            return $json;
        }
        $parts = array();
        foreach ($arr as $item) {
            $title = isset($item['productName']) ? $item['productName'] : (isset($item['title']) ? $item['title'] : '');
            $num = isset($item['num']) ? $item['num'] : 1;
            $device = isset($item['deviceName']) ? $item['deviceName'] : (isset($item['deviceSn']) ? $item['deviceSn'] : '');
            if ($title !== '') {
                $parts[] = $title . ($device !== '' ? '(' . $device . ')' : '') . 'x' . $num;
            }
        }
        return $parts ? implode('；', $parts) : $json;
    }

    private function formatTime($value)
    {
        if (empty($value)) {
            return '-';
        }
        if (!is_numeric($value)) {
            $value = strtotime($value);
        }
        if (!$value) {
            return '-';
        }
        return date('Y-m-d H:i:s', intval($value));
    }
}
