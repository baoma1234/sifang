<?php

namespace Pay\Controller;


class BotController extends PayController
{

    public function __construct()
    {
        parent::__construct();
    }

    // 机器人 Token
    private $token = 'bot7958315334:AAFc1prlKsCU4oArwn5tjAysR49-Kl0Jlbc';

    /**
     * Webhook 入口
     */
    public function webhook()
    {
        $this->setBotCommands();
        // 1. 获取 Telegram 推送的原始 JSON 数据
        $json = file_get_contents("php://input");
        file_put_contents(dirname(__FILE__) . '/bot/~message-log-' . date('Y-m-d', $_SERVER['REQUEST_TIME']) . '.log', '成功信息-源串：' . $json . "\n", FILE_APPEND);
        $update = json_decode($json, true);

        // 如果没有数据则退出，防止直接访问报错
        if (!$update) {
            exit;
        }

        // 2. 提取关键信息
        $message = $update['message']['text'];    // 用户发送的文本
        $chatId = $update['message']['chat']['id']; // 会话ID
        $userId = $update['message']['from']['id']; // 用户ID
        $keyword = "商户绑定";


        // 正则匹配（区分大小写，/i 修饰符表示不区分）
        if (preg_match("/{$keyword}/", $message)) {
            $bd = true;
        } else {
            $bd = false;
        }

        $keyword2 = "支付绑定";


        // 正则匹配（区分大小写，/i 修饰符表示不区分）
        if (preg_match("/{$keyword2}/", $message)) {
            $zf = true;
        } else {
            $zf = false;
        }

        // 3. 根据指令逻辑处理
        if ($message == '/start@my_new_jiuyuan_bot' || $message == '商户余额' || $message == '查询余额') {

            $list = M('Member')
                ->where(array('telid' => $chatId))
                ->find();
            if (!empty($list)) {
                $reply = $this->yue($list);
            } else {
                $reply = '未绑定商户';
            }
        } elseif ($message == '/help') {
            $reply = "请输入特定指令获取服务。";
        } elseif ($message == '已回调') {
            $this->huitiaos($update);
        } elseif ($bd == true) {
            // 用空字符串替换关键词，实现删除效果
            $id = str_replace($keyword, "", $message);
            $id = trim($id);
            $list = M('Member')
                ->field('id,balance')
                ->where(array('username' => $id))
                ->find();
            if (!empty($list)) {
                if (!empty($list['telid'])) {
                    $reply = '商户已被绑定';
                } else {
                    $res = M('Member')
                        ->where(array('id' => $list['id']))
                        ->save(array('telid' => $chatId));

                    if ($res == 1 || $res == 0) {
                        $reply = '绑定成功';
                        // $reply=$list['id'];
                    } else {
                        $reply = '绑定失败,联系商务';
                    }
                }

            } else {
                $reply = '商户不存在';
            }

        } elseif ($zf == true) {
            $id = str_replace($keyword2, "", $message);
            $id = trim($id);
            $list = M('channel')
                ->field('id')
                ->where(array('title' => $id))
                ->find();
            if (!empty($list)) {
                if (!empty($list['telid'])) {
                    $reply = '商户已被绑定';
                } else {
                    $res = M('channel')
                        ->where(array('id' => $list['id']))
                        ->save(array('telid' => $chatId));

                    if ($res == 1 || $res == 0) {
                        $reply = '通道群组绑定成功';
                        // $reply=$list['id'];
                    } else {
                        $reply = '通道群组绑定失败,联系商务';
                    }
                }

            } else {
                $reply = '通道群组不存在';
            }
        } else {

            $list = M('Member')
                ->where(array('telid' => $chatId))
                ->find();
            if (!empty($list)) {
                $this->zhuanfaorder($list, $update);
            }
            if (!empty($update['message']['reply_to_message'])) {
                $this->shuifu($update);
            }


        }

        // 4. 发送回复
        $this->sendMessage($chatId, $reply);

        // 5. 必须返回 HTTP 200 状态码告知 Telegram 已收到
        echo "ok";
    }

    //转发
    public function zhuanfaorder($list, $update)
    {
        $message = $update['message']['text'];    // 用户发送的文本
        $chatId = $update['message']['chat']['id']; // 会话ID
        $m_Order = M("Order");
        $pay_memberid = $list['id'] + 10000;
        $order_info = $m_Order->field('id,pay_tongdao,out_trade_id,pay_orderid')->where(['out_trade_id' => $message])->find(); //获取订单信息
        $order_info2 = $m_Order->field('id,pay_tongdao,out_trade_id,pay_orderid')->where(['out_trade_id' => $update['message']['caption']])->find(); //获取订单信息
        //   var_dump($chanel);
        if ($order_info || $order_info2) {
            $final_order = $order_info ?: $order_info2;
            $chanel = M("channel")->field('id,telid')->where(['code' => $final_order['pay_tongdao']])->find();
            //  var_dump($chanel);
            if (!empty($chanel['telid'])) {

                if (!empty($update['message']['caption'])) {
                    // $forwardData = [
                    // 'chat_id' => $chanel['telid'],
                    // 'from_chat_id' => $chatId,
                    // 'message_id' => $update['message']['message_id']
                    // ];

                    $data = [
                        'chat_id' => $chanel['telid'],
                        'photo' => $update['message']['photo'][0]['file_id'],
                        'caption' => $final_order['pay_orderid'],
                    ];
                    $forwardRes = $this->sendRequest('sendPhoto', $data);
                } else {

                    $data = [
                        'chat_id' => $chanel['telid'],
                        'text' => $final_order['pay_orderid'],
                        // 'reply_to_message_id' => $update['message']['message_id'] // 关键：关联到刚转发的消息
                    ];
                    $forwardRes = $this->sendRequest('sendMessage', $data);
                }

                $realdata['message_id'] = $update['message']['message_id'];
                $m_Order->where(['id' => $final_order['id']])->save($realdata);
                //  var_dump($data);
                // var_dump($forwardRes);
                file_put_contents(dirname(__FILE__) . '/bot/~fasong-log-' . date('Y-m-d', $_SERVER['REQUEST_TIME']) . '.log', '转发-源串：' . json_encode($forwardRes) . "\n", FILE_APPEND);


                $replyPhotoData = [
                    'chat_id' => $chatId,
                    'text' => "客官稍等，快马加鞭查询中~~~",
                    'reply_to_message_id' => $update['message']['message_id'] // 关键：关联到刚转发的消息
                ];

                $replyRes = $this->sendRequest('sendMessage', $replyPhotoData);
                file_put_contents(dirname(__FILE__) . '/bot/~fasong-log-' . date('Y-m-d', $_SERVER['REQUEST_TIME']) . '.log', '转发回复-源串：' . json_encode($replyRes) . "\n", FILE_APPEND);
                // if ($replyRes['ok']) {
                //     $reply = "转发并回复图片成功！";
                // }

            } else {
                $reply = '查单失败';
            }


        }
    }

    /*通道回复信息
    */
    public function shuifu($update)
    {
        $message = $update['message']['reply_to_message']['caption'];    // 用户发送的文本
        if (empty($message)) {
            $message = $update['message']['reply_to_message']['text'];    // 用户发送的文本
        }
        $m_Order = M("Order");
        $order_info = $m_Order->field('id,pay_memberid,out_trade_id,pay_orderid,message_id')->where(['pay_orderid' => $message])->find(); //获取订单信息
        if (!empty($order_info)) {
            $list = M('Member')
                ->field('telid')
                ->where(array('id' => ($order_info['pay_memberid'] - 10000)))
                ->find();

            $targets = array("已回调"); // 目标名单
            $pattern = '/\b(' . implode('|', $targets) . ')\b/';
            //  var_dump($targets);
            if (preg_match($pattern, $message, $matches)) {
                $text = $order_info['out_trade_id'] . "   回调成功";
                $forwardData = [
                    'chat_id' => $list['telid'],
                    'text' => $text,
                    'reply_to_message_id' => $order_info['message_id'] // 关键：关联到刚转发的消息
                ];
                $forwardRes = $this->sendRequest('sendMessage', $forwardData);

            } else {
                //  var_dump($update);
                if (!empty($update['message']['text'])) {
                    $text = $this->replaceorder($update['message']['text'], $order_info['pay_orderid'], $order_info['out_trade_id']);
                    $forwardData = [
                        'chat_id' => $list['telid'],
                        'text' => $text,
                        'reply_to_message_id' => $order_info['message_id'] // 关键：关联到刚转发的消息
                    ];
                    $forwardRes = $this->sendRequest('sendMessage', $forwardData);
                } else {
                    $text = $this->replaceorder($update['message']['caption'], $order_info['pay_orderid'], $order_info['out_trade_id']);
                    $forwardData = [
                        'chat_id' => $list['telid'],
                        'photo' => $update['message']['photo'][0]['file_id'],
                        'caption' => $text,
                        'reply_to_message_id' => $order_info['message_id'] // 关键：关联到刚转发的消息
                    ];
                    // var_dump($forwardData);
                    $forwardRes = $this->sendRequest('sendPhoto', $forwardData);
                }

            }


            // $this->sendMessage($list['telid'], $order_info['out_trade_id'] . "   回调成功");
        }
    }

    public function replaceorder($str, $search, $replace)
    {
        // 直接使用字符串替换，性能最高
        $result = str_replace($search, $replace, $str);

        return $result;
    }

    function puckc()
    {
        $json = '{"update_id":10134176,
"message":{"message_id":396,"from":{"id":1122956641,"is_bot":false,"first_name":"DaDA","username":"saodeyipi","language_code":"zh-hans"},"chat":{"id":-5036865539,"title":"c\u901a\u9053\u5546\u6237","type":"group","all_members_are_administrators":false,"accepted_gift_types":{"unlimited_gifts":false,"limited_gifts":false,"unique_gifts":false,"premium_subscription":false,"gifts_from_channels":false}},"date":1773598537,"photo":[{"file_id":"AgACAgUAAxkBAAIBi2m29yySqueWR9vKwzymwpKqxJBKAAL0EGsb8O-4VRkehEJ_3fYEAQADAgADcwADOgQ","file_unique_id":"AQAD9BBrG_DvuFV4","file_size":485,"width":59,"height":90},{"file_id":"AgACAgUAAxkBAAIBi2m29yySqueWR9vKwzymwpKqxJBKAAL0EGsb8O-4VRkehEJ_3fYEAQADAgADbQADOgQ","file_unique_id":"AQAD9BBrG_DvuFVy","file_size":2224,"width":148,"height":227}],"caption":"20260306200041571005 215215215215"}}';
        $json = json_decode($json, 1);
        $list = M('Member')
            ->where(array('telid' => '-5194894458'))
            ->find();
        var_dump($this->shuifu($json));
    }

    public function huitiaos($update)
    {
        $message = $update['message']['reply_to_message']['caption'];    // 用户发送的文本
        if (empty($message)) {
            $message = $update['message']['reply_to_message']['text'];    // 用户发送的文本
        }
        $m_Order = M("Order");
        $order_info = $m_Order->field('id,pay_memberid,out_trade_id')->where(['pay_orderid' => $message])->find(); //获取订单信息
        if (!empty($order_info)) {
            $list = M('Member')
                ->field('telid')
                ->where(array('id' => ($order_info['pay_memberid'] - 10000)))
                ->find();
            $this->sendMessage($list['telid'], $order_info['out_trade_id'] . "   回调成功");
        }


    }

    public function test($message)
    {
        return '收到' . $message;
    }

    //每日余额
    public function yue($list)
    {
        $todayBegin = date('Y-m-d') . ' 00:00:00';
        $todyEnd = date('Y-m-d') . ' 23:59:59';
        $yesterday = date('Y-m-d', strtotime('-1 day'));

        // 拼接时间范围
        $yesterdayBegin = $yesterday . ' 00:00:00';
        $yesterdayEnd = $yesterday . ' 23:59:59';
        $yj = M('moneychange')->where(['userid' => $list['id'], 'datetime' => ['between', [$todayBegin, $todyEnd]], 'lx' => 9])->sum('money');
        $stat = array();
        $stat['todayorderactualsum'] = M('Order')->where(['pay_memberid' => 10000 + $list['id'], 'pay_successdate' => ['between', [strtotime($todayBegin), strtotime($todyEnd)]], 'pay_status' => ['in', '1,2']])->sum('pay_actualamount');
        $stat['today_income'] = $stat['todayorderactualsum'] + $yj;


        $stat['todaysxf'] = M('Order')->where(['pay_memberid' => 10000 + $list['id'], 'pay_successdate' => ['between', [strtotime($todayBegin), strtotime($todyEnd)]], 'pay_status' => ['in', '1,2']])->sum('pay_poundage');

        $yjy = M('moneychange')->where(['userid' => $list['id'], 'datetime' => ['between', [$yesterdayBegin, $yesterdayEnd]], 'lx' => 9])->sum('money');
        $staty = array();
        $staty['todayorderactualsum'] = M('Order')->where(['pay_memberid' => 10000 + $list['id'], 'pay_successdate' => ['between', [strtotime($yesterdayBegin), strtotime($yesterdayEnd)]], 'pay_status' => ['in', '1,2']])->sum('pay_actualamount');
        $staty['today_income'] = $staty['todayorderactualsum'] + $yjy;
        $staty['todaysxf'] = M('Order')->where(['pay_memberid' => 10000 + $list['id'], 'pay_successdate' => ['between', [strtotime($yesterdayBegin), strtotime($yesterdayEnd)]], 'pay_status' => ['in', '1,2']])->sum('pay_poundage');
        $reply = "久远 今日" . date('Y-m-d') . "截止当前账单\n";
        $reply .= "---------------------------------\n";
        $reply .= "商户：" . $list['username'] . "\n";
        $reply .= "商户号：" . ($list['id'] + 10000) . "\n";
        $reply .= "今日总跑量：" . $stat['today_income'] . "\n";
        $reply .= "今日手续费：" . $stat['todaysxf'] . "\n";
        // $reply.="应下发余额：".$stat['today_income']."\n";
        $reply .= "商户可下发余额：" . $list['balance'] . "\n";
        $reply .= "---------------------------------\n";
        $reply .= "昨日总跑量：" . $staty['today_income'] . "\n";
        $reply .= "昨日手续费：" . $staty['todaysxf'] . "\n";
        return $reply;
    }


    public function setBotCommands()
    {
        $token = $this->token;
        $url = "https://api.telegram.org/{$token}/setMyCommands";

        $commands = [
            ['command' => 'start', 'description' => '商户余额'],
            ['command' => 'bd', 'description' => '商户绑定'],
            ['command' => 'help', 'description' => '汇率'],


        ];

        $data = [
            'commands' => json_encode($commands)
        ];

        // 使用 TP5 自带的 curl 或 file_get_contents 发送请求
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);
        var_dump($response);
    }

    /**
     * 调用 Telegram API 发送消息
     */
    private function sendMessage($chatId, $text)
    {
        $url = "https://api.telegram.org/" . $this->token . "/sendMessage";

        $params = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML' // 支持 HTML 格式
        ];

        // 使用 PHP 的 curl 发送请求
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 跳过 SSL 验证（视服务器环境而定）
        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }


    private function sendRequest($cao, $params)
    {
        $url = "https://api.telegram.org/" . $this->token . "/" . $cao;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 跳过 SSL 验证（视服务器环境而定）
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }


}

?>