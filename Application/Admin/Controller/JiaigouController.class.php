<?php

namespace Admin\Controller;

use Think\Page;

class JiaigouController extends BaseController
{
    protected $baseUrl = 'https://jiuaigou.net';

    public function index()
    {
        $channelId = I('get.channel_id', 0, 'intval');
        $keyword = I('get.keyword', '', 'trim');
        $rows = I('get.rows', 15, 'intval');
        if (!$rows) {
            $rows = 15;
        }

        $where = array();
        if ($channelId) {
            $where['channel_id'] = $channelId;
        }
        if ($keyword !== '') {
            $where['title|mch_id'] = array('like', '%' . $keyword . '%');
        }

        $channels = M('Channel')->where(['status' => 1])->select();
        $count = M('channel_account')->where($where)->count();
        $Page = new Page($count, $rows);
        $accounts = M('channel_account')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        foreach ($accounts as &$account) {
            $account['channel_title'] = M('Channel')->where(['id' => $account['channel_id']])->getField('title');
            $account['cookie_updated_text'] = $this->formatTime($account['cookie_update_time']);
        }
        unset($account);

        $this->assign('channel_id', $channelId);
        $this->assign('keyword', $keyword);
        $this->assign('rows', $rows);
        $this->assign('channels', $channels);
        $this->assign('accounts', $accounts);
        $this->assign('page', $Page->show());
        $this->display('index');
    }

    public function getCaptcha()
    {
        $aid = I('get.aid', 0, 'intval');
        $cookieFile = $this->getCookieFile($aid);
        if (!file_exists($cookieFile) || filesize($cookieFile) == 0) {
            $this->initSession($cookieFile);
        }

        $captchaUrl = $this->baseUrl . '/bs/captcha/captchaImage?type=math';
        $ch = curl_init($captchaUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Accept: image/avif,image/webp,image/apng,image/*,*/*;q=0.8',
            'Accept-Language: zh-CN,zh;q=0.9',
            'Connection: keep-alive',
            'Referer: ' . $this->baseUrl . '/bs/login',
        ));
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $imgData = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode != 200) {
            @unlink($cookieFile);
            $this->error('验证码获取失败');
        }

        header('Content-Type: image/png');
        echo $imgData;
        exit;
    }

    private function initSession($cookieFile)
    {
        $ch = curl_init($this->baseUrl . '/bs/login');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_exec($ch);
        curl_close($ch);
    }

    public function saveCookie()
    {
        if (IS_POST) {
            $aid = I('post.aid', 0, 'intval');
            $cookie = I('post.cookie', '', 'trim');
            if (!$aid || $cookie === '') {
                $this->ajaxReturn(['status' => 0, 'msg' => '参数错误']);
            }

            M('channel_account')->where(['id' => $aid])->save(array(
                'cookie' => $cookie,
                'cookie_status' => 1,
                'cookie_update_time' => time(),
            ));
            $this->ajaxReturn(['status' => 1, 'msg' => 'Cookie已保存']);
        }
    }

    public function loginAccount()
    {
        $aid = I('request.aid', 0, 'intval');
        $account = M('channel_account')->where(['id' => $aid])->find();

        if (IS_POST) {
            $username = I('post.username', '', 'trim');
            $password = I('post.password', '', 'trim');
            $captcha = I('post.captcha', '', 'trim');
            if (!$aid || $username === '' || $password === '' || $captcha === '') {
                $this->ajaxReturn(['status' => 0, 'msg' => '请填写完整信息']);
            }

            M('channel_account')->where(['id' => $aid])->save(array(
                'mch_id' => $username,
                'signkey' => $password,
                'cookie_update_time' => time(),
            ));

            $cookieFile = $this->getCookieFile($aid);
            $result = $this->doLogin($cookieFile, $username, $password, $captcha);
            if ($result['code'] == 1) {
                $cookie = @file_get_contents($cookieFile);
                $valid = $this->checkCookieValid($cookieFile);
                if ($cookie !== false && trim($cookie) !== '' && $valid) {
                    M('channel_account')->where(['id' => $aid])->save(array(
                        'cookie' => trim($cookie),
                        'cookie_status' => 1,
                        'cookie_update_time' => time(),
                    ));
                    $this->ajaxReturn(['status' => 1, 'msg' => '登录成功']);
                }
            }

            M('channel_account')->where(['id' => $aid])->save(array(
                'cookie_status' => 0,
                'cookie_update_time' => time(),
            ));
            $this->ajaxReturn(['status' => 0, 'msg' => $result['msg']]);
        }

        $this->assign('aid', $aid);
        $this->assign('account', $account);
        $this->display('loginAccount');
    }

    private function doLogin($cookieFile, $username, $password, $captcha)
    {
        $loginUrl = $this->baseUrl . '/bs/login';
        $postData = http_build_query(array(
            'username' => $username,
            'password' => $password,
            'validateCode' => $captcha,
            'rememberMe' => 'false',
        ));

        $ch = curl_init($loginUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Accept: */*',
            'Accept-Language: zh-CN,zh;q=0.9',
            'Connection: keep-alive',
            'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
            'Origin: ' . $this->baseUrl,
            'Referer: ' . $this->baseUrl . '/bs/login',
            'X-Requested-With: XMLHttpRequest',
        ));
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $response = curl_exec($ch);
        curl_close($ch);

        $resArr = json_decode($response, true);
        if (isset($resArr['code']) && intval($resArr['code']) === 0) {
            return array('code' => 1, 'msg' => '成功');
        }

        $msg = isset($resArr['msg']) ? $resArr['msg'] : '未知错误';
        return array('code' => 0, 'msg' => $msg);
    }

    private function checkLoginStatus()
    {
        return false;
    }

    private function checkCookieValid($cookieFile)
    {
        $targetUrl = $this->baseUrl . '/bs/biz/notice/list';
        $postData = http_build_query(array(
            'pageSize' => 10,
            'pageNum' => 1,
            'orderByColumn' => 'id',
            'isAsc' => 'desc',
            'noticeType' => '',
            'noticeTitle' => '',
            'readStatus' => '',
        ));

        $ch = curl_init($targetUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Accept: application/json, text/javascript, */*; q=0.01',
            'Accept-Language: zh-CN,zh;q=0.9,zh-HK;q=0.8',
            'Connection: keep-alive',
            'Content-Type: application/x-www-form-urlencoded',
            'Origin: ' . $this->baseUrl,
            'Referer: ' . $this->baseUrl . '/bs/biz/notice',
            'Sec-Fetch-Dest: empty',
            'Sec-Fetch-Mode: cors',
            'Sec-Fetch-Site: same-origin',
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36',
            'X-Requested-With: XMLHttpRequest',
        ));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $response = curl_exec($ch);
        curl_close($ch);

        $resArr = json_decode($response, true);
        return is_array($resArr) && isset($resArr['code']) && intval($resArr['code']) === 0;
    }

    public function editAccount()
    {
        $aid = I('get.aid', 0, 'intval');
        $account = array(
            'id' => 0,
            'channel_id' => I('get.channel_id', 0, 'intval'),
            'title' => '',
            'mch_id' => '',
            'signkey' => '',
            'weight' => 1,
            'status' => 1,
            'gudingmoney' => '',
        );
        if ($aid) {
            $dbAccount = M('channel_account')->where(['id' => $aid])->find();
            if ($dbAccount) {
                $account = $dbAccount;
            }
        }
        $channels = M('Channel')->where(['status' => 1])->select();
        $this->assign('aid', $aid);
        $this->assign('account', $account);
        $this->assign('channels', $channels);
        $this->display('editAccount');
    }

    public function saveAccount()
    {
        if (IS_POST) {
            $aid = I('post.aid', 0, 'intval');
            $data = I('post.data/a');
            if (!$aid) {
                $this->ajaxReturn(['status' => 0, 'msg' => '参数错误']);
            }
            $gudingmoney = isset($data['gudingmoney']) ? trim($data['gudingmoney']) : '';
            if ($gudingmoney === '') {
                $this->ajaxReturn(['status' => 0, 'msg' => '固定金额池不能为空']);
            }
            $save = array(
                'channel_id' => intval($data['channel_id']),
                'title' => trim($data['title']),
                'mch_id' => trim($data['mch_id']),
                'signkey' => trim($data['signkey']),
                'weight' => intval($data['weight']),
                'status' => intval($data['status']),
                'gudingmoney' => $gudingmoney,
                'updatetime' => time(),
            );
            $res = M('channel_account')->where(['id' => $aid])->save($save);
            $this->ajaxReturn(['status' => $res ? 1 : 0, 'msg' => $res ? '保存成功' : '保存失败']);
        }
    }

    public function saveCookieView()
    {
        $aid = I('get.aid', 0, 'intval');
        $account = M('channel_account')->where(['id' => $aid])->find();
        $this->assign('aid', $aid);
        $this->assign('account', $account);
        $this->display('saveCookie');
    }

    public function checkCookieStatus()
    {
        $aid = I('post.aid', 0, 'intval');
        $where = array();
        if ($aid) {
            $where['id'] = $aid;
        }

        $accounts = M('channel_account')->where($where)->select();
        if (empty($accounts)) {
            $this->ajaxReturn(['status' => 0, 'msg' => '账号不存在']);
        }

        $result = array();
        foreach ($accounts as $account) {
            $cookieFile = $this->getCookieFile($account['id']);
            $valid = $this->checkCookieValid($cookieFile);
            M('channel_account')->where(['id' => $account['id']])->save(array(
                'cookie_status' => $valid ? 1 : 0,
                'cookie_update_time' => time(),
            ));
            $result[] = array(
                'aid' => $account['id'],
                'cookie_status' => $valid ? 1 : 0,
                'cookie_text' => $valid ? '已登录' : '未登录',
            );
        }

        $this->ajaxReturn(array(
            'status' => 1,
            'data' => $result,
        ));
    }

    private function getCookieFile($aid)
    {
        return RUNTIME_PATH . 'jiuaigou_cookie_' . intval($aid) . '.txt';
    }

    private function formatTime($value)
    {
        if (empty($value)) {
            return '-';
        }
        return date('Y-m-d H:i:s', intval($value));
    }
}
