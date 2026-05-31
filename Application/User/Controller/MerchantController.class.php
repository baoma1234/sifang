<?php
/**
 * Created by PhpStorm.
 * User: gaoxi  技术QQ：1968984054   源码免费提供，仅供交流学习测试使用，禁止用于非法用途，否则后果自负。
 * Date: 2017-07-25
 * Time: 11:16
 */
namespace User\Controller;

/**
 * 商户进件申请控制器
 * Class MerchantController
 * @package User\Controller
 */
class MerchantController extends UserController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        //初始商户号
        $mch_id = $this->fans['memberid'].'-'.date('YmdHis');
        $this->assign('mch_id',$mch_id);
        $this->display();
    }
}