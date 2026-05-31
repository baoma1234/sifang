<?php 
//Telegram : @luckystarts
//技术QQ：1968984054
		return array(
			'WEB_TITLE' => '久远支付',
			'DOMAIN' => 'jyzf.live',
			'MODULE_ALLOW_LIST'   => array('Home','User','admin','Install', 'Weixin','Pay','Cashier','Agent','Payment'),
			'URL_MODULE_MAP'  => array('admin'=>'admin', 'agent'=>'user', 'user'=>'user'),
			'LOGINNAME' => 'taoke',
			'HOUTAINAME' => 'admin',
		);
//为了安全起见 已经停用后台修改路径功能  请参考如下说明进行修改
//例如：
//---------------------------------------------------------------------------------------------------------------------------------------------------------------
//'WEB_TITLE' => '聚合支付',     注：聚合支付为站点名称
//'DOMAIN' => '172.83.155.100',   注：172.83.155.100为站点访问地址
//'MODULE_ALLOW_LIST'   => array('Home','User','qadmin123','Install', 'Weixin','Pay','Cashier','Agent','Payment'),    注：qadmin123为新的后台地址
//'URL_MODULE_MAP'  => array('qadmin123'=>'admin', 'agent'=>'user', 'user'=>'user'),  注：qadmin123为新的后台地址
//'LOGINNAME' => 'login', 注：前台登录地址
//'HOUTAINAME' => 'qadmin123',    注：qadmin123为新的后台地址
//---------------------------------------------------------------------------------------------------------------------------------------------------------------
//切记不熟悉的情况下不要随意修改其他字段
//参数中的 qadmin123 即为新的后台地址 保存后  清空浏览器缓存后 使用新地址登录
//如 www.1pay.com/qadmin123
?>