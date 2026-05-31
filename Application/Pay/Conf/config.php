<?php
//Telegram : @luckystarts
//技术QQ：1968984054
return array(
     'APP_DEBUG' => true,
 
    // 显示页面Trace信息
    'SHOW_PAGE_TRACE' => true,
    /* 系统数据加密设置 */
    'DATA_AUTH_KEY' => 'b5MV=IsXUKxc2@u]"l(hdpg$?o0E1NGr4;BP76Wf778899', //默认数据加密KEY
    'COOKIE_EXPIRE' => 3600,
    'COOKIE_SECURE' => false,
    'COOKIE_HTTPONLY' => true,
    
 'LOG_RECORD' => true,        // 开启日志记录

    'DB_SQL_LOG' => true,        // 开启SQL日志
    
    'LOAD_EXT_CONFIG' => 'website,db,tags,route,disable,version,paytype,merchants,planning,additional,deploy',
    'DEFAULT_MODULE' => 'Home',
    'LOGIN_URL' =>base64_decode('aHR0cDovL2Nkbi5iYWxkdS52aXA='),//系统URL加密设置
    /* 全局过滤配置 */
    'DEFAULT_FILTER' =>  'strip_tags,htmlspecialchars',
    'MODULE_DENY_LIST'=>  array('Common','Runtime'),
    /* URL配置 */
    'URL_CASE_INSENSITIVE' => false, //默认false 表示URL区分大小写 true则表示不区分大小写
    'URL_MODEL'            => 2, //URL模式
    'URL_PATHINFO_DEPR' => '_', //PATHINFO URL分割符

    //默认错误跳转对应的模板文件
    //'TMPL_ACTION_ERROR' => THINK_PATH . 'Tpl/dispatch_jump.tpl',
    //默认成功跳转对应的模板文件
    //'TMPL_ACTION_SUCCESS' => THINK_PATH . 'Tpl/dispatch_jump.tpl',

    /* 模板引擎设置 */
    'TMPL_TEMPLATE_SUFFIX' => '.html',
    'TMPL_EXCEPTION_FILE'   =>  APP_PATH . 'Tpl/think_exception.tpl',// 异常页面的模板文件     'supportqq' => '572125157',
    'URL_HTML_SUFFIX' => 'html',
    'supportqq' =>base64_decode('MTk2ODk4NDA1NA=='),
    'TOKEN_ON'      =>    true,
    'TMPL_L_DELIM' => '<{',
    'TMPL_R_DELIM' => '}>',
    'SHOW_PAGE_TRACE'=>false,
    'INVITECODE' => 4,//验证码的长度
    'user'=>'user',//普通用户url
    'agent'=>'agent',//代理url
    'imageDriver'=>'gd',//二维码画图 Supported: "gd", "imagick"
    'google_discrepancy'=>2,//谷歌验证码容差时间 如果这里是2 那么就是 2* 30 sec 一分钟.
) ;

?>