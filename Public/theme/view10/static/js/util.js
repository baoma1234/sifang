(function ($) {
    let url = document.baseURI || document.URL;
    let localhostUrl = 'localhost'; //本地环境
    let devNGUrl = '10.10.10.13'; //开发环境NG地址
    let devUrl = 'http://10.10.10.11:8092'; //开发环境地址
    // 旧测试环境
    let oldUatNGUrl = '10.10.10.17'; //测试环境地址
    let oldUatUrl = 'http://10.10.10.17:8092'; //测试环境地址

    // 新测试环境
    let uatNGUrl = '10.40.50.58'; //测试环境地址
    // let uatUrl = 'http://10.40.50.53:8092'; //测试环境地址
    let uatUrl = 'http://10.40.50.4/gw-web-portal2'; //测试环境地址
    let prodNGUrl = 'portal2.joinpay.com'; //生产NG环境
    let prodNGUrlHttps = 'www.joinpay.com'; //生产NG环境
    // let prodUrl = 'https://portal2-backend.joinpay.com'; //生产API链接
	let prodUrl = 'https://b.joinpay.com/apis';
    let openUrl = 'www.joinpay.com';

    //获取BaseUrl
    $.getBaseUrl = function() {

        if (url.indexOf(localhostUrl) != -1 || url.indexOf(devNGUrl) != -1) {
            return devUrl;
        } else if (url.indexOf(uatNGUrl) != -1) {
            return uatUrl;
        } else if (url.indexOf(oldUatNGUrl) != -1) {
            return oldUatUrl;
        } else if (url.indexOf(prodNGUrl) != -1 || url.indexOf(prodNGUrlHttps) != -1 || url.indexOf(openUrl) != -1) {
            return prodUrl;
        }
    };

    // 获取开放平台网址
    $.getOpenUrl = function() {

        if (url.indexOf(localhostUrl) != -1) {
            return 'http://' + window.location.host + '/joinpay-site';
        } else if (url.indexOf(devNGUrl) != -1) {
            return 'http://' + devNGUrl + '/official';
        } else if (url.indexOf(uatNGUrl) != -1) {
            return 'http://' + uatNGUrl + '/official';
        } else if (url.indexOf(oldUatNGUrl) != -1) {
            return 'http://' + oldUatNGUrl + '/official';
        } else if (url.indexOf(prodNGUrl) != -1 || url.indexOf(prodNGUrlHttps) != -1) {
            return 'https://' + prodNGUrlHttps;
        }
    };

    // 获取平台网址
    $.getProUrl = function() {

        if (url.indexOf(localhostUrl) != -1) {
            return 'http://' + window.location.host + '/joinpay-site';
        } else if (url.indexOf(devNGUrl) != -1) {
            return 'http://' + devNGUrl + '/official';
        } else if (url.indexOf(uatNGUrl) != -1) {
            return 'http://' + uatNGUrl + '/official';
        } else if (url.indexOf(oldUatNGUrl) != -1) {
            return 'http://' + oldUatNGUrl + '/official';
        } else if (url.indexOf(prodNGUrl) != -1 || url.indexOf(prodNGUrlHttps) != -1) {
            return 'https://' + prodNGUrlHttps;
        }
    };

    // 获取商户后台
    $.getBackUrl = function(){
        if (url.indexOf(localhostUrl) != -1 || url.indexOf(devNGUrl) != -1) {
            return 'http://' + devNGUrl + '/#';
        } else if (url.indexOf(uatNGUrl) != -1) {
            return 'http://' + uatNGUrl + '/#';
        } else if (url.indexOf(oldUatNGUrl) != -1) {
            return 'http://' + oldUatNGUrl + '/#';
        } else if (url.indexOf(prodNGUrl) != -1 || url.indexOf(prodNGUrlHttps) != -1) {
            return 'https://b.joinpay.com/#'
        }
    };

    $.apiPath = {
        host : 'http://10.10.10.11:8092',
        filePath: '/public/getFileSysUrl',
        bannerPath : '/public/listBanner',
        pointsPath : '/public/getActicleList?number=5',
        questionPath : '/public/getActicleList?number=3',
        newsPath: '/public/listTopicCenterActicle',
        safeClassPath: '/public/getActicleList?number=9',
        detailPath: '/public/getArticleDetail',
        informationPath: '/public/getActicleList?number=100',
        agreementPath: '/public/listOfficialFilePage',
        getUnclosedNotice:'/public/index/getWebsiteNotice',
        agreementSigned: '/public/alt/sign'
    };

    $.getRegister = function(path){51
        window.location.href =  $.getBackUrl() + path;
    };

    $.getCreateYear = function(date) {
        var dateStr = new Date(date);
        // console.log(date,dateStr);
        return dateStr.getFullYear();
    };

    $.getCreateDate = function(date) {
        var dateStr = new Date(date);
        return (dateStr.getMonth() + 1) + '-' + dateStr.getDate();
    };

    $.getUrlParam = function(name) {
        var search = decodeURI(decodeURI(window.location.search)); // 解决中文乱码
        var reg = new RegExp("(^|&)" + name + "=([^&]*)(&|$)"); //构造一个含有目标参数的正则表达式对象
        var r = search.substr(1).match(reg);  //匹配目标参数
        if (r != null) return unescape(r[2]); return null; //返回参数值
    };

    // 添加文件系统域名
    $.addHttp = function(str) {
        var re = /^http(s)?:\/\/.+/;
        return re.test(str) ? str : "http://" + str;
    };

    // 添加文件系统域名
    $.addDomain = function(str) {
        var fileUrl = JSON.parse(localStorage.getItem('fileUrl'));
        return  fileUrl + str;
    };

    //获取专题信息
    $.getTopic = function(number) {
        var topic = {};
        switch (number) {
            case 3:
                topic.name =  "常见问题";
                topic.href =  "./customerProblems.html";break;
            case 9:
                topic.name =  "金融安全课堂";
                topic.href =  "./safeClass.html";break;
            case 10:
                topic.name =  "公告及看点";
                topic.href =  "./customerPoints.html";break;
            case 100:
                topic.name =  "行业资讯";
                topic.href =  "./customerPoints.html?type=2";break;
            default:
                topic.name =  "专题中心";
                topic.href =  "./newsCenter.html";
        }
        return topic;
    };

    //生成分页框
    $.appendPaginationHtml = function(currentPage, endPageNum, parent,page) {
        var paginationHtml;
        // 分页框
        if( currentPage && endPageNum) {
            paginationHtml = "<li class=\"pagination-btn Previous\">&lt;</li>" +
                "<li data-index=\"1\">1</li>";

            if( endPageNum > 5 ) {
                if( currentPage < 5){
                    if( endPageNum < 8 && (endPageNum - currentPage) < 4) {
                        for (var i = 2; i < (endPageNum + 1); i++ ) {
                            paginationHtml += "<li data-index=\"" + i + "\">" + i + "</li>";
                        }
                    }else{
                        for (var i = 2; i < (currentPage + 3);i++ ) {
                            paginationHtml += "<li data-index=\"" + i + "\">" + i + "</li>";
                        }
                        paginationHtml += "<li>...</a></li>";
                        paginationHtml += "<li data-index=\"" + endPageNum + "\">" + endPageNum + "</li>";
                    }

                }else if( currentPage > 3 && currentPage < (endPageNum - 3)) {
                    paginationHtml += "<li>...</li>";
                    for (var i = currentPage - 2; i < (currentPage + 3);i++ ) {
                        paginationHtml += "<li data-index=\"" + i + "\">" + i + "</li>";
                    }
                    paginationHtml += "<li><a>...</a></li>";
                    paginationHtml += "<li data-index=\"" + endPageNum + "\">" + endPageNum + "</li>";
                }else if( currentPage > (endPageNum - 4)) {
                    paginationHtml += "<li>...</li>";
                    for (var i = currentPage - 2; i < (endPageNum + 1); i++ ) {
                        paginationHtml += "<li data-index=\"" + i + "\">" + i + "</li>";
                    }
                }
            }else{
                for (var i = 2; i <= endPageNum; i++ ) {
                    paginationHtml += "<li data-index=\"" + i + "\">" + i + "</li>";
                }
            }

            paginationHtml += "<li class=\"pagination-btn Next\">&gt;</li>";
        }else{
            paginationHtml = "";
        }

        $(parent).append(paginationHtml);

        $(parent + " li").each(function() {
            if ($(this).data("index") === currentPage) {
                $(this).addClass('active');
            }

            if($(this).data("index")){
                $(this).addClass('dark-li');
                $(this).on('click', function() {

                    window.location.href = page + (page.indexOf("?") === -1 ? "?" : "&") + 'page=' + $(this).data("index");
                })
            }
        });

        if (currentPage !== 1) {
            $(".Previous").addClass('dark-li');
            $(".Previous").on('click', function() {
                window.location.href = page + (page.indexOf("?") === -1 ? "?" : "&") + 'page=' + (currentPage - 1);
            });
        }

        if (currentPage !== endPageNum) {
            $(".Next").addClass('dark-li');
            $(".Next").on('click', function() {
                window.location.href = page + (page.indexOf("?") === -1 ? "?" : "&") + 'page=' + (currentPage + 1);
            });
        }
    };

    //ajax获取信息
    $.getBackMsg = function(path, parent, callback) {
        var url = $.apiPath.host + path;

        $(parent).empty();
        $(parent).appendLoading();

        $.ajax({
            type: 'GET',
            url: url,
            dataType: 'json',
            crossDomain: true == !(document.all),
            success: function(response) {
                if (response.msg === "success") {
                   callback(response);
                } else {
                    $(parent).empty();
                    $(parent).append("<span class=\"error-msg\">数据未获取到</span>");
                }
            },
            error: function (error) {
                $(parent).empty();
                $(parent).append("<span class=\"error-msg\">请求失败</span>");
            }
        })
    };

    //index news
    //ajax获取信息
    $.getIndexNews = function(path, parent, callback) {
        var url = $.apiPath.host + path;

        $(parent).empty();
        $(parent).appendLoading();
        $('.index-news-img').attr('src','./images/index/banner.png');

        $.ajax({
            type: 'GET',
            url: url,
            dataType: 'json',
            crossDomain: true == !(document.all),
            success: function(response) {
                if (response.msg === "success") {
                    callback(response);
                } else {
                    $(".index-news").empty();
                    $('.index-news-img').attr('src','./images/index/banner.png');
                    $('.index-news').append("<span class=\"msg-not-find\">获取新闻失败</span>");
                }
            },
            error: function (error) {
                $(".index-news").empty();
                $('.index-news-img').attr('src','./images/index/banner.png');
                $('.index-news').append("<span class=\"msg-not-find\">获取新闻失败</span>");
            }
        })
    };

    //ajax banner信息
    $.getBannerMsg = function(path, parent, callback) {
        var url = $.apiPath.host + path;
        var bannerHtml = "<div class=\"index-banner-item item active\">\n" +
            "<img class='index-banner-1 index-banner-img' src=\"" + "./images/index/banner.png" + "\" alt=\"First slide\">\n" +
            "</div>";

        $.ajax({
            type: 'GET',
            url: url,
            dataType: 'json',
            crossDomain: true == !(document.all),
            success: function(response) {
                if (response.msg === "success") {
                    callback(response);
                } else {
                    $(parent).empty();
                    $(parent).append(bannerHtml);
                }
            },
            error: function (error) {
                $(parent).empty();
                $(parent).append(bannerHtml);
            }
        })
    };

    //文本溢出省略号
    $.fn.mitulineHide = function(options) {
        var defaultOptions = {num: 150};
        var options = $.extend(defaultOptions,options);
        var text = this.text().trim();
        if (text.length > options.num) {
            text = text.substring(0, options.num - 1) + "...";
            this.text(text);
        }
    };

    //添加加载
    $.fn.appendLoading = function() {
        this.append("<span class=\"loading-title\">加载中</span>");
    };
})(jQuery);


$(document).ready(function(){
    $.apiPath.host = $.getBaseUrl();
    //导航点击效果
    $('.nav-sm-li').on('click',function(e){
        $('.nav-sm-list').toggleClass('nav-sm-list-on');
    });

    $('.nav-open-platform').click(function() {
        window.location.href = $.getOpenUrl() + '/open-platform/pages/platform.html';
    });

    $('.header-pro-toggle').on('click',function(e){
        $('.header-sm-pro-list').toggleClass('header-sm-list-on');
        $('.header-pro-toggle').toggleClass('pro-toggle-in');
        $('.header-pro-arrow').toggleClass('header-pro-arrow-up');
    });

    $('.header-aboutus-toggle').on('click',function(e){
        $('.header-sm-aboutus-list').toggleClass('header-sm-list-on');
        $('.header-aboutus-toggle').toggleClass('aboutus-toggle-in');
        $('.header-aboutus-arrow').toggleClass('header-aboutus-arrow-up');
    });
});

