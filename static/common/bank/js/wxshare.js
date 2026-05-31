var Intent = null,
File = null,
Uri = null,
main = null;
var shares = null;
var shareImageUrl = '';
mui.plusReady(function() {  
    updateSerivces();
    if (plus.os.name == "Android") {
        Intent = plus.android.importClass("android.content.Intent");
        File = plus.android.importClass("java.io.File");
        Uri = plus.android.importClass("android.net.Uri");
        main = plus.android.runtimeMainActivity();
    }
});
/**
 * 更新分享服务
 */
function updateSerivces() {
    plus.share.getServices(function(s) {
        shares = {};
        for (var i in s) {
            var t = s[i];
            shares[t.id] = t;
        }
        outSet("获取分享服务列表成功");
    }, function(e) {
        outSet("获取分享服务列表失败：" + e.message);
    });
}
/**
 * 分享操作
*/
function shareAction(id, ex) {
    var s = null;
    if (!id || !(s = shares[id])) {
        outLine("无效的分享服务！");
        return;
    }
    if (s.authenticated) {
        outSet("---已授权---");
        shareMessage(s, ex);
    } else {
        outSet("---未授权---");
			s.authorize(function() {
			shareMessage(s, ex);
        }, function(e) {
         outLine("认证授权失败");
        });
    }
}
/**
* 发送分享消息
 */
function shareMessage(s, ex) {
	var content = localStorage.getItem("share_content");
	var href = localStorage.getItem("share_href");
	var title = localStorage.getItem("share_title");
	var thumbs = localStorage.getItem("share_thumbs");
	var pictures = localStorage.getItem("share_pictures");
	
    var msg = {
        content: content,
        href: href,
        title: title,
        content: content,
        thumbs: [thumbs],
        pictures: [pictures],
        extra: {
            scene: ex
        }
    };
    s.send(msg, function() {
        outLine("分享成功!");
    }, function(e) {
        outLine("分享失败!");
    });
}
/**
* 分享按钮点击事件
*/
function shareHref() {
    var ids = [{
			id: "weixin", 
			ex: "WXSceneSession"  /*微信好友*/
        }, {
            id: "weixin",
            ex: "WXSceneTimeline" /*微信朋友圈*/
        }, {
            id: "qq"   /*QQ好友*/
        }, {
            id: "tencentweibo"   /*腾讯微博*/
        },{
            id: "sinaweibo"  /*新浪微博*/
        }],
        bts = [{
            title: "发送给微信好友"
        }, {
            title: "分享到微信朋友圈"
        }];
        plus.nativeUI.actionSheet({
            cancel: "取消",
            buttons: bts
        },
        function(e) {
            var i = e.index;
            if (i > 0) {
                shareAction(ids[i - 1].id, ids[i - 1].ex);
            }
        }
        );
}       
 // 控制台输出日志
function outSet(msg) {
    console.log(msg);
}
// 界面弹出吐司提示
function outLine(msg) {
    mui.toast(msg);
}
//小程序分享
function WxMini_Share(){
	var content = localStorage.getItem("share_content");
	var href = localStorage.getItem("share_href");
	var title = localStorage.getItem("share_title");
	var thumbs = localStorage.getItem("share_thumbs");
	var pictures = localStorage.getItem("share_pictures");
	
	WXMiniProgramObject mini = new WXMiniProgramObject();
	miniProgramObj.webpageUrl = "http://www.qq.com"; //
	miniProgramObj.miniprogramType = WXMiniProgramObject.MINIPTOGRAM_TYPE_RELEASE;// 正式版:0，测试版:1，体验版:2
	miniProgramObj.userName = "gh_d43f693ca31f"; // 小程序原始id
	miniProgramObj.path = "/pages/media"; //小程序页面路径
	WXMediaMessage msg = new WXMediaMessage(miniProgramObj);
	msg.title = title; // 小程序消息title
	msg.description = content; // 小程序消息desc
	msg.thumbData = getThumb(); // 小程序消息封面图片，小于128k
	SendMessageToWX.Req req = new SendMessageToWX.Req();
	req.transaction = buildTransaction("webpage");
	req.message = msg;
	req.scene = SendMessageToWX.Req.WXSceneSession; // 目前支持会话
	api.sendReq(req);
}



