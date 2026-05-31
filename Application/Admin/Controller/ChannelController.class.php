<?php
namespace Admin\Controller;

use Think\Page;

class ChannelController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->assign("Public", MODULE_NAME); // 模块名称
        $this->assign('paytypes', C('PAYTYPES'));

        //通道
        $channels = M('Channel')
            ->where(['status' => 1])
            ->field('id,code,title,paytype,status')
            ->select();
        $this->assign('channels', $channels);
        $this->assign('channellist', json_encode($channels));
    }

    //供应商接口列表
    public function index()
    {
        $Channelname= I('get.Channelname', '');
        $count = M('Channel')->count();
        $size  = 15;
        $rows  = I('get.rows', $size, 'intval');
        if (!$rows) {
            $rows = $size;
        }
      $where['title|code'] = ['like', "%" . $Channelname . "%"];
        $Page = new Page($count, $rows);
        $data = M('Channel')
            ->where($where)
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->order('id DESC')
            ->select();
        $this->assign('rows', $rows);
        $this->assign('list', $data);
        $this->assign('page', $Page->show());
        $this->display();
    }
    /**
     * 保存编辑供应商
     */
    public function saveEditSupplier()
    {
        if (IS_POST) {
            $id                       = I('post.id', 0, 'intval');
            $papiacc                  = I('post.pa/a');
            $_request['code']         = trim($papiacc['code']);
            $_request['title']        = trim($papiacc['title']);
            $_request['mch_id']       = trim($papiacc['mch_id']);
            $_request['signkey']      = trim($papiacc['signkey']);
            $_request['appid']        = trim($papiacc['appid']);
            $_request['appsecret']    = trim($papiacc['appsecret']);
            $_request['gateway']      = trim($papiacc['gateway']);
            $_request['notify_ip']      = trim($papiacc['notify_ip']);
            $_request['fudong_money']     =  trim($papiacc['fudong_money']);
            $_request['fudong_status']     =  trim($papiacc['fudong_status']);
            $_request['min_money']      = trim($papiacc['min_money']);
            $_request['max_money']      = trim($papiacc['max_money']);
            $_request['pagereturn']   = $papiacc['pagereturn'];
            $_request['serverreturn'] = $papiacc['serverreturn'];
            $_request['defaultrate']  = $papiacc['defaultrate'] ? $papiacc['defaultrate'] : 0;
            $_request['fengding']     = $papiacc['fengding'] ? $papiacc['fengding'] : 0;
            $_request['rate']         = $papiacc['rate'] ? $papiacc['rate'] : 0;
            $_request['t0defaultrate']  = $papiacc['t0defaultrate'] ? $papiacc['t0defaultrate'] : 0;
            $_request['t0fengding']     = $papiacc['t0fengding'] ? $papiacc['t0fengding'] : 0;
            $_request['t0rate']         = $papiacc['t0rate'] ? $papiacc['t0rate'] : 0;
            $_request['updatetime']   = time();
            $_request['unlockdomain'] = $papiacc['unlockdomain'];
            $_request['paytype']      = $papiacc['paytype'];
            $_request['status']       = $papiacc['status'];

         if ($id) {
                //更新
                $res = M('Channel')->where(array('id' => $id))->save($_request);
            } else {
                //添加
                $res = M('Channel')->add($_request);
            }
            // var_dump( M('Member')->getDbError());
            $this->ajaxReturn(['status' => $res]);
        }
    }

    //开启供应商接口
    public function editStatus()
    {
        if (IS_POST) {
            $pid    = intval(I('post.pid'));
            $isopen = I('post.isopen') ? I('post.isopen') : 0;
            $res    = M('Channel')->where(['id' => $pid])->save(['status' => $isopen]);
            $this->ajaxReturn(['status' => $res]);
        }
    }

    //新增供应商接口
    public function addSupplier()
    {
        $this->display();
    }

    //编辑供应商接口
    public function editSupplier()
    {
        $pid = intval($_GET['pid']);
        if ($pid) {
            $pa = M('Channel')->where(['id' => $pid])->find();
        }
        // dump($pa);
        $this->assign('pa', $pa);
        $this->display('addSupplier');
    }
    //删除供应商接口
    public function delSupplier()
    {
        $pid = I('post.pid', 0, 'intval');
        if ($pid) {
            // 删除子账号
            M('channel_account')->where(['channel_id' => $pid])->delete();
            $res = M('Channel')->where(['id' => $pid])->delete();
            $this->ajaxReturn(['status' => $res]);
        }
    }

    //编辑费率
    public function editRate()
    {
        if (IS_POST) {
            $pa = I('post.pa/a');
            $pid = I('post.pid', 0, 'intval');
            if ($pid) {
                $res       = M('Channel')->where(['id' => $pid])->save($pa);
                $pa['pid'] = $pid;
                $this->ajaxReturn(['status' => $res, 'data' => $pa]);
            }
        } else {
            $pid = intval(I('get.pid'));
            if ($pid) {
                $data = M('Channel')->where(['id' => $pid])->find();
            }

            $this->assign('pid', $pid);
            $this->assign('pa', $data);
            $this->display();
        }
    }

    //产品列表
    public function product()
    {
        $data = M('Product')->select();
        $this->assign('list', $data);
        $this->display();
    }

    //切换产品状态
    public function prodStatus()
    {
        if (IS_POST) {
            $id    = I('post.id', 0, 'intval');
            $colum = I('post.k');
            $value = I('post.v');
            $res   = M('Product')->where(['id' => $id])->save([$colum => $value]);
            $this->ajaxReturn(['status' => $res]);
        }
    }

    //切换用户显示状态
    public function prodDisplay()
    {
        if (IS_POST) {
            $id    = I('post.id', 0, 'intval');
            $colum = I('post.k');
            $value = I('post.v');
            $res   = M('Product')->where(['id' => $id])->save([$colum => $value]);
            $this->ajaxReturn(['status' => $res]);
        }
    }
    //添加产品
    public function addProduct()
    {
        $this->display();
    }

    //编辑产品
    public function editProduct()
    {
        $id   = I('get.pid', 0, 'intval');
        $data = M('Product')->where(['id' => $id])->find();

        //权重
        $weights    = [];
        $weights    = explode('|', $data['weight']);
        $_tmpWeight = '';
        if (is_array($weights)) {
            foreach ($weights as $value) {
                list($pid, $weight) = explode(':', $value);
                if ($pid) {
                    $_tmpWeight[$pid] = ['pid' => $pid, 'weight' => $weight];
                }
            }
        } else {
            list($pid, $weight) = explode(':', $data['weight']);
            if ($pid) {
                $_tmpWeight[$pid] = ['pid' => $pid, 'weight' => $weight];
            }
        }
        $data['weight'] = $_tmpWeight;
        //通道
        //$channels = M('Channel')->where(["paytype" => $data['paytype'], "status" => 1])->select();
        $channels = M('Channel')->where(["status" => 1])->select();
        // var_dump($channels);
        $this->assign('channels', $channels);
        $this->assign('pd', $data);
        $this->display('addProduct');
    }

    //保存更改
    public function saveProduct()
    {
        if (IS_POST) {
            $id     = intval(I('post.id'));
            $rows   = I('post.pd/a');
            $weight = I('post.w/a');
            //权重
            $weightStr = '';
            if (is_array($weight)) {
                foreach ($weight as $weigths) {
                    if ($weigths['pid']) {
                        $weightStr .= $weigths['pid'] . ':' . $weigths['weight'] . "|";
                    }
                }
            }
            $rows['weight'] = trim($weightStr, '|');
            $rows['id']=$rows['ids'];
            unset($rows['ids']);
            //保存
            if ($id) {
                $res = M('Product')->where(['id' => $id])->save($rows);
            } else {
                $res = M('Product')->add($rows);
            }
            $this->addAdminLog("操作了渠道 ID: $id");
            $this->ajaxReturn(['status' => $res]);
        }
    }

    //删除产品
    public function delProduct()
    {
        if (IS_POST) {
            $id  = I('post.pid', 0, 'intval');
            $res = M('Product')->where(['id' => $id])->delete();
            $this->ajaxReturn(['status' => $res]);
        }
    }

    //接口模式
    public function selProduct()
    {
        if (IS_POST) {
            $paytyep = I('post.paytype', 0, 'intval');
            //通道
            $data = M('Channel')->where(["paytype" => $paytyep, "status" => 1])->select();
            $this->ajaxReturn(['status' => 0, 'data' => $data]);
        }
    }

    /**
     * 通道账户列表
     */
    public function account()
    {
        $channel_id = I('get.pid', 0, 'intval');
        $channel    = M('Channel')->where(['id' => $channel_id])->find();
        $accounts   = M('channel_account')->where(['channel_id' => $channel_id])->select();

        foreach ($accounts as &$account) {
            $cookieInfo = $this->getJiuaigouCookieStatus($account['id']);
            $account['cookie_status_code'] = $cookieInfo['code'];
            $account['cookie_status_text'] = $cookieInfo['text'];
            $account['cookie_file'] = $cookieInfo['file'];
            $account['cookie_updated_text'] = $this->formatTime($cookieInfo['updated']);
            $account['gudingmoney_text'] = !empty($account['gudingmoney']) ? $account['gudingmoney'] : '-';
        }
        unset($account);

        $this->assign('channel', $channel);
        $this->assign('accounts', $accounts);
        $this->display();
    }

    /**
     * 编辑账户
     */
    public function editAccountControl()
    {
        if (IS_POST) {
            $data = I('post.data', '');

            if ($data['start_time'] != 0 || $data['end_time'] != 0) {
                if ($data['start_time'] >= $data['end_time']) {
                    $this->ajaxReturn(['status' => 0, 'msg' => '交易结束时间不能小于开始时间！']);
                }
            }
            if ($data['max_money'] != 0 && $data['min_money'] != 0) {
                if ($data['min_money'] >= $data['max_money']) {
                    $this->ajaxReturn(['status' => 0, 'msg' => '最大交易金额不能小于或等于最小金额！']);
                }
            }
            if ($data['is_defined'] == 0) {
                $channel_id = M('ChannelAccount')->where(['id' => $data['id']])->getField('channel_id');
                $channelInfo = M('Channel')->where(['id' => $channel_id])->find();
                $data['offline_status'] = $channelInfo['offline_status'];
                $data['control_status'] = $channelInfo['control_status'];
            }
            $res = M('ChannelAccount')->where(['id' => $data['id']])->save($data);
            $this->ajaxReturn(['status' => $res]);
        } else {
            $aid  = I('get.aid', '', 'intval');
            $info = M('ChannelAccount')->where(['id' => $aid])->find();

            $this->assign('info', $info);
            $this->assign('aid', $aid);
            $this->display();
        }

    }

    /**
     * 编辑账户
     */
    public function editAccount()
    {
        $aid = intval($_GET['aid']);
        if ($aid) {
            $pa = M('channel_account')->where(['id' => $aid])->find();
        }
        $this->assign('pa', $pa);
        $this->assign('pid', $pa['channel_id']);
        $this->display('addAccount');
    }

    /**
     * 新增账户
     */
    public function addAccount()
    {
        $pid = intval($_GET['pid']);
        $this->assign('pid', $pid);
        $this->assign('channels', M('Channel')->where(['status' => 1])->select());
        $this->display('addAccount');
    }

    /**
     * 批量导入账户
     */
    public function batchAddAccount()
    {
        $pid = intval($_GET['pid']);
        $this->assign('pid', $pid);
        $this->assign('channels', M('Channel')->where(['status' => 1])->select());
        $this->display('batchAddAccount');
    }

    public function showEven()
    {
        // echo "<pre>";
        $channelList = M('Channel')->where(['control_status' => 1, 'status' => 1])->select();
        $accountList = M('ChannelAccount')->where(['control_status' => 1, 'status' => 1])->select();

        $list = [];
        foreach ($channelList as $k => $v) {
            $v['offline_status'] = $v['offline_status'] ? '上线' : '下线';
            $list[$k]            = $v;
            foreach ($accountList as $k1 => $v1) {
                if ($v1['channel_id'] == $v['id']) {
                    $v1['offline_status']  = $v1['offline_status'] ? '上线' : '下线';
                    $list[$k]['account'][] = $v1;
                }
            }
        }
        $this->assign('list', $list);
        $this->display();
    }

    /**
     * 保存账户
     */
    public function saveEditAccount()
    {
        if (IS_POST) {
            $id                     = I('post.id', 0, 'intval');
            $papiacc                = I('post.pa/a');
            $_request['title']      = trim($papiacc['title']);
            $_request['channel_id'] = trim($papiacc['pid']);
            $_request['mch_id']     = trim($papiacc['mch_id']);
            $_request['signkey']    = trim($papiacc['signkey']);
            $_request['appid']      = trim($papiacc['appid']);
            $_request['appsecret']  = trim($papiacc['appsecret']);
            // 默认为1
            $weight                     = trim($papiacc['weight']);
            $_request['weight']         = $weight === '' ? 1 : $weight;
            $_request['custom_rate']    = $papiacc['custom_rate'];
            $_request['defaultrate']    = $papiacc['defaultrate'] ? $papiacc['defaultrate'] : 0;
            $_request['fengding']       = $papiacc['fengding'] ? $papiacc['fengding'] : 0;
            $_request['rate']           = $papiacc['rate'] ? $papiacc['rate'] : 0;
            $_request['t0defaultrate']    = $papiacc['t0defaultrate'] ? $papiacc['t0defaultrate'] : 0;
            $_request['t0fengding']       = $papiacc['t0fengding'] ? $papiacc['t0fengding'] :0;
            $_request['t0rate']           = $papiacc['t0rate'] ? $papiacc['t0rate'] : 0;
            $_request['updatetime']     = time();
            $_request['status']         = $papiacc['status'];
            $_request['is_defined']     = $papiacc['is_defined'];
            $_request['all_money']      = $papiacc['all_money'] == '' ? 0:$papiacc['all_money'];
            $_request['min_money']      = $papiacc['min_money'] == '' ? 0:$papiacc['min_money'];
            $_request['max_money']      = $papiacc['max_money'] == '' ? 0:$papiacc['max_money'];
            $_request['start_time']     = $papiacc['start_time'];
            $_request['gudingmoney']     = $papiacc['gudingmoney'];
            $_request['iphei']     = $papiacc['iphei'];
            $_request['end_time']       = $papiacc['end_time'];
            $_request['offline_status'] = $papiacc['offline_status'];
            $_request['control_status'] = $papiacc['control_status'];
            $_request['unlockdomain'] = $papiacc['unlockdomain'];
            $_request['gudingmoney'] = isset($papiacc['gudingmoney']) ? trim($papiacc['gudingmoney']) : '';
            if ($_request['gudingmoney'] === '') {
                $this->ajaxReturn(['status' => 0, 'msg' => '固定金额池不能为空']);
            }
            if ($id) {
                //更新
                $res = M('channel_account')->where(array('id' => $id))->save($_request);
            } else {
                //添加
                $res = M('channel_account')->add($_request);
            }
            $this->ajaxReturn(['status' => $res]);
        }
    }

    public function saveBatchAccount()
    {
        if (IS_POST) {
            $pid = I('post.pid', 0, 'intval');
            $channelId = I('post.channel_id', 0, 'intval');
            $raw = trim(I('post.accounts', '', 'trim'));
            if (!$channelId || $raw === '') {
                $this->ajaxReturn(['status' => 0, 'msg' => '参数错误']);
            }

            $lines = preg_split('/\r\n|\r|\n/', $raw);
            $inserted = 0;
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '') {
                    continue;
                }
                $cols = preg_split('/\s*[\|,]\s*/', $line);
                $title = isset($cols[0]) ? trim($cols[0]) : '';
                if ($title === '') {
                    continue;
                }

                $data = array(
                    'channel_id' => $channelId,
                    'title' => $title,
                    'mch_id' => isset($cols[1]) ? trim($cols[1]) : '',
                    'signkey' => isset($cols[2]) ? trim($cols[2]) : '',
                    'weight' => 1,
                    'status' => 1,
                    'updatetime' => time(),
                );
                if (M('channel_account')->add($data)) {
                    $inserted++;
                }
            }

            $this->ajaxReturn(['status' => 1, 'msg' => '导入成功', 'count' => $inserted]);
        }
    }

    //开启供应商接口
    public function editAccountStatus()
    {
        if (IS_POST) {
            $aid    = intval(I('post.aid'));
            $isopen = I('post.isopen') ? I('post.isopen') : 0;
            $res    = M('channel_account')->where(['id' => $aid])->save(['status' => $isopen]);
            $this->ajaxReturn(['status' => $res]);
        }
    }

    //删除供应商接口
    public function delAccount()
    {
        $aid = I('post.aid', 0, 'intval');
        if ($aid) {
            $res = M('channel_account')->where(['id' => $aid])->delete();
            $this->ajaxReturn(['status' => $res]);
        }
    }

    //编辑费率
    public function editAccountRate()
    {
        if (IS_POST) {
            $pa = I('post.pa');
            $accountId = I('post.aid');
            if ($accountId) {
                $res       = M('channel_account')->where(['id' => $accountId])->save($pa);
                $pa['aid'] = $accountId;
                $this->ajaxReturn(['status' => $res, 'data' => $pa]);
            }
        } else {
            $aid = intval(I('get.aid'));
            if ($aid) {
                $data = M('channel_account')->where(['id' => $aid])->find();
            }

            $this->assign('aid', $aid);
            $this->assign('pa', $data);
            $this->display();
        }
    }

    public function loginCookie()
    {
        if (!IS_POST) {
            $aid = intval(I('get.aid', 0, 'intval'));
            $account = M('channel_account')->where(['id' => $aid])->find();
            $this->assign('account', $account);
            $this->assign('aid', $aid);
            $this->display('loginCookie');
            return;
        }

        $aid = I('post.aid', 0, 'intval');
        $cookie = trim(I('post.cookie', ''));
        if (!$aid) {
            $this->ajaxReturn(['status' => 0, 'msg' => '参数错误']);
        }
        if ($cookie === '') {
            $this->ajaxReturn(['status' => 0, 'msg' => '请输入cookie']);
        }

        $account = M('channel_account')->where(['id' => $aid])->find();
        if (!$account) {
            $this->ajaxReturn(['status' => 0, 'msg' => '账户不存在']);
        }

        $cookieFile = $this->getJiuaigouCookiePath($aid);
        $dir = dirname($cookieFile);
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
        $saved = file_put_contents($cookieFile, $cookie);
        if ($saved === false) {
            $this->ajaxReturn(['status' => 0, 'msg' => '保存cookie失败']);
        }

        M('channel_account')->where(['id' => $aid])->save(array(
            'cookie' => $cookie,
            'cookie_status' => 1,
            'cookie_update_time' => time(),
        ));

        $this->ajaxReturn(['status' => 1, 'msg' => 'cookie保存成功']);
    }

    public function loginCookieStatus()
    {
        $aid = I('get.aid', 0, 'intval');
        $this->ajaxReturn(array(
            'status' => 1,
            'data' => array(
                'status' => $this->getJiuaigouCookieStatus($aid),
                'cookie_file' => $this->getJiuaigouCookiePath($aid),
            ),
        ));
    }

    private function getJiuaigouCookiePath($aid)
    {
        return RUNTIME_PATH . 'jiuaigou_cookie_' . intval($aid) . '.txt';
    }

    private function getJiuaigouCookieStatus($aid)
    {
        $aid = intval($aid);
        if (!$aid) {
            return array('code' => 0, 'text' => '未登录', 'updated' => 0, 'file' => '');
        }

        $account = M('channel_account')->where(['id' => $aid])->field('id,title,cookie,cookie_status,cookie_update_time')->find();
        $file = $this->getJiuaigouCookiePath($aid);
        $fileExists = file_exists($file) && filesize($file) > 0;
        $dbCookie = !empty($account['cookie']) ? trim($account['cookie']) : '';
        $status = 0;
        if (!empty($account) && (intval($account['cookie_status']) === 1 || $dbCookie !== '') && ($fileExists || $dbCookie !== '')) {
            $status = 1;
        }

        return array(
            'code' => $status,
            'text' => $status ? '已登录' : '未登录',
            'updated' => !empty($account['cookie_update_time']) ? intval($account['cookie_update_time']) : 0,
            'cookie' => $dbCookie,
            'file' => $file,
            'account' => $account,
        );
    }

    private function persistJiuaigouCookie($aid, $cookie)
    {
        $aid = intval($aid);
        $cookie = trim((string)$cookie);
        if (!$aid || $cookie === '') {
            return false;
        }

        $data = array(
            'cookie' => $cookie,
            'cookie_status' => 1,
            'cookie_update_time' => time(),
        );

        $saved = M('channel_account')->where(['id' => $aid])->save($data);
        @file_put_contents($this->getJiuaigouCookiePath($aid), $cookie);
        return $saved !== false;
    }

    private function getJiuaigouCookieContent($aid)
    {
        $status = $this->getJiuaigouCookieStatus($aid);
        if (!empty($status['cookie'])) {
            return $status['cookie'];
        }

        $file = $this->getJiuaigouCookiePath($aid);
        if (file_exists($file)) {
            return trim(@file_get_contents($file));
        }

        return '';
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

    public function saveCookie()
    {
        if (IS_POST) {
            $aid = I('post.aid', 0, 'intval');
            $cookie = I('post.cookie', '', 'trim');
            if (!$aid || $cookie === '') {
                $this->ajaxReturn(['status' => 0, 'msg' => '参数错误']);
            }

            $res = $this->persistJiuaigouCookie($aid, $cookie);
            $this->ajaxReturn(['status' => $res ? 1 : 0, 'msg' => $res ? 'Cookie已保存' : '保存失败']);
        }
    }

    //编辑风控
    public function editControl()
    {
        if (IS_POST) {
            $data = I('post.data', '');
            if ($data['start_time'] != 0 || $data['end_time'] != 0) {
                if ($data['start_time'] >= $data['end_time']) {
                    $this->ajaxReturn(['status' => 0, 'msg' => '交易结束时间不能小于开始时间！']);
                }
            }
            if ($data['max_money'] != 0 && $data['min_money'] != 0) {
                if ($data['min_money'] >= $data['max_money']) {
                    $this->ajaxReturn(['status' => 0, 'msg' => '最大交易金额不能小于或等于最小金额！']);
                }
            }
       
            $res = M('Channel')->where(['id' => $data['id']])->save($data);
            $this->ajaxReturn(['status' => $res]);
        } else {
            $pid  = I('get.pid', '');
            $info = M('Channel')->where(['id' => $pid])->find();
            $this->assign('info', $info);
            $this->assign('pid', $pid);
            $this->display();
        }
    }
}
