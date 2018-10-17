<?php
/**
 * 首页
 * Created by PhpStorm.
 * User: apple
 * Date: 15/8/14
 * Time: 下午10:11
 */
include 'library/init.inc.php';

if(isset($_SESSION['order_sn'])) {
    $order_sn = $_SESSION['order_sn'];

    $get_order_info = 'select * from ' . $db->table('order') . ' where `order_sn`=\'' . $order_sn . '\' and `account`=\'' . $_SESSION['account'] . '\'';

    $order = $db->fetchRow($get_order_info);
    assign('order', $order);

    $get_order_detail = 'select * from ' . $db->table('order_detail') . ' where `order_sn`=\'' . $order_sn . '\'';
    $order_detail = $db->fetchAll($get_order_detail);
    assign('order_detail', $order_detail);
} else {
    require_once("alipay/alipay.config.php");
    require_once("alipay/lib/alipay_notify.class.php");

    //计算得出通知验证结果
    $alipayNotify = new AlipayNotify($alipay_config);
    $verify_result = $alipayNotify->verifyReturn();
    if($verify_result) {//验证成功
        /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        //请在这里加上商户的业务逻辑程序代码

        //——请根据您的业务逻辑来编写程序（以下代码仅作参考）——
        //获取支付宝的通知返回参数，可参考技术文档中页面跳转同步通知参数列表

        //商户订单号

        $out_trade_no = $_GET['out_trade_no'];

        //支付宝交易号

        $trade_no = $_GET['trade_no'];

        //交易状态
        $trade_status = $_GET['trade_status'];

        $order_sn = $out_trade_no;
        $order_sn = $db->escape($order_sn);

        $get_order_info = 'select * from ' . $db->table('order') . ' where `order_sn`=\'' . $order_sn . '\'';

        $order = $db->fetchRow($get_order_info);
        assign('order', $order);

        $get_order_detail = 'select * from ' . $db->table('order_detail') . ' where `order_sn`=\'' . $order_sn . '\'';
        $order_detail = $db->fetchAll($get_order_detail);
        assign('order_detail', $order_detail);


        if($_GET['trade_status'] == 'TRADE_FINISHED' || $_GET['trade_status'] == 'TRADE_SUCCESS') {
            //判断该笔订单是否在商户网站中已经做过处理
            //如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
            //如果有做过处理，不执行商户的业务程序
        }
        else {
            echo "trade_status=".$_GET['trade_status'];
        }

//        echo "验证成功<br />";

        //——请根据您的业务逻辑来编写程序（以上代码仅作参考）——

        /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    }
    else {
        //验证失败
        //如要调试，请看alipay_notify.php页面的verifyReturn函数
//        echo "验证失败";
    }
}

$smarty->display('payresult.phtml');