/**
 * Created by amos on 14-8-9.
 */
(function(global){
    if(global.__WPA){
        return;
    }

    //speed report
    var flag1 = 21848,
        flag2 = 1,
        flag3 = 1,
        flag_loaded = 28,
        reportCgi = 'http://report.huatuo.qq.com/report.cgi';

    //to avoid the difference when the api is not the same due to the different protocols
    if (location.protocol.indexOf('https') !== -1) {
        reportCgi = 'https://report.huatuo.qq.com/report.cgi';
    }

    var platform = 'pc',
        ua = navigator.userAgent;

    if (/(?:iphone|ipad|ipod)/i.test(ua)) {
        platform = 'ios';
    } else if(/android/i.test(ua)) {
        platform = 'android';
    }

    // conf
    var 
        // env can be preset by global.__WPAENV
        env = global.__WPAENV || 'production',
        version = '4.1.0',        
        // support file protocol
        //强制protocol为https
        protocol = 'https:',//location.protocol.indexOf('http') > -1 ? location.protocol : 'http:',
        hostMap = {
            development: 'dev',
            test: 'oa'
        },

        // static base can be preset by global.__WPASTATICBASE
        //todo
        //if it is https, then 'combo' should be prefixed with 'ssl'


        //staticBase = global.__WPASTATICBASE || protocol + '//' + (hostMap[env] || '') + 'combo.b.qq.com/qidian/wpa/dist/' + version,
        //本地开发时会手动传入staticBase,所以不能直接覆盖掉，先判断是否是本地的
        staticBase = global.__WPASTATICBASE,// || protocol + '//' + (hostMap[env] || '') + 'combo.b.qq.com/qidian/src/wpa/dist/' + version,

        host = protocol + '//' + (hostMap[env] || '') + 'wp.qiye.qq.com',
        // base can be preset by global.__WPABASE
        base = global.__WPABASE || host,
        // base can be preset by global.__WPAAPIBASE
        apiBase = global.__WPABAPIASE || host + '/api',
        stack = [],
        apiStack = [],
        tmpEventBucket = {},
        charset = 'utf-8'; 

    //重写staticBase为gtimg域名的
    if (!staticBase) {
        if (env === 'development') {
            staticBase = 'https://dev.gtimg.com';
            staticBase += '/qidian/src/wpa/dist/' + version;
        } else if (env === 'test') {
            staticBase = 'https://oa.gtimg.com';
            staticBase += '/qidian/src/wpa/dist/' + version;
        } else {
            staticBase = 'https://bqq.gtimg.com';
            staticBase += '/qidian/src/wpa/dist/' + version;
        }

        // staticBase += '/qidian/src/wpa/dist/' + version;
    }
    

    // fetch
    var doc = document,
        head = doc.head || doc.getElementsByTagName("head")[0] || doc.documentElement,
        baseElement = head.getElementsByTagName("base")[0],
        currentlyAddingScript;

    function fetch(uri) {
        var node = doc.createElement("script"),
            sTime;

        node.charset = charset;
        node.async = true;
        node.src = uri;
        node.id = 'LBFnode';

        // For some cache cases in IE 6-8, the script executes IMMEDIATELY after
        // the end of the insert execution, so use `currentlyAddingScript` to
        // hold current node, for deriving uri in `define` call
        currentlyAddingScript = node;

        node.onload = node.onreadystatechange = function() {
            if(!this.readyState || this.readyState=='loaded' || this.readyState=='complete') {
                var eTime = +new Date(),
                    timeCost = eTime - sTime;
                // console.log('loading pk1.js time:' + (eTime - sTime));
                var r = new Image(),
                    params = 'flag1=' + flag1 + '&flag2=' + flag2 + '&flag3=' + flag3 + '&' + flag_loaded + '=' + timeCost;
                // r.src = reportCgi + '?appid=20282&platform=' + platform + '&speedparams=' + encodeURIComponent(params);
            }
        };

        //start to load pk1.js
        sTime = +new Date();
        // ref: #185 & http://dev.jquery.com/ticket/2709
        baseElement ?
            head.insertBefore(node, baseElement) :
            head.appendChild(node);

        currentlyAddingScript = null;
    }

    function getScriptPosition() {
        var scripts = document.getElementsByTagName('script');
        return scripts.length > 0 ? scripts[scripts.length - 1] : null;
    }


    // Public API

    var exports = global.__WPA = {
        version: version,

        base: base,

        staticBase: staticBase,

        apiBase: apiBase,

        env: env,

        protocol: protocol,

        create: function(data){
            data.scriptPosition = getScriptPosition();
            stack.push(data);
        },

        on: function(type, cb){
            tmpEventBucket[type] ?
                tmpEventBucket[type].push(cb) :
                tmpEventBucket[type] = [cb];
        },

        api: function(){
            apiStack.push(arguments);
        },

        ready: function(onReady){
            exports.on('load', onReady);
        },

        getScriptPosition: getScriptPosition,

        _stack: stack,

        _apiStack: apiStack,

        _evtBkt: tmpEventBucket
    };

    // fetch packed modules
    fetch(staticBase + '/pk1.js');
})(this);
