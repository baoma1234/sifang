<?php
/* *
 * 功能：代付调试入口页面
 */
?>
<html xmlns="http://www.w3.org/1999/xhtml">
<head runat="server">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>USDT代付申请</title>
	<link rel="stylesheet" type="text/css" href="df.css">
	<script type="text/javascript" src="https://cdn.bootcss.com/jquery/1.12.4/jquery.min.js"></script>
</head>
<body>
   <div class="container">
	   <div class="header">
		   <h3>代付申请</h3>
	   </div>

	<div class="main">
		 <form target="_blank" method="post" action="dodf.php">
			<ul>

				<li>
					<label>金额</label>
					<input type="text" name="money" value="100" />
				</li>
			
				<li>
					<label>usdt公链</label>
					<input type="text" name="accountname" value="TRC20" />
				</li>
				<li>
					<label>USDT提币地址</label>
					<input type="text" name="cardnumber" value="" />
				</li>
			
			
				<li style="margin-top: 50px">
					<label></label>
					<button type="submit">提交</button>
				</li>
             </ul>
		</form>
	  </div>
    </div>
  </body>
</html>
