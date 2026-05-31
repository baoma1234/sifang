<?php
$pay_orderid = 'E'.date("YmdHis").rand(10000,99999);    //订单号
$pay_amount = "1.00";    //交易金额
$product_name="DEMO页测试订单";
?>

<!DOCTYPE html>
<html>
<head>

    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta content="width=device-width, initial-scale=1, maximum-scale=1.0, user-scalable=0" name="viewport">
    <title>聚合支付 DEMO测试页</title>
    <link href="./demo/css/Reset.css" rel="stylesheet" type="text/css">
    <script src="./demo/js/jquery-1.11.3.min.js"></script>
    <link href="./demo/css/main12.css" rel="stylesheet" type="text/css">
    <style>
        .pay_li input{
            display: none;
        }
        .immediate_pay{
            border:none;
        }
        .PayMethod12
        {
            min-height: 150px;
        }
        @media screen and (max-width: 700px) {
            .PayMethod12{
                padding-top:0;
            }
            .order-amount12{
                margin-bottom: 0;
            }
            .order-amount12,.PayMethod12{
                padding-left: 15px;padding-right: 15px;
            }
        }
        .order-amount12-right input{
            border:1px solid #efefef;
            width:6em;
            padding:5px 20px;
            font-size: 15px;
            text-indent: 0.5em;
            line-height: 1.8em;
        }

 .si_PayMethod12 {
            width: 1010px;
            padding: 35px 35px;
            background: #fff;
            margin-top: 36px;
        }
        .si_PayMethod12 h2 {
            font-size: 16px;
            height: 30px;
            line-height: 30px;
            /*margin-bottom: 10px;*/
        }
        .si_PayMethod12 ul {
            overflow: hidden;
        }
        .si_PayMethod12 ul li {
            padding: 20px 0 0 30px;
            margin-bottom: 10px;
            margin-right: 10px;
            float: left;
        }
        .si_PayMethod12 ul li .iw {
            display: inline-block;
            vertical-align: middle;
            width: 114px;
            height: 25px;
            padding-top: 5px;
            padding-left: 10px;
            background: url(bank_logo.png) no-repeat;
            border: #DDD solid 1px;
            text-indent: -9999px;
        }
        .ICBC {background-position: 0px -186px !important;}
        .CCB {background-position: 0px -93px !important;}
        .ABC {background-position: 0px 0px !important;}
        .CMBCHINA {background-position: -8px -465px !important;}
        .BOC {background-position: -12px -279px !important;}
        .BOCO {background-position: 0px -372px !important;}
        .POST {background-position: 0px -930px !important;}
        .CEB {background-position: 0px -744px !important;}
        .GDB {background-position: 0px -1116px !important;}
        .CIB {background-position: 0px -651px !important;}
        .SPDB {background-position: -5px -1302px !important;}
        .CMBC {background-position: 0px -558px !important;}
        .ECITIC {background-position: -5px -837px !important;}
        .PINGANBANK {background-position: -137px -1488px !important;}
        .SHB {background-position: -150px 0px !important;}
        .SRCB {background-position: -137px -1209px !important;}
        .BCCB {background-position: -7px -1023px !important;}
        .CBHB {background-position: -138px -651px !important;}
        .NJCB {background-position: -145px -930px !important;}
        .HXB {background-position: -5px -1395px !important;}

    </style>
    <script>
        var lastClickTime;
        var orderNo = "15248148988132090444";
        $(function () {
            $('.PayMethod12 ul li').each(function (index, element) {
                $('.PayMethod12 ul li').eq(5 * index + 4).css('margin-right', '0')
            });

            //支付方式选择
            $('.PayMethod12 ul li').click(function (e) {
                $(this).addClass('active').siblings().removeClass('active');
            });

            $(".pay_li").click(function () {
                $(".pay_li").removeClass("active");
                $(this).addClass("active");
            });
            //点击立即支付按钮
            $(".immediate_pay").click(function () {
                //判断用户是否选择了支付渠道
                if (!$(".pay_li").hasClass("active")) {
                    message_show("请选择支付功能");
                    return false;
                }
                //获取选择的支付渠道的li
                var payli = $(".pay_li[class='pay_li active']");
                if (payli[0]) {
                    prepay(payli.attr("data_power_id"), payli.attr("data_product_id"));
                } else {
                    message_show("请重新选择支付功能");
                }

            });


            $('.mt_agree').click(function (e) {
                $('.mt_agree').fadeOut(300);
            });

            $('.mt_agree_main').click(function (e) {
                return false;
            });

            //弹窗
        // 		$('.pay_sure12').click(function(e) {
        // 			$(this).fadeOut();
        // 		});

            $('.pay_sure12-main').click(function (e) {
                //e. stopPropagation();
                return false;
            });
        });

</script>

    <script>
        if(/Android|webOS|iPhone|iPod|BlackBerry/i.test(navigator.userAgent)) {
            window.location.href = "mobile.php";
        } else {

        }
    </script>

</head>
<body style="background-color:#f9f9f9">
<form action="index1.php" method="post" autocomplete="off">
<!--弹窗开始-->
<div class="pay_sure12">
    <div class="pay_sure12-main">
        <h2>支付确认</h2>
        <h3 class="h3-01">请在新打开的页面进行支付！<br><strong>支付完成前请不要关闭此窗口。</strong></h3>
        <div class="pay_sure12-btngroup">
            <a class="immediate_button immediate_payComplate" onclick="callback_pc();">已完成支付</a>
            <a class="immediate_button immediate_payChange" onclick="hide();">更换支付方式</a>
        </div>
        <p>支付遇到问题？请联系 <span class="f12 blue">在线客服</span> 获得帮助。</p>
    </div>
</div>
<!--弹窗结束-->
<!--导航-->
<div class="w100 navBD12">
    <div class="w1080 nav12">
        <div class="nav12-left">
            <a href="/"><img src="/Uploads/logo/5d41cdd0bcf75.png" style="max-height: 45px;"></a>
            <span class="shouyintai"></span>
        </div>
        <div class="nav12-right">
                <span class="contact">支付体验收银台</span>
            </div>
    </div>
</div>
<!--订单金额-->
<div class="w1080 order-amount12" style="border-radius: 1em;">
    <ul class="order-amount12-left">
        <li>
            <span>商品名称：</span>
            <span><?php echo $product_name;?></span>
        </li>
        <li>
            <span>订单编号：</span>
            <span><?php echo $pay_orderid;?></span>
        </li>
    </ul>
    <div class="order-amount12-right">
        <span>订单金额：</span>
        <strong><input type="text" name="amount" value="<?php echo $pay_amount;?>"></strong>
        <span>元</span>
    </div>
</div>
<!--支付方式-->

    <input type="hidden" name="orderid" value="<?php echo $pay_orderid;?>">
   
<div class="w1080 PayMethod12" style="border-radius: 1em;">
    <div class="row">
        <h2>支付方式</h2>
        <ul>



            <label for="zfb">

            
			<li class="pay_li active" data_power_id="3000000011" data_product_id="3000000001">
            <input value="932" checked="checked" name="channel" id="zfb" type="radio">

                <i class="i1"></i>
                <span>支付宝扫码</span>
            </li></label>


           


            <label for="wx">
            <li class="pay_li" data_power_id="3000000031" data_product_id="3000000031">
                <input value="902" name="channel" id="wx" type="radio">

                <i class="i2"></i>
                <span>微信扫码</span>

            </li>  </label>
          
          
	
          
			<label for="qq">
				<li class="pay_li" data_power_id="3000000041" data_product_id="3000000041">
            <input value="908"  name="channel" id="qq" type="radio">

                <i class="i5"></i>
                <span>QQ钱包</span>
            </li></label>


           
			
			
			<label for="baidu">
			<li class="pay_li" data_power_id="3000000051" data_product_id="3000000051">
            <input value="909" name="channel" id="baidu" type="radio">

                <i class="i6"></i>
                <span>百度钱包</span>
            </li></label>
			

			
		
			 <label for="yl">
            <li class="pay_li" data_power_id="3000000061" data_product_id="3000000061">
                <input value="911" name="channel" id="yl" type="radio">

                <i class="i3"></i>
                <span>银联快捷</span>
            </li></label>
			

                <label class="bank">
                     <li class="pay_li" data_power_id="3000000071" data_product_id="3000000071">
                    <input type="radio" name="channel" id="wz"  value="918" />
                        <i class="i3"></i>
                        <span>网银支付</span>

                   </li></label>
         

			




        </ul>
    </div>
</div>
<!--立即支付-->
  
  <!--    网银支付OR信用卡支付-->
<div class="w1080 si_PayMethod12" style="border-radius: 1em; display: none">
    <div class="row">
        <h2>网银支付OR信用卡支付</h2>
        <ul>
            <li>
                <label class="from">
                    <input type="radio" name="bankAbbr" value="ICBC"  style="margin-top:10px;float:left; height:13px;" />
                    <div class="iw ICBC" style=" padding-top:3px; padding-bottom:3px; margin-left:10px" title="工商银行"></div>
                </label>
            </li>
            <li>
                <label class="from">
                    <input type="radio" name="bankAbbr" value="CCB" style="margin-top:10px;float:left; height:13px;"  />
                    <div class="iw CCB" style=" padding-top:3px; padding-bottom:3px; margin-left:10px" title="建设银行"></div>
                </label>
            </li>
            <li>
                <label class="from">
                    <input type="radio" name="bankAbbr" value="ABC" style="margin-top:10px;float:left; height:13px;"  />
                    <div class="iw ABC" style=" padding-top:3px; padding-bottom:3px; margin-left:10px" title="农业银行"></div>
                </label>
            </li>
            <li>
                <label class="from">
                    <input type="radio" name="bankAbbr" value="CMB" style="margin-top:10px;float:left; height:13px;"  />
                    <div class="iw CMBCHINA" style=" padding-top:3px; padding-bottom:3px; margin-left:10px" title="招商银行"></div>
                </label>
            </li>
            <li>
                <label class="from">
                    <input type="radio" name="bankAbbr" value="BOC" style="margin-top:10px;float:left; height:13px;"  />
                    <div class="iw BOC" style=" padding-top:3px; padding-bottom:3px; margin-left:10px" title="中国银行"></div>
                </label>
            </li>
            <li>
                <label class="from">
                    <input type="radio" name="bankAbbr" value="COMM" style="margin-top:10px;float:left; height:13px;"  />
                    <div class="iw BOCO" style=" padding-top:3px; padding-bottom:3px; margin-left:10px" title="交通银行"></div>
                </label>
            </li>
            <li>
                <label class="from">
                    <input type="radio" name="bankAbbr" value="PSBC" style="margin-top:10px;float:left; height:13px;"  />
                    <div class="iw POST" style=" padding-top:3px; padding-bottom:3px; margin-left:10px" title="中国邮政储蓄"></div>
                </label>
            </li>
            <li>
                <label class="from">
                    <input type="radio" name="bankAbbr" value="CEB" style="margin-top:10px;float:left; height:13px;"  />
                    <div class="iw CEB" style=" padding-top:3px; padding-bottom:3px; margin-left:10px" title="光大银行"></div>
                </label>
            </li>
            <li>
                <label class="from">
                    <input type="radio" name="bankAbbr" value="GDB" style="margin-top:10px;float:left; height:13px;"  />
                    <div class="iw GDB" style=" padding-top:3px; padding-bottom:3px; margin-left:10px" title="广东发展银行"></div>
                </label>
            </li>
            <li>
                <label class="from">
                    <input type="radio" name="bankAbbr" value="CIB" style="margin-top:10px;float:left; height:13px;"  />
                    <div class="iw CIB" style=" padding-top:3px; padding-bottom:3px; margin-left:10px" title="兴业银行"></div>
                </label>
            </li>
            <li>
                <label class="from">
                    <input type="radio" name="bankAbbr" value="SPDB" style="margin-top:10px;float:left; height:13px;"  />
                    <div class="iw SPDB" style=" padding-top:3px; padding-bottom:3px; margin-left:10px" title="上海浦东发展银行"></div>
                </label>
            </li>
            <li>
                <label class="from">
                    <input type="radio" name="bankAbbr" value="CMBC" style="margin-top:10px;float:left; height:13px;"  />
                    <div class="iw CMBC" style=" padding-top:3px; padding-bottom:3px; margin-left:10px" title="民生银行"></div>
                </label>
            </li>
            <li>
                <label class="from">
                    <input type="radio" name="bankAbbr" value="CITIC" style="margin-top:10px;float:left; height:13px;"  />
                    <div class="iw ECITIC" style=" padding-top:3px; padding-bottom:3px; margin-left:10px" title="中信银行"></div>
                </label>
            </li>
            <li>
                <label class="from">
                    <input type="radio" name="bankAbbr" value="SZPAB" style="margin-top:10px;float:left; height:13px;"  />
                    <div class="iw PINGANBANK" style=" padding-top:3px; padding-bottom:3px; margin-left:10px" title="平安银行"></div>
                </label>
            </li>
            <li>
                <label class="from">
                    <input type="radio" name="bankAbbr" value="BOS" style="margin-top:10px;float:left; height:13px;"  />
                    <div class="iw SHB" style=" padding-top:3px; padding-bottom:3px; margin-left:10px" title="上海银行"></div>
                </label>
            </li>
            <li>
                <label class="from">
                    <input type="radio" name="bankAbbr" value="SHRCB" style="margin-top:10px;float:left; height:13px;"  />
                    <div class="iw SRCB" style=" padding-top:3px; padding-bottom:3px; margin-left:10px" title="上海农村商业银行"></div>
                </label>
            </li>
            <li>
                <label class="from">
                    <input type="radio" name="bankAbbr" value="BJRCB" style="margin-top:10px;float:left; height:13px;"  />
                    <div class="iw BCCB" style=" padding-top:3px; padding-bottom:3px; margin-left:10px" title="北京农商银行"></div>
                </label>
            </li>
            <li>
                <label class="from">
                    <input type="radio" name="bankAbbr" value="CBHB" style="margin-top:10px;float:left; height:13px;"  />
                    <div class="iw CBHB" style=" padding-top:3px; padding-bottom:3px; margin-left:10px" title="渤海银行"></div>
                </label>
            </li>
            <li>
                <label class="from">
                    <input type="radio" name="bankAbbr" value="NJCB" style="margin-top:10px;float:left; height:13px;"  />
                    <div class="iw NJCB" style=" padding-top:3px; padding-bottom:3px; margin-left:10px" title="南京银行"></div>
                </label>
            </li>
            <li>
                <label class="from">
                    <input type="radio" name="bankAbbr" value="HXB" style="margin-top:10px;float:left; height:13px;"  />
                    <div class="iw HXB" style=" padding-top:3px; padding-bottom:3px; margin-left:10px" title="华夏银行"></div>
                </label>
            </li>

        </ul>
    </div>
</div>
<!--立即支付-->
  
<div class="w1080 immediate-pay12" style="border-radius: 1em; padding-top:1em; padding-bottom: 1em;padding-right: 1em;">
    <div class="immediate-pay12-right">
        <span>需支付：<strong><?php echo $pay_amount;?></strong>元</span>

        <button type="submit" class="immediate_pay" >立即支付</button>
    </div>
</div>
<div class="mt_agree">
    <div class="mt_agree_main">
        <h2>提示信息</h2>
        <p id="errorContent" style="text-align:center;line-height:36px;"></p>
        <a class="close_btn" onclick="message_hide()">确定</a>
    </div>
</div>
<!--底部-->
<div class="w1080 footer12">
    <p><?php echo "Copyright © 2017-" . date("Y") . " "; ?> 聚合支付 版权所有</p>
	   

</div>


<script type="text/javascript">
    function message_show(message) {
        $("#errorContent").html(message);
        $('.mt_agree').fadeIn(300);
    }

    function message_hide() {
        $('.mt_agree').fadeOut(300);
    }

</script>
  <script type="text/javascript">
    function message_show(message) {
        $("#errorContent").html(message);
        $('.mt_agree').fadeIn(300);
    }

    function message_hide() {
        $('.mt_agree').fadeOut(300);
    }

    //银行显示 赋默认值   表示选中网银支付
    $("#wz").click(function () {
        $("#select").val('select');
        $(".w1080").show();
    });
    //其他的支付方式  隐藏银行编号以及赋的默认值清空
    $("#kj").click(function () {
        $("#select").val('');
        $(".si_PayMethod12").hide();
    });
    $("#zfb").click(function () {
        $("#select").val('');
        $(".si_PayMethod12").hide();
    });
    $("#qq").click(function () {
        $("#select").val('');
        $(".si_PayMethod12").hide();
    });
    $("#baidu").click(function () {
        $("#select").val('');
        $(".si_PayMethod12").hide();
    });
    $("#wx").click(function () {
        $("#select").val('');
        $(".si_PayMethod12").hide();
    });
    $("#zfbh5").click(function () {
        $("#select").val('');
        $(".si_PayMethod12").hide();
    });
    $("#wxh5").click(function () {
        $("#select").val('');
        $(".si_PayMethod12").hide();
    });
    $("#qqh5").click(function () {
        $("#select").val('');
        $(".si_PayMethod12").hide();
    });

    //这个方法只是用来做个掩护!
    function mySubmit(flag){
        return flag;
    }

</script>
</form>

</body>
</html>