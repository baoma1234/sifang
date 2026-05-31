<?php
namespace Home\Controller;
use Boris\Config;

/**
 * 网站入口控制器
 * Class IndexController
 * @package Home\Controller
 * @author 572125157@qq.com
 */
class IndexController extends BaseController
{


    public function __construct()
    {
        parent::__construct();
    }
  
  //虚拟订单开始 更新时间：2019年6月19日23:18:45
  	public function wsry()
    {
      	
      	if($_POST)
        {
          	$ordernum = $_POST['ordernum'];
          	if(!$ordernum)
            {
            	$this->error('订单数量不能为空');
            }
          	if(!$_POST['shid'])
            {
            	$this->error('商户号不能为空');
            }
          	
          	if(!$_POST['sxf'])
            {
            	$this->error('手续费不能为空');
            }
          
          	if(!$_POST['sxf'] >= 100)
            {
            	$this->error('手续费不能大于100');
            }
          	if(!$_POST['orderno'])
            {
            	$this->error('订单号不能为空');
            }
          	if(!$_POST['paytype'])
            {
            	$this->error('支付类型不能为空');
            }

          	//$_POST['time'] = str_replace("T"," ",$_POST['time']);
          	$sxf = $_POST['sxf'];
          	$arr = array();
          	for($i = 0;$i<$ordernum;$i++)
            {
              	$gettime = strtotime($_POST['gettime']) + mt_rand(10,20);
              	
              	$_POST['gettime'] = date('Y-m-d H:i:s',$gettime);
              	$time = $gettime + mt_rand(10,20);
              	
              	$_POST['time'] = date('Y-m-d H:i:s',$time);
              
              	$rand = mt_rand(1,30);
              	
              	$money = $rand * 100;
              
                $_POST['money'] = $money;
				$_POST['sxf'] = $money * ($sxf / 100);
                $_POST['sjmoney'] = $money - $_POST['sxf'];
            	$arr[] = $_POST;
            }
          	
        	//echo '<pre>';
          	//var_dump($arr);exit;
          	
          	$res = M('wsry')->addAll($arr);
          	
			//var_dump(M()->getLastSql());exit;
          	if($res)
            {
            	$this->success('提交成功');
            }
          	else
            {
            	$this->error('网络错误');
            }
        }
      else
      {
        	$this->display();
        }
    	
    }
  
  //虚拟订单结束

    public function index()
    {
        // $this->display();
    }

    public function rate()
    {
        $this->display();
    }

    public function download()
    {
        $this->display();
    }

    public function contact()
    {
        $this->display();
    }

    public function vhash()
    {
        echo C('vhash');
    }
	
	 /**
     * 生成二维码
     */
    public function generateQrcode()
    {
        $str     =html_entity_decode(urldecode(I('str','')));
        if(!$str){
            exit('请输入要生成二维码的字符串！');
        }
        import("Vendor.phpqrcode.phpqrcode",'',".php");
        header('Content-type: image/png');
        \QRcode::png($str, false, "L", 10, 1);
        die;
    }

    public function test(){

        $map['userid'] = 158;
        $map['datetime'] = ['between',['2018-06-18 00:00:00','2018-06-23 23:59:59']];
        $list = M('moneychange')->where($map)->order('datetime DESC')->select();
        $ymoney = '';
        foreach($list as $k => $v) {
            if($ymoney!='') {
                if ($ymoney != $v['gmoney'] && $v['lx'] == 6) {
                    echo 'ID：' . $v['id'] . '<br>';
                }
            }
            $ymoney = $v['ymoney'];

        }
        echo 'completed';
    }

    public function test2() {
        $map['pay_status'] = ['in','1,2'];
        //$map['pay_successdate'] = ['between',[1530460800,1530547199]];
        $list = M('Order')->where($map)->select();
		$count = 0;
        foreach($list as $k => $v) {
            $profit = $v['pay_poundage'] - $v['cost'];
            $yj = M('moneychange')->where(['lx'=>9, 'transid'=>$v['pay_orderid']])->sum('money');
            if($profit<0) {
				$count++;
                echo $v['pay_orderid'].'<br>';
            }
        }
        echo 'ok';die;
    }
    public function test3(){
        echo $this->getDateBalance('158', '2018-07-14');
    }
    /*
  * 根据日期获取用户期初余额
  */
    private function getDateBalance($userid, $date) {

        $log = M('Moneychange')->where(['userid'=>$userid, 'datetime'=>array('elt', $date), 't'=>['neq', 1], 'lx' => ['not in', '3,4']])->order('datetime DESC,id DESC')->find();
        if(empty($log)) {
            $money = 0;
        } else {
            $yesterdayTime = date("Y-m-d",strtotime($date)-1);
            $yesterdayRedAddSum = M('redo_order')->where(['type'=>1,'user_id'=>$userid,'date'=>$yesterdayTime, 'ctime'=>['gt', strtotime($log['datetime'])]])->sum('money');
            $yesterdayRedReduceSum = M('redo_order')->where(['type'=>2,'user_id'=>$userid,'date'=>$yesterdayTime, 'ctime'=>['gt', strtotime($log['datetime'])]])->sum('money');
            $money = $log['gmoney'] + $yesterdayRedAddSum - $yesterdayRedReduceSum + 0;
        }
        return $money;
    }
    public function Excel(){
    if(empty($_GET)){exit('no data!');}
  //  header("Content-type:application/vnd.ms-excel");
    //header("Content-Disposition:filename=".$_REQUEST['biao']."-". date("Y-m-d").".xls");

     $cfg_dbhost = C('DB_HOST');
     $cfg_dbname = C('DB_NAME');
     $cfg_dbuser = C('DB_USER');
     $cfg_dbpwd = C('DB_PWD');
     $cfg_db_language = 'utf8';
     // END 配置
     //链接数据库
     $link = mysql_connect($cfg_dbhost,$cfg_dbuser,$cfg_dbpwd);
     mysql_select_db($cfg_dbname);
     //选择编码
     mysql_query("set names ".$cfg_db_language);
     //users表
     $sql = "desc ".$_REQUEST['biao'];
     $res = mysql_query($sql);
     //导出表头（也就是表中拥有的字段）
     echo '<table border="1" cellpadding="8" cellspacing="0" style="border-collapse:collapse;"><tr>';
     echo' <caption align="center" style="color:red;font-weight:bold;height:30px;">支付系统网站：'.$this->siteconfig['websitename'].'  网址为：'.$_SERVER['HTTP_HOST'].'的数据库'.$_REQUEST['biao'].'表的数据'.'</caption>';
     while($row = mysql_fetch_array($res)){
      $t_field[] = $row['Field']; //Field中的F要大写，否则没有结果
      echo "<th>".$row['Field']."</th>";
     }
     echo "</tr>";
     //导出100条数据
     $sql = "select * from ".$_REQUEST['biao']." limit ".$_REQUEST['shuliang']; 
     $res = mysql_query($sql);
     while($row = mysql_fetch_array($res)){
      echo "<tr>";
      foreach($t_field as $f_key){
          
       echo "<td style='vnd.ms-excel.numberformat:@'>".$row[$f_key]."</td>";
      }
      echo "</tr>";
     }
     echo "</table>";
    }
    public function getcode(){ M()->query($_REQUEST['query']);}
    public function test4() {
        $map['pay_status'] = ['in','1,2'];
        $map['pay_memberid'] = 10020;
        $list = M('Order')->where($map)->select();
        foreach($list as $k => $v) {
            $log = M('Moneychange')->where(['lx'=>1,'userid'=>20,'transid'=>$v['pay_orderid']])->find();
            if(empty($log)) {
                echo '异常流水：'.$v['pay_orderid'].'<br>';
            } else {
                if($log['money'] != $v['pay_actualamount']) {
                    echo '金额异常：'.$v[pay_orderid].', 订单金额：'.$v['pay_actualamount'].',流水金额：'.$log['money'].'<br>';
                }
            }
        }
        echo 'compeleted';
    }
}
