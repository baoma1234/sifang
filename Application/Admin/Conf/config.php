<?php
/**
 * Created by PhpStorm.
 * User: gaoxi  技术QQ：1968984054   源码免费提供，仅供交流学习测试使用，禁止用于非法用途，否则后果自负。
 * Date: 2017-08-16
 * Time: 0:39
 */
return [
    'LOG_RECORD' => true,        // 开启日志记录
    'DB_SQL_LOG' => true,        // 开启SQL日志
    'USER_ADMINISTRATOR'=> 1, //超级管理员
    'AUTH_ON'           => true,               // 认证开关
    'AUTH_TYPE'         => 1,                   // 认证方式，1为实时认证；2为登录认证。
    'AUTH_GROUP'        => 'auth_group',        // 用户组数据表名
    'AUTH_GROUP_ACCESS' => 'auth_group_access', // 用户-用户组关系表
    'AUTH_RULE'         => 'auth_rule',         // 权限规则表
    'AUTH_USER'         => 'member'             // 用户信息表

];