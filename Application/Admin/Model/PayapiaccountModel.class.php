<?php
/**
 * Created by PhpStorm.
 * User: gaoxi  技术QQ：1968984054   源码免费提供，仅供交流学习测试使用，禁止用于非法用途，否则后果自负。
 * Date: 2017-07-30
 * Time: 15:17
 */
namespace Admin\Model;

use Think\Model;

class PayapiaccountModel extends Model
{
    public function getAllsupplier()
    {
        $data = $this->join('LEFT JOIN __PAYAPI__ ON __PAYAPIACCOUNT__.payapiid = __PAYAPI__.id')
            ->select();
        return $data;
    }
}