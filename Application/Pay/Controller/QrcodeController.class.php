<?php

namespace Cli\Controller;

use Think\Controller;
use Think\Log;

class QrcodeController extends Controller
{
    public function index()
    {
        $this->refreshQrcode();
        $this->cancelTimeoutOrder();
        echo "执行完成";
    }

    // 60 秒刷新二维码
    private function refreshQrcode()
    {
        $map = [
            1 => 10,
            2 => 13,
            3 => 18,
            4 => 20,
            5 => 25,
        ];

        foreach ($map as $channelNo => $money) {
            $deviceCode = $this->getDeviceCodeByChannel($channelNo);
            if (empty($deviceCode)) {
                continue;
            }

            $result = $this->fetchDeviceData($deviceCode);
            if (!$result || !isset($result['code']) || $result['code'] != 0) {
                continue;
            }

            $qrContent = '';
            $qrUrl = '';

            // 按你接口实际返回结构取值
            if (isset($result['data']['url'])) {
                $qrUrl = $result['data']['url'];
                $qrContent = $result['data']['url'];
            } elseif (isset($result['data']['qrcode'])) {
                $qrContent = $result['data']['qrcode'];
                $qrUrl = $result['data']['qrcode'];
            } else {
                $qrContent = json_encode($result['data']);
            }

            $data = [
                'channel_no'  => $channelNo,
                'money'       => $money,
                'qrcode_url'  => $qrUrl,
                'qrcode_data' => $qrContent,
                'status'      => 1,
                'orderid'     => '',
                'create_time' => time(),
                'expire_time' => time() + 60,
                'update_time' => time(),
            ];

            $exist = M('qrcode_pool')->where([
                'channel_no' => $channelNo,
                'money'      => $money,
            ])->find();

            if ($exist) {
                M('qrcode_pool')->where(['id' => $exist['id']])->save($data);
            } else {
                M('qrcode_pool')->add($data);
            }
        }
    }

    // 120 秒未支付订单取消
    private function cancelTimeoutOrder()
    {
        $timeout = time() - 120;

        $lists = M('order')->where([
            'pay_code'   => 'ceshi',
            'pay_status' => 0,
            'create_time'=> ['lt', $timeout],
        ])->select();

        if (empty($lists)) {
            return;
        }

        foreach ($lists as $v) {
            M('order')->where(['id' => $v['id']])->save([
                'pay_status'  => 3,
                'cancel_time' => time(),
            ]);

            // 释放二维码占用
            M('qrcode_pool')->where(['orderid' => $v['pay_orderid']])->save([
                'orderid'     => '',
                'status'      => 0,
                'update_time' => time(),
            ]);
        }
    }

    private function getDeviceCodeByChannel($channelNo)
    {
        $deviceMap = [
            1 => '867272085449731',
            2 => '867272085449731',
            3 => '867272085449731',
            4 => '867272085449731',
            5 => '867272085449731',
        ];

        return isset($deviceMap[$channelNo]) ? $deviceMap[$channelNo] : '';
    }

    private function fetchDeviceData($deviceCode)
    {
        $url = "https://www.jiuaigou.net/app/client/device/scanCode/" . $deviceCode;

        $headers = [
            "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8",
            "Accept-Language: zh-CN,zh;q=0.9,zh-HK;q=0.8",
            "Connection: keep-alive",
            "Upgrade-Insecure-Requests: 1",
            "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36"
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_COOKIE, "JSESSIONID=0aac0851-ecf0-4298-b55e-76eb04c710ca");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return false;
        }

        return json_decode($response, true);
    }
}