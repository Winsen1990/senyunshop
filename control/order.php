<?php
/**
 * 订单管理
 * @author 王仁欢
 * @email wrh4285@163.com
 * @date 2015-10-09
 * @version 1.0.0
 */

include 'library/init.inc.php';
back_base_init();

$template = 'order/';
assign('subTitle', '订单管理');

$action = 'view|detail|export';
$operation = 'express_info';

$act = check_action($action, getGET('act'));
$act = ( $act == '' ) ? 'view' : $act;

$opera = check_action($operation, getPOST('opera'));
//===========================================================================
if('express_info' == $opera)
{
    $order_sn = getPOST('order_sn');

    $response = array('error'=>1, 'msg'=>'');

    if($order_sn == '')
    {
        $response['msg'] = '参数错误';
    } else {
        $order_sn = $db->escape($order_sn);
    }

    $get_order_info = 'select `express_id`,`status`,`express_sn` from '.$db->table('order').' where `order_sn`=\''.$order_sn.'\'';
    $order = $db->fetchRow($get_order_info);

    if($order && $order['status'] == 6)
    {
        $get_express_code = 'select `code` from '.$db->table('express').' where `id`='.$order['express_id'];
        $express_info = query_express($db->fetchOne($get_express_code   ), $order['express_sn']);
        $express_info = json_decode($express_info, true);
        assign('order_info', $express_info);
        $response['error'] = 0;
        $response['msg'] = $smarty->fetch('public/express_info.phtml');
    } else {
        $response['msg'] = '当前没有任何信息';
    }

    echo json_encode($response);
    exit;
}
//===========================================================================
//导出数据
if( 'export' == $act ) {
    if( !check_purview('pur_order_view', $_SESSION['purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }

    $status_str = array(
        1 => '待支付',
        2 => '支付中',
        3 => '支付完成',
        4 => '待发货',
        5 => '配货中',
        6 => '已发货',
        7 => '已收货',
        8 => '申请退单',
        9 => '退单中',
        10 => '已退单',
        11 => '无效订单',
        12 => '已完成',
    );

    $status = intval(getGET('status'));
    if( 0 >= $status || 12 < $status || 3 == $status || 2 == $status ) {
        assign('status', 0);
        assign('order_status', '');
        $and_where = '';
    } else {
        assign('status', $status);
        assign('order_status', $status_str[$status]);
        $and_where = ' and status = '.$status;
    }

    $st = trim(getGET('st'));
    $et = trim(getGET('et'));
    $start_time = strtotime($st);
    $end_time = strtotime($et);

    $pattern = '#[0-9]{4}\-[0-9]{1,2}\-[0-9]{1,2}#';
    if( $st ) {
        if( preg_match($pattern, $st) ) {
            $and_where .= ' and add_time > ' . $start_time;
        } else {
            $st = '';
        }
    }
    if( $et ) {
        if( preg_match($pattern, $et) ) {
            $and_where .= ' and add_time < ' . ($end_time + 3600 * 24);
        } else {
            $st = '';
        }
    }

    //分页参数
    $page = intval(getGET('page'));
    $count = intval(getGET('count'));
    //获取总数
    $get_total = 'select count(*) from '.$db->table('order');
    $get_total .= ' where 1 ';
    $get_total .= $and_where;
//    $get_total .= ' and is_virtual = 0';    //实体产品
    $total = $db->fetchOne($get_total);

    $page = ( $page > $total ) ? $total : $page;
    $page = ( $page <= 0 ) ? 1 : $page;
    $count = ( $count <= 0 ) ? 10 : $count;
    $offset = ( $page - 1 ) * $count;
    $total_page = ceil( $total / $count );

    $get_order_list = 'select a.*, e.name as express_name, p.province_name, city.city_name, d.district_name, g.group_name from '.$db->table('order').' as a';
    $get_order_list .= ' left join '.$db->table('province').' as p on a.province = p.id';
    $get_order_list .= ' left join '.$db->table('city').' as city on a.city = city.id';
    $get_order_list .= ' left join '.$db->table('district').' as d on a.district = d.id';
    $get_order_list .= ' left join '.$db->table('express').' as e on a.express_id = e.id';
    $get_order_list .= ' left join '.$db->table('group').' as g on a.group = g.id';

    $get_order_list .= ' where 1 ';
    $get_order_list .= $and_where;
//    $get_order_list .= ' and a.is_virtual = 0';
    $get_order_list .= ' order by add_time desc';
    $order_list = $db->fetchAll($get_order_list);

//echo $get_order_list;exit;
    if( $order_list ) {
        foreach ($order_list as $key => $order) {
            $order_list[$key]['add_time_str'] = $order['add_time'] ? date('Y-m-d H:i:s', $order['add_time']) : '';
            $order_list[$key]['delivery_time_str'] = $order['delivery_time'] ? date('Y-m-d H:i:s', $order['delivery_time']) : '未发货';
            $order_list[$key]['receive_time_str'] = $order['receive_time'] ? date('Y-m-d H:i:s', $order['receive_time']) : '未收货';
            $order_list[$key]['pay_time_str'] = $order['pay_time'] ? date('Y-m-d H:i:s', $order['pay_time']) : '未支付';
            $order_list[$key]['status_str'] = $status_str[$order['status']];
        }
    }
    //导出
    if (PHP_SAPI == 'cli')
        die('This example should only be run from a Web Browser');

    /** Include PHPExcel */
    require_once ROOT_PATH.'/plugins/PHPExcel/Classes/PHPExcel.php';


    // Create new PHPExcel object
    $objPHPExcel = new PHPExcel();

    // Set document properties
    $objPHPExcel->getProperties()->setCreator($_SESSION['account'])
        ->setLastModifiedBy($_SESSION['account'])
        ->setTitle("Office 2007 XLSX Test Document")
        ->setSubject("Office 2007 XLSX Test Document")
        ->setDescription("订单")
        ->setKeywords("office 2007 openxml php")
        ->setCategory("订单");


    $objPHPExcel->setActiveSheetIndex(0)->getColumnDimension('A')->setWidth(15);
    $objPHPExcel->setActiveSheetIndex(0)->getColumnDimension('B')->setWidth(15);
    $objPHPExcel->setActiveSheetIndex(0)->getColumnDimension('C')->setWidth(15);
    $objPHPExcel->setActiveSheetIndex(0)->getColumnDimension('D')->setWidth(15);
    $objPHPExcel->setActiveSheetIndex(0)->getColumnDimension('E')->setWidth(15);
    $objPHPExcel->setActiveSheetIndex(0)->getColumnDimension('F')->setWidth(15);
    $objPHPExcel->setActiveSheetIndex(0)->getColumnDimension('G')->setWidth(15);
    $objPHPExcel->setActiveSheetIndex(0)->getColumnDimension('H')->setWidth(15);
    $objPHPExcel->setActiveSheetIndex(0)->getColumnDimension('I')->setWidth(15);
    $objPHPExcel->setActiveSheetIndex(0)->getColumnDimension('J')->setWidth(15);
    $objPHPExcel->setActiveSheetIndex(0)->getColumnDimension('K')->setWidth(15);
    $objPHPExcel->setActiveSheetIndex(0)->getColumnDimension('L')->setWidth(15);

    $objPHPExcel->setActiveSheetIndex(0)->getStyle()->getFont()->setSize(15);
    $objPHPExcel->getActiveSheet()->getDefaultRowDimension()->setRowHeight(30);


    // add data
    $i = 0;
    foreach( $order_list as $key => $order ) {

        $i++;
        //设置填充的样式和背景色
        $objPHPExcel->getActiveSheet()->getStyle( 'A'.$i.':L'.$i)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
        $objPHPExcel->getActiveSheet()->getStyle( 'A'.$i.':L'.$i)->getFill()->getStartColor()->setARGB('FF808080');

        $i++;
        $temp = $order['province_name'].$order['city_name'].$order['district_name'].$order['group_name'].'    ';
        $temp .= $order['address'].'    ';
        $order['self_delivery_str'] = $order['self_delivery'] == 0 ? '否' : '是';
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A'.$i, '订单编号');
        $objPHPExcel->setActiveSheetIndex(0)->mergeCells('B'.$i.':C'.$i);
        $objPHPExcel->setActiveSheetIndex(0)->getStyle('B'.$i)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_TEXT);
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueExplicit('B'.$i, $order['order_sn'], PHPExcel_Cell_DataType::TYPE_STRING);
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('D'.$i, '订单状态');
        $objPHPExcel->setActiveSheetIndex(0)->mergeCells('E'.$i.':F'.$i);
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('E'.$i, $order['status_str']);
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('G'.$i, '下单时间');
        $objPHPExcel->setActiveSheetIndex(0)->mergeCells('H'.$i.':I'.$i);
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('H'.$i, $order['add_time_str']);
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('J'.$i, '发货时间');
        $objPHPExcel->setActiveSheetIndex(0)->mergeCells('K'.$i.':L'.$i);
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('K'.$i, $order['delivery_time_str']);

        $i++;
        $objPHPExcel->setActiveSheetIndex(0)->mergeCells('A'.$i.':L'.$i);
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A'.$i, '订单详情');

        $i++;
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A'.$i, '产品编号');
        $objPHPExcel->setActiveSheetIndex(0)->mergeCells('B'.$i.':D'.$i);
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('B'.$i, '产品名称及规格');
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('F'.$i, '产品数量');
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('G'.$i, '单价');
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('H'.$i, '产品总额');
        $objPHPExcel->setActiveSheetIndex(0)->mergeCells('I'.$i.':L'.$i);
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('I'.$i, '备注');

        $get_order_detail = 'select * from '.$db->table('order_detail').' where order_sn = \''.$order['order_sn'].'\'';
        $order_detail = $db->fetchAll($get_order_detail);

        if( $order_detail ) {
            foreach ($order_detail as $detail) {
                $product_amount = $detail['count'] * $detail['product_price'];
                $i++;
                $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A' . $i, $detail['product_sn']);
                $objPHPExcel->setActiveSheetIndex(0)->mergeCells('B' . $i . ':D' . $i);
                $objPHPExcel->setActiveSheetIndex(0)->setCellValue('B' . $i, $detail['product_name']);
                $objPHPExcel->setActiveSheetIndex(0)->setCellValue('F' . $i, $detail['count']);
                $objPHPExcel->setActiveSheetIndex(0)->getStyle('G'.$i)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
                $objPHPExcel->setActiveSheetIndex(0)->setCellValue('G' . $i, sprintf('%.2f', $detail['product_price']));
                $objPHPExcel->setActiveSheetIndex(0)->getStyle('H'.$i)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
                $objPHPExcel->setActiveSheetIndex(0)->setCellValue('H' . $i, $product_amount);
                $objPHPExcel->setActiveSheetIndex(0)->mergeCells('I' . $i . ':L' . $i);
                $objPHPExcel->setActiveSheetIndex(0)->setCellValue('I' . $i, $order['remark']);
            }
        }
        $i++;
        $objPHPExcel->setActiveSheetIndex(0)->mergeCells('A'.$i.':F'.$i);
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('G'.$i, '订单运费');
        $objPHPExcel->setActiveSheetIndex(0)->getStyle('H'.$i)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('H'.$i, sprintf('%.2f', $order['delivery_fee']));
        $objPHPExcel->setActiveSheetIndex(0)->mergeCells('I'.$i.':L'.$i);

        $i++;
        $objPHPExcel->setActiveSheetIndex(0)->mergeCells('A'.$i.':F'.$i);
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('G'.$i, '订单总额');
        $objPHPExcel->setActiveSheetIndex(0)->getStyle('H'.$i)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('H'.$i, sprintf('%.2f', $order['product_amount'] + $order['delivery_fee']));
        $objPHPExcel->setActiveSheetIndex(0)->mergeCells('I'.$i.':L'.$i);

        if( 0 == $order['self_delivery'] ) {
            $i++;
            $objPHPExcel->setActiveSheetIndex(0)->mergeCells('A'.$i.':L'.$i);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A'.$i, '物流信息');

            $i++;
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A'.$i, '配送方式');
            $objPHPExcel->setActiveSheetIndex(0)->mergeCells('B'.$i.':C'.$i);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('B'.$i, $order['delivery_name']);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('D'.$i, '物流公司');
            $objPHPExcel->setActiveSheetIndex(0)->mergeCells('E'.$i.':F'.$i);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('E'.$i, $order['express_name']);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('G'.$i, '快递编号');
            $objPHPExcel->setActiveSheetIndex(0)->mergeCells('H'.$i.':I'.$i);
            $objPHPExcel->setActiveSheetIndex(0)->getStyle('H'.$i)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_TEXT);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValueExplicit('H'.$i, $order['express_sn'], PHPExcel_Cell_DataType::TYPE_STRING);

            $i++;
            $objPHPExcel->setActiveSheetIndex(0)->mergeCells('A'.$i.':F'.$i);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A'.$i, '寄件人信息');
            $objPHPExcel->setActiveSheetIndex(0)->mergeCells('G'.$i.':L'.$i);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('G'.$i, '收件人信息');

            $i++;
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A'.$i, '单位名称');
            $objPHPExcel->setActiveSheetIndex(0)->mergeCells('B'.$i.':C'.$i);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('D'.$i, '联系人');
            $objPHPExcel->setActiveSheetIndex(0)->mergeCells('E'.$i.':F'.$i);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('G'.$i, '单位名称');
            $objPHPExcel->setActiveSheetIndex(0)->mergeCells('H'.$i.':I'.$i);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('J'.$i, '联系人');
            $objPHPExcel->setActiveSheetIndex(0)->mergeCells('K'.$i.':L'.$i);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('K'.$i, $order['consignee']);

            $i++;
            $objPHPExcel->setActiveSheetIndex(0)->mergeCells('A'.$i.':A'.($i+1));
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A'.$i, '地址');
            $objPHPExcel->setActiveSheetIndex(0)->mergeCells('B'.$i.':F'.($i+1));

            $objPHPExcel->setActiveSheetIndex(0)->mergeCells('G'.$i.':G'.($i+1));
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('G'.$i, '地址');
            $objPHPExcel->setActiveSheetIndex(0)->mergeCells('H'.$i.':L'.($i+1));
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('H'.$i, $order['province_name'].$order['city_name'].$order['district_name'].$order['group_name'].$order['address']);

            $i += 2;
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A'.$i, '区号');
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('C'.$i, '联系号码');
            $objPHPExcel->setActiveSheetIndex(0)->mergeCells('D'.$i.':F'.$i);

            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('G'.$i, '区号');
            $objPHPExcel->setActiveSheetIndex(0)->getStyle('H'.$i)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_TEXT);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValueExplicit('H'.$i, $order['zipcode'], PHPExcel_Cell_DataType::TYPE_STRING);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('I'.$i, '联系号码');
            $objPHPExcel->setActiveSheetIndex(0)->getStyle('J'.$i)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_TEXT);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValueExplicit('J'.$i, $order['mobile'], PHPExcel_Cell_DataType::TYPE_STRING);
            $objPHPExcel->setActiveSheetIndex(0)->mergeCells('J'.$i.':L'.$i);
        }
        //设置填充的样式和背景色
    }
    $objPHPExcel->getActiveSheet()->setTitle('订单');


// Set active sheet index to the first sheet, so Excel opens this as the first sheet
    $objPHPExcel->setActiveSheetIndex(0);


// Redirect output to a client’s web browser (Excel2007)
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="订单'.date('Ymd', time()).'.xlsx"');
    header('Cache-Control: max-age=0');
// If you're serving to IE 9, then the following may be needed
    header('Cache-Control: max-age=1');

// If you're serving to IE over SSL, then the following may be needed
    header ('Expires: '.gmdate('D, d M Y H:i:s').' GMT'); // Date in the past
    header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
    header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
    header ('Pragma: public'); // HTTP/1.0

    $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
    $objWriter->save('php://output');
    exit;
}

if( 'view' == $act ) {
    if (!check_purview('pur_order_view', $_SESSION['purview'])) {
        show_system_message('权限不足', array());
        exit;
    }
    $status_str = array(
        1 => '待支付',
        2 => '支付中',
        3 => '支付完成',
        4 => '待发货',
        5 => '配货中',
        6 => '已发货',
        7 => '已收货',
        8 => '申请退单',
        9 => '退单中',
        10 => '已退单',
        11 => '无效订单',
        12 => '已完成',
    );

    $status = intval(getGET('status'));
    if( 0 >= $status || 12 < $status || 2 == $status ) {
        assign('status', 0);
        assign('order_status', '');
        $and_where = '';
    } else {
        assign('status', $status);
        assign('order_status', $status_str[$status]);
        $and_where = ' and status = '.$status;
    }

    $st = trim(getGET('st'));
    $et = trim(getGET('et'));
    $start_time = strtotime($st);
    $end_time = strtotime($et);

    $pattern = '#[0-9]{4}\-[0-9]{1,2}\-[0-9]{1,2}#';
    if( $st ) {
        if( preg_match($pattern, $st) ) {
            $and_where .= ' and add_time > ' . $start_time;
        } else {
            $st = '';
        }
    }
    if( $et ) {
        if( preg_match($pattern, $et) ) {
            $and_where .= ' and add_time < ' . ($end_time + 3600 * 24);
        } else {
            $st = '';
        }
    }

    //分页参数
    $page = intval(getGET('page'));
    $count = intval(getGET('count'));
    //获取总数
    $get_total = 'select count(*) from '.$db->table('order');
    $get_total .= ' where 1';
    $get_total .= $and_where;
    $get_total .= ' and is_virtual = 0';
    $total = $db->fetchOne($get_total);

    $page = ( $page > $total ) ? $total : $page;
    $page = ( $page <= 0 ) ? 1 : $page;
    $count = ( $count <= 0 ) ? 10 : $count;
    $offset = ( $page - 1 ) * $count;
    $total_page = ceil( $total / $count );

    $get_order_list = 'select a.*, p.province_name, city.city_name, d.district_name, g.group_name from '.$db->table('order').' as a';
    $get_order_list .= ' left join '.$db->table('province').' as p on a.province = p.id';
    $get_order_list .= ' left join '.$db->table('city').' as city on a.city = city.id';
    $get_order_list .= ' left join '.$db->table('district').' as d on a.district = d.id';
    $get_order_list .= ' left join '.$db->table('group').' as g on a.group = g.id';

    $get_order_list .= ' where 1';
    $get_order_list .= $and_where;
    $get_order_list .= ' and a.is_virtual = 0';
    $get_order_list .= ' order by add_time desc';
    $get_order_list .= ' limit '.$offset.','.$count;
    $order_list = $db->fetchAll($get_order_list);

    if( $order_list ) {
        foreach ($order_list as $key => $order) {
            $order_list[$key]['add_time_str'] = $order['add_time'] ? date('Y-m-d H:i:s', $order['add_time']) : '';
            $order_list[$key]['delivery_time_str'] = $order['delivery_time'] ? date('Y-m-d H:i:s', $order['delivery_time']) : '未发货';
            $order_list[$key]['receive_time_str'] = $order['receive_time'] ? date('Y-m-d H:i:s', $order['receive_time']) : '未收货';
            $order_list[$key]['pay_time_str'] = $order['pay_time'] ? date('Y-m-d H:i:s', $order['pay_time']) : '未支付';
            $order_list[$key]['status_str'] = $status_str[$order['status']];
        }
    }

    assign('order_list', $order_list);
    create_pager($page, $total_page, $total);

    assign('count', $count);
    assign('st', $st);
    assign('et', $et);
}

if( 'detail' == $act ) {
    if (!check_purview('pur_order_view', $_SESSION['purview'])) {
        show_system_message('权限不足', array());
        exit;
    }

    $status_str = array(
        1 => '待支付',
        2 => '支付中',
        3 => '支付完成',
        4 => '待发货',
        5 => '配货中',
        6 => '已发货',
        7 => '已收货',
        8 => '申请退单',
        9 => '退单中',
        10 => '已退单',
        11 => '无效订单',
        12 => '已完成',
    );


    $order_sn = trim(getGET('sn'));

    if( '' == $order_sn ) {
        show_system_message('参数错误', array());
        exit;
    }
    $order_sn = $db->escape($order_sn);

    $get_order = 'select a.*, p.province_name, city.city_name, d.district_name, g.group_name, e.name as express_name from '.$db->table('order').' as a';
    $get_order .= ' left join '.$db->table('province').' as p on a.province = p.id';
    $get_order .= ' left join '.$db->table('city').' as city on a.city = city.id';
    $get_order .= ' left join '.$db->table('district').' as d on a.district = d.id';
    $get_order .= ' left join '.$db->table('group').' as g on a.group = g.id';
    $get_order .= ' left join '.$db->table('express').' as e on a.express_id = e.id';

    $get_order .= ' where 1';
    $get_order .= ' and order_sn = \''.$order_sn.'\'';
    $get_order .= ' and a.is_virtual = 0';
    $get_order .= ' limit 1';

    $order = $db->fetchRow($get_order);

    if( empty($order) ) {
        show_system_message('订单不存在', array());
        exit;
    }

    $order['add_time_str'] = $order['add_time'] ? date('Y-m-d H:i:s', $order['add_time']) : '';
    $order['delivery_time_str'] = $order['delivery_time'] ? date('Y-m-d H:i:s', $order['delivery_time']) : '未发货';
    $order['receive_time_str'] = $order['receive_time'] ? date('Y-m-d H:i:s', $order['receive_time']) : '未收货';
    $order['pay_time_str'] = $order['pay_time'] ? date('Y-m-d H:i:s', $order['pay_time']) : '未支付';
    $order['status_str'] = $status_str[$order['status']];

    $get_order_detail = 'select o.*, p.img from '. $db->table('order_detail').' as o';
    $get_order_detail .= ' left join '.$db->table('product').' as p on o.product_sn = p.product_sn';
    $get_order_detail .= ' where 1';
    $get_order_detail .= ' and o.order_sn = \''.$order_sn.'\'';

    $order_detail = $db->fetchAll($get_order_detail);

    assign('order', $order);
    assign('order_detail', $order_detail);
}

$template .= $act.'.phtml';
$smarty->display($template);
