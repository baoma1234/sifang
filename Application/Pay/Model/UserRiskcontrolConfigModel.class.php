<?php
/**
 * Created by PhpStorm.
 * User: gaoxi  技术QQ：1968984054   源码免费提供，仅供交流学习测试使用，禁止用于非法用途，否则后果自负。
 * Date: 2017-03-21
 * Time: 11:18
 */
namespace Pay\Model;

use Think\Model;

class UserRiskcontrolConfigModel extends RiskcontrolModel
{
    protected $config_info;

    public function __construct()
    {
        parent::__construct();
    }



    //查找某一配置
    public function findConfigInfo($user_id)
    {	
        $config_info = $this->where(['user_id' => $user_id, 'status' => 1])->find();
        if (!$config_info || $config_info['systemxz'] != 1) {
            $config_info = $this->where(['is_system' => 1, 'status' => 1])->find();
        }
        return $config_info = $config_info ? $config_info : false;
    }

  
}
