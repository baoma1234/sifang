<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">
  <title>转跳提示 - <{$sitename}></title>
  <!--[if lt IE 9]>
  <meta http-equiv="refresh" content="0;ie.html" />
  <![endif]-->
    <link rel="icon" href="Public/qq572125157/images/favicon.ico">
<!-- Bootstrap 4.0-->
	<link rel="stylesheet" href="Public/qq572125157/css/assets/vendor_components/bootstrap/dist/css/bootstrap.css">
	<!-- Data Table-->
	<link rel="stylesheet" type="text/css" href="Public/qq572125157/css/assets/vendor_components/datatable/datatables.min.css"/>	
	<!-- Bootstrap extend-->
	<link rel="stylesheet" href="Public/qq572125157/css/bootstrap-extend.css">	
	<!-- theme style -->
	<link rel="stylesheet" href="Public/qq572125157/css/master_style.css">
	<!-- Fab Admin skins -->
	<link rel="stylesheet" href="Public/qq572125157/css/skins/_all-skins.css">	
	<!-- Morris charts -->
	<link rel="stylesheet" href="Public/qq572125157/css/assets/vendor_components/morris.js/morris.css">
   <link rel="stylesheet" href="/Public/Front/js/plugins/layui/css/layui.css">
    <script src="/Public/Front/js/jquery.min.js"></script>
    <script type="text/javascript">
        $(function(){
            var height2=$('.system-message').height();
            var height1=$(window).height();
            $('.system-message').css('margin-top',((height1-height2)/2)-30);
        });
    </script>
	<!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
	<!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
	<!--[if lt IE 9]>
	<script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
	<script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
	<![endif]-->
</head>
<body class="hold-transition skin-blue-light sidebar-mini">
<div class="wrapper">
<link rel="stylesheet" href="/Public/qq572125157/assets/vendor_components/bootstrap/dist/css/bootstrap.min.css">	
<!-- Bootstrap extend-->
<link rel="stylesheet" href="/Public/qq572125157/css/bootstrap-extend.css">
<!-- Theme style -->
<link rel="stylesheet" href="/Public/qq572125157/css/master_style.css">
<!-- Fab Admin skins -->
<link rel="stylesheet" href="/Public/qq572125157/css/skins/_all-skins.css">
<section class="bg-img h-p100" style="background-image: url(/Public/qq572125157/images/photo1.jpg);">
		<div class="container h-p100">
		  <div class="row h-p100 align-items-center justify-content-center">
			<div class="col-lg-8 col-11">		  
			  <div class="error-page bg-white rounded">
				<div class="error-content">
					<div class="row h-p100 align-items-center bg-bubbles-dark bg-danger">
						<div class="col-lg-6 col-md-12 bg-white text-dark">
							<div class="p-30">		
                         <?php if(isset($message)) {?>
								<h1 class="text-danger font-size-40 font-weight-700">系统提示：</h1>
                            
								<h2><?php echo(strip_tags($message)); ?></h2>
                              	<div class="col-lg-6 col-md-12">
						 	<div class="error-content-inner text-center p-15">
							  <h1 class="margin-top-0 headline"><i class="fa fa-warning text-white"></i></h1>
                              等待时间： <b id="wait"><?php echo($waitSecond); ?></b>
                              <h6> <a id="href" href="<?php echo($jumpUrl); ?>" class="btn btn-info btn-block margin-top-10">转跳中请稍等</a></h6>  </div>  </div>
                          
                               <?php }else{?>
                              <h1 class="text-danger font-size-40 font-weight-700">系统提示：</h1>
                            
								<h2><?php echo(strip_tags($error)); ?></h2>
                              <div class="col-lg-6 col-md-12">
						 	<div class="error-content-inner text-center p-15">
							  <h1 class="margin-top-0 headline"><i class="fa fa-warning text-white"></i></h1>
                              等待时间： <b id="wait"><?php echo($waitSecond); ?></b>
                              <h6> <a id="href" href="<?php echo($jumpUrl); ?>" class="btn btn-info btn-block margin-top-10">转跳中请稍等</a></h6>  </div>  </div>

							
								 <?php }?>
                              
                              
                              </div>
						</div>
                              <div class="col-lg-6 col-md-12">
						 	<div class="error-content-inner text-center p-15">
							  <h1 class="margin-top-0 headline"><i class="fa fa-warning text-white"></i></h1>
							  <h2> 友情提示： 耐心等待</h2>
                              <h6> 页面自动转跳!</h6>
							</div>
						</div>
                              
							</div>
						</div>
                     
					
                      
					</div>
				</div>
				<!-- /.error-content -->
			  </div>
                </div>

                   </div> </div>
	</section>
<script type="text/javascript">
    (function(){
        var wait = document.getElementById('wait'),href = document.getElementById('href').href;
        var interval = setInterval(function(){
            var time = --wait.innerHTML;
            if(time <= 0) {
                location.href = href;
                clearInterval(interval);
            };
        }, 1000);
    })();
</script>
</body>
</html>