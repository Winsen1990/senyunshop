<?php
/**
 * Created by PhpStorm.
 * User: Winsen
 * Date: 2018/12/8
 * Time: 7:45 PM
 */
include 'library/init.inc.php';
global $db, $config, $log, $smarty;
business_base_init();

$template = 'analyse/';
assign('subTitle', '数据中心');

$action = 'category_calendar|trade_summary|sale_summary|product_sale_rank|order_detail|order_detail_summary|order_list';
$operation = 'edit|add';

$act = check_action($action, getGET('act'));
$act = ( $act == '' ) ? 'view' : $act;

$opera = check_action($operation, getPOST('opera'));

$order_status = [
    1 => '待支付',
    2 => '支付中',
    3 => '已支付',
    4 => '待发货',
    5 => '配货中',
    6 => '已发货',
    7 => '已收货',
    8 => '申请退单',
    9 => '退单中',
    10 => '已退单',
    11 => '无效订单',
    12 => '已完成'
];

//有效订单状态
$available_order_status = [3, 4, 5, 6, 7, 12];

//分类每日销售汇总
if('category_calendar' == $act) {
    if( !check_purview('pur_data_view', $_SESSION['business_purview']) ) {
        show_system_message('没有操作权限');
    }

    $data_set = [];

    $export = check_action('export|view', getGET('export'), 'view');
    $start_date = trim(getGET('start_date'));
    $end_date = trim(getGET('end_date'));

    if(empty($start_date)) {
        $start_date = strtotime('-7 days, 00:00:00');
    } else {
        $start_date = strtotime($start_date . ' 00:00:00');
    }

    if(empty($end_date)) {
        $end_date = time();
    } else {
        $end_date = strtotime($end_date . ' 23:00:00');
    }

    if($start_date <= 0 || $end_date <= 0) {
        show_system_message('开始/结束日期格式错误');
    }

    if($end_date < $start_date) {
        show_system_message('结束日期不能早于开始日期');
    }

    if($end_date - $start_date > 15 * 86400) {
        show_system_message('最多只能汇总15天的数据');
    }

    $category_id = intval(getGET('category_id'));
    if($category_id < 0) {
        show_system_message('参数错误');
    }

    $categories = [];
    if($category_id > 0) {
        $category = $db->find('category', ['id', 'name', 'parent_id', 'path'], ['id' => $category_id]);
        if (empty($category)) {
            show_system_message('分类不存在');
        }

        array_push($categories, $category);
        if($category['parent_id'] == 0) {
            $sub_categories = $db->all('category', ['id', 'name', 'parent_id', 'path'], ['path' => ['like', $category['path'].'%']], null, ['path']);
            if(!empty($sub_categories)) {
                while($sub_category = array_shift($sub_categories)) {
                    array_push($categories, $sub_category);
                }
            }
        }
    } else {
        $categories = $db->all('category', ['id', 'name', 'parent_id', 'path'], null, null, ['path']);
    }

    if(count($categories) == 0) {
        show_system_message('分类数据为空');
    }

    $calendar = [];
    $_begin = $start_date;
    while($_begin < $end_date) {
        $calendar[date('Y-m-d', $_begin)] = [
            'date' => date('Y-m-d', $_begin),
            'amount' => 0
        ];

        $_begin += 86400;
    }

    while($category = array_shift($categories)) {
        $data_set[$category['id']] = [
            'id' => $category['id'],
            'name' => $category['name'],
            'parent' => $category['parent_id'] == 0,
            'calendar' => $calendar
        ];
    }

    $get_order_details = 'select od.`product_sn`,od.`count`,od.`product_price`,o.`add_time` from '.$db->table('order_detail').
        ' as od left join '.$db->table('order').' as o using(`order_sn`) '.
        'where o.`add_time` between '.$start_date.' and '.$end_date.' and o.`status` in ('.implode(',', $available_order_status).')';

    $order_details = $db->fetchAll($get_order_details);
    $product_category_mapper = [];
    $category_cache = [];
    if(!empty($order_details)) {
        foreach($order_details as &$_order_detail) {
            $_order_detail['add_time'] = date('Y-m-d', $_order_detail['add_time']);
            $_order_detail['amount'] = $_order_detail['product_price'] * $_order_detail['count'];
            if(isset($product_category_mapper[$_order_detail['product_sn']])) {
                continue;
            }

            $product_category_mapper[$_order_detail['product_sn']] = [];
            $product_category_assoc = $db->all('product_category_mapper', ['category_id'], ['product_sn' => $_order_detail['product_sn']]);
            if(!empty($product_category_assoc)) {
                while($assoc = array_shift($product_category_assoc)) {
                    array_push($product_category_mapper[$_order_detail['product_sn']], $assoc['category_id']);

                    if(!isset($category_cache[$assoc['category_id']])) {
                        $parent_id = $db->getColumn('category', 'parent_id', ['id' => $assoc['category_id']]);
                        $category_cache[$assoc['category_id']] = $parent_id;
                    }

                    if($category_cache[$assoc['category_id']] > 0) {
                        array_push($product_category_mapper[$_order_detail['product_sn']], $category_cache[$assoc['category_id']]);
                    }
                }
            }
        }

        while($order_detail = array_shift($order_details)) {
            $product_category_assoc = $product_category_mapper[$order_detail['product_sn']];
            foreach($product_category_assoc as $_category_id) {
                if(!isset($data_set[$_category_id])) {
                    continue;
                }

                $data_set[$_category_id]['calendar'][$order_detail['add_time']]['amount'] += $order_detail['amount'];
            }
        }
    }

    $data_rows = [];
    $data_header = ['分类'];
    $_begin = $start_date;
    while($_begin < $end_date) {
        array_push($data_header, date('Y-m-d', $_begin));
        $_begin += 86400;
    }

    foreach($data_set as $_data) {
        $row = [$_data['name']];
        foreach($_data['calendar'] as $_date_record) {
            array_push($row, $_date_record['amount']);
        }

        array_push($data_rows, $row);
    }

    if($export == 'export') {
        $file_name = '分类销售汇总'.date('YmdHis').'.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="'.$file_name.'"');
        $out = fopen('php://output', 'w');
        fwrite($out, chr(0xEF).chr(0xBB).chr(0xBF));
        fputcsv($out, $data_header);
        foreach($data_rows as $row) {
            fputcsv($out, $row);
        }
        fclose($out);
        exit;
    }

    assign('start_date', date('Y-m-d', $start_date));
    assign('end_date', date('Y-m-d', $end_date));
    assign('category_id', $category_id);
    assign('data_header', $data_header);
    assign('data_rows', $data_rows);

    $categories = $db->all('category', ['id', 'name', 'parent_id']);
    assign('categories', $categories);
}

//交易汇总
if('trade_summary' == $act) {
    if( !check_purview('pur_data_view', $_SESSION['business_purview']) ) {
        show_system_message('没有操作权限');
    }

    $data_set = [];

    $export = check_action('export|view', getGET('export'), 'view');
    $start_date = trim(getGET('start_date'));
    $end_date = trim(getGET('end_date'));

    if(empty($start_date)) {
        $start_date = strtotime('-7 days, 00:00:00');
    } else {
        $start_date = strtotime($start_date . ' 00:00:00');
    }

    if(empty($end_date)) {
        $end_date = time();
    } else {
        $end_date = strtotime($end_date . ' 23:00:00');
    }

    if($start_date <= 0 || $end_date <= 0) {
        show_system_message('开始/结束日期格式错误');
    }

    if($end_date < $start_date) {
        show_system_message('结束日期不能早于开始日期');
    }

    if($end_date - $start_date > 365 * 86400) {
        show_system_message('最多只能汇总1年的数据');
    }

    $time_span = $end_date - $start_date;
    $time_divide_by = 'hour';

    if($time_span <= 86400) {
        $time_divide_by = 'hour'; //按小时汇总
    } else if($time_span <= 61 * 86400) {
        $time_divide_by = 'day'; //按日期汇总
    } else {
        $time_divide_by = 'month'; //按月汇总
    }

    $calendar = [];
    $_begin = $start_date;
    while($_begin < $end_date) {
        switch($time_divide_by) {
            case 'day':
                $calendar[date('Y-m-d', $_begin)] = [
                    'date' => date('Y-m-d', $_begin),
                    'amount' => 0,
                    'avg' => 0,
                    'count' => 0
                ];

                $_begin += 86400;
                break;

            case 'month':
                if(!isset($calendar[date('Y-m', $_begin)])) {
                    $calendar[date('Y-m', $_begin)] = [
                        'date' => date('Y-m', $_begin).'月',
                        'amount' => 0,
                        'avg' => 0,
                        'count' => 0
                    ];
                }

                $_begin += 86400 * 10;
                break;

            case 'hour':
                if(!isset($calendar[date('Y-m-d H', $_begin)])) {
                    $calendar[date('Y-m-d H', $_begin)] = [
                        'date' => date('Y-m-d H', $_begin).'时',
                        'amount' => 0,
                        'avg' => 0,
                        'count' => 0
                    ];
                }

                $_begin += 3600;
                break;
        }
    }

    $total_amount = 0;
    $total_count = 0;
    $total_average = 0;

    $order_list = $db->all('order', ['amount', 'add_time'], ['add_time' => ['between', [$start_date, $end_date]]]);
    if(!empty($order_list)) {
        while ($order = array_shift($order_list)) {
            switch($time_divide_by) {
                case 'hour':
                    $order['date'] = date('Y-m-d H', $order['add_time']);
                    break;

                case 'day':
                    $order['date'] = date('Y-m-d', $order['add_time']);
                    break;

                case 'month':
                    $order['date'] = date('Y-m', $order['add_time']);
                    break;
            }

            $calendar[$order['date']]['amount'] += $order['amount'];
            $calendar[$order['date']]['count'] += 1;
            $calendar[$order['date']]['avg'] = $calendar[$order['date']]['amount']/max(1, $calendar[$order['date']]['count']);

            $total_amount += $order['amount'];
            $total_count += 1;
            $total_average = $total_amount/$total_count;
        }
    }

    $calendar_date = [];
    $amount_list = [];
    $count_list = [];
    $average_list = [];

    foreach($calendar as $_date) {
        array_push($calendar_date, $_date['date']);
        array_push($amount_list, round($_date['amount'], 2));
        array_push($count_list, $_date['count']);
        array_push($average_list, round($_date['avg'], 2));
    }

    if($export == 'export') {
        array_unshift($calendar_date, '时间');
        array_unshift($amount_list, '交易总额');
        array_unshift($count_list, '交易总量');
        array_unshift($average_list, '平均单价');

        $file_name = '交易汇总'.date('YmdHis').'.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="'.$file_name.'"');
        $out = fopen('php://output', 'w');
        fwrite($out, chr(0xEF).chr(0xBB).chr(0xBF));
        fputcsv($out, $calendar_date);
        fputcsv($out, $amount_list);
        fputcsv($out, $count_list);
        fputcsv($out, $average_list);
        fclose($out);
        exit;
    }

    assign('start_date', date('Y-m-d', $start_date));
    assign('end_date', date('Y-m-d', $end_date));
    assign('calendar_date', $calendar_date);
    assign('amount_list', $amount_list);
    assign('count_list', $count_list);
    assign('average_list', $average_list);

    assign('total_amount', $total_amount);
    assign('total_count', $total_count);
    assign('total_average', $total_average);
}

//畅销时间图表
if('sale_summary' == $act) {
    if( !check_purview('pur_data_view', $_SESSION['business_purview']) ) {
        show_system_message('没有操作权限');
    }

    $data_set = [];

    $export = check_action('export|view', getGET('export'), 'view');
    $start_date = trim(getGET('start_date'));
    $end_date = trim(getGET('end_date'));

    if(empty($start_date)) {
        $start_date = strtotime('-24hours');
    } else {
        $start_date = strtotime($start_date);
    }

    if(empty($end_date)) {
        $end_date = time();
    } else {
        $end_date = strtotime($end_date);
    }

    if($start_date <= 0 || $end_date <= 0) {
        show_system_message('开始/结束日期格式错误');
    }

    if($end_date < $start_date) {
        show_system_message('结束日期不能早于开始日期');
    }

    if($end_date - $start_date > 86400) {
        show_system_message('最多只能查看24小时的数据');
    }

    $category_id = intval(getGET('category_id'));
    if($category_id < 0) {
        show_system_message('参数错误');
    }

    $categories = [];
    if($category_id > 0) {
        $category = $db->find('category', ['id', 'name', 'parent_id', 'path'], ['id' => $category_id]);
        if (empty($category)) {
            show_system_message('分类不存在');
        }

        array_push($categories, $category);
        if($category['parent_id'] == 0) {
            $sub_categories = $db->all('category', ['id', 'name', 'parent_id', 'path'], ['path' => ['like', $category['path'].'%']], null, ['path']);
            if(!empty($sub_categories)) {
                while($sub_category = array_shift($sub_categories)) {
                    array_push($categories, $sub_category);
                }
            }
        }
    } else {
        $categories = $db->all('category', ['id', 'name', 'parent_id', 'path'], null, null, ['path']);
    }

    if(count($categories) == 0) {
        show_system_message('分类数据为空');
    }

    $category_id = $categories[0]['id'];
    $calendar = [];
    $_begin = $start_date;
    while($_begin < $end_date) {
        $calendar[date('Y-m-d H', $_begin)] = [
            'date' => date('Y-m-d H', $_begin),
            'amount' => 0,
            'count' => 0
        ];

        $_begin += 3600;
    }

    while($category = array_shift($categories)) {
        $data_set[$category['id']] = [
            'id' => $category['id'],
            'name' => $category['name'],
            'parent' => $category['parent_id'] == 0,
            'calendar' => $calendar
        ];
    }

    $get_order_details = 'select od.`product_sn`,od.`count`,od.`product_price`,o.`add_time` from '.$db->table('order_detail').
        ' as od left join '.$db->table('order').' as o using(`order_sn`) '.
        'where o.`add_time` between '.$start_date.' and '.$end_date.' and o.`status` in ('.implode(',', $available_order_status).')';

    $order_details = $db->fetchAll($get_order_details);
    $product_category_mapper = [];
    $category_cache = [];
    if(!empty($order_details)) {
        foreach($order_details as &$_order_detail) {
            $_order_detail['add_time'] = date('Y-m-d H', $_order_detail['add_time']);
            $_order_detail['amount'] = $_order_detail['product_price'] * $_order_detail['count'];
            if(isset($product_category_mapper[$_order_detail['product_sn']])) {
                continue;
            }

            $product_category_mapper[$_order_detail['product_sn']] = [];
            $product_category_assoc = $db->all('product_category_mapper', ['category_id'], ['product_sn' => $_order_detail['product_sn']]);
            if(!empty($product_category_assoc)) {
                while($assoc = array_shift($product_category_assoc)) {
                    array_push($product_category_mapper[$_order_detail['product_sn']], $assoc['category_id']);

                    if(!isset($category_cache[$assoc['category_id']])) {
                        $parent_id = $db->getColumn('category', 'parent_id', ['id' => $assoc['category_id']]);
                        $category_cache[$assoc['category_id']] = $parent_id;
                    }

                    if($category_cache[$assoc['category_id']] > 0) {
                        array_push($product_category_mapper[$_order_detail['product_sn']], $category_cache[$assoc['category_id']]);
                    }
                }
            }
        }

        while($order_detail = array_shift($order_details)) {
            $product_category_assoc = $product_category_mapper[$order_detail['product_sn']];
            foreach($product_category_assoc as $_category_id) {
                if(!isset($data_set[$_category_id])) {
                    continue;
                }

                $data_set[$_category_id]['calendar'][$order_detail['add_time']]['amount'] += $order_detail['amount'];
                $data_set[$_category_id]['calendar'][$order_detail['add_time']]['count'] += $order_detail['count'];
            }
        }
    }

    $amount_list = [];
    $count_list = [];
    $data_header = [];
    $category_name = '';
    $_begin = $start_date;
    while($_begin < $end_date) {
        array_push($data_header, date('d日H时', $_begin));
        $_begin += 3600;
    }

    foreach($data_set as $_data) {
        $category_name = $_data['name'];
        $category_id = $_data['id'];
        foreach($_data['calendar'] as $_date_record) {
            array_push($amount_list, $_date_record['amount']);
            array_push($count_list, $_date_record['count']);
        }

        break;
    }

    if($export == 'export') {
        array_unshift($data_header, '时间');
        array_unshift($amount_list, '成交额');
        array_unshift($count_list, '成交量');

        $file_name = $category_name.'产品畅销分析'.date('YmdHis').'.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="'.$file_name.'"');
        $out = fopen('php://output', 'w');
        fwrite($out, chr(0xEF).chr(0xBB).chr(0xBF));
        fputcsv($out, $data_header);
        fputcsv($out, $amount_list);
        fputcsv($out, $count_list);
        fclose($out);
        exit;
    }

    assign('start_date', date('Y-m-d H:i:s', $start_date));
    assign('end_date', date('Y-m-d H:i:s', $end_date));
    assign('category_id', $category_id);
    assign('category_name', $category_name);
    assign('data_header', $data_header);
    assign('count_list', $count_list);
    assign('amount_list', $amount_list);

    $categories = $db->all('category', ['id', 'name', 'parent_id']);
    assign('categories', $categories);
}

//产品销量排行榜
if('product_sale_rank' == $act) {
    if( !check_purview('pur_data_view', $_SESSION['business_purview']) ) {
        show_system_message('没有操作权限');
    }

    $data_set = [];

    $export = check_action('export|view', getGET('export'), 'view');
    $start_date = trim(getGET('start_date'));
    $end_date = trim(getGET('end_date'));

    if(empty($start_date)) {
        $start_date = strtotime('-24hours');
    } else {
        $start_date = strtotime($start_date);
    }

    if(empty($end_date)) {
        $end_date = time();
    } else {
        $end_date = strtotime($end_date);
    }

    if($start_date <= 0 || $end_date <= 0) {
        show_system_message('开始/结束日期格式错误');
    }

    if($end_date < $start_date) {
        show_system_message('结束日期不能早于开始日期');
    }

    if($end_date - $start_date > 90 * 86400) {
        show_system_message('最多只能查看3个月的数据');
    }

    $category_id = intval(getGET('category_id'));
    if($category_id < 0) {
        show_system_message('参数错误');
    }

    $rank = [];
    $count_rank = [];
    $categories = [];

    $get_order_details = 'select od.`product_sn`,od.`count`,od.`product_price`,od.`product_name` from '.$db->table('order_detail').
        ' as od left join '.$db->table('order').' as o using(`order_sn`) '.
        'where o.`add_time` between '.$start_date.' and '.$end_date.' and o.`status` in ('.implode(',', $available_order_status).')';

    $order_details = $db->fetchAll($get_order_details);
    $product_category_mapper = [];
    $category_cache = [];
    if(!empty($order_details)) {
        foreach($order_details as &$_order_detail) {
            $_order_detail['amount'] = $_order_detail['product_price'] * $_order_detail['count'];

            if($category_id == 0) {
                $_order_detail['join'] = true;
                continue;
            }

            $_order_detail['join'] = false;

            if(!isset($product_category_mapper[$_order_detail['product_sn']])) {
                $product_category_mapper[$_order_detail['product_sn']] = [];
                $product_category_assoc = $db->all('product_category_mapper', ['category_id'], ['product_sn' => $_order_detail['product_sn']]);
                if (!empty($product_category_assoc)) {
                    while ($assoc = array_shift($product_category_assoc)) {
                        array_push($product_category_mapper[$_order_detail['product_sn']], $assoc['category_id']);

                        if (!isset($category_cache[$assoc['category_id']])) {
                            $parent_id = $db->getColumn('category', 'parent_id', ['id' => $assoc['category_id']]);
                            $category_cache[$assoc['category_id']] = $parent_id;
                        }

                        if ($category_cache[$assoc['category_id']] > 0) {
                            array_push($product_category_mapper[$_order_detail['product_sn']], $category_cache[$assoc['category_id']]);
                        }
                    }
                }
            }

            $product_assoc = $product_category_mapper[$_order_detail['product_sn']];
            foreach($product_assoc as $_category_id) {
                if($_category_id == $category_id) {
                    $_order_detail['join'] = true;
                    break;
                }
            }
        }

        while($order_detail = array_shift($order_details)) {
            if(!$order_detail['join']) {
                continue;
            }

            if(!isset($rank[$order_detail['product_sn']])) {
                $rank[$order_detail['product_sn']] = [
                    'name' => $order_detail['product_name'],
                    'count' => 0,
                    'amount' => 0
                ];
            }

            $rank[$order_detail['product_sn']]['amount'] += $order_detail['amount'];
            $rank[$order_detail['product_sn']]['count'] += $order_detail['count'];
        }

        $rank = array_values($rank);

        usort($rank, function ($a, $b) {
            return $a['count'] < $b['count'] ? 1 : -1;
        });
        $count_rank = $rank;

        usort($rank, function ($a, $b) {
            return $a['amount'] < $b['amount'] ? 1 : -1;
        });
    }

    $category_name = '';

    $rank_header = [];
    $rank_rows = [];
    $count_rank_header = [];
    $count_rank_rows = [];
    $top = 20;
    $top = min(count($rank), $top);

    for($i = 0; $i < $top; $i++) {
        $_rank = $rank[$i];
        array_push($rank_header, $_rank['name']);
        array_push($rank_rows, $_rank['amount']);

        $_count_rank = $count_rank[$i];
        array_push($count_rank_header, $_count_rank['name']);
        array_push($count_rank_rows, $_count_rank['count']);
    }

    if($export == 'export') {
        array_unshift($rank_header, '产品');
        array_unshift($rank_rows, '销售额');
        array_unshift($count_rank_header, '产品');
        array_unshift($count_rank_rows, '销量');

        $file_name = $category_name.'产品销量排行'.date('YmdHis').'.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="'.$file_name.'"');
        $out = fopen('php://output', 'w');
        fwrite($out, chr(0xEF).chr(0xBB).chr(0xBF));
        fputcsv($out, $rank_header);
        fputcsv($out, $rank_rows);
        fputcsv($out, $count_rank_header);
        fputcsv($out, $count_rank_rows);
        fclose($out);
        exit;
    }

    $rank_header = array_reverse($rank_header);
    $rank_rows = array_reverse($rank_rows);
    $count_rank_header = array_reverse($count_rank_header);
    $count_rank_rows = array_reverse($count_rank_rows);

    assign('start_date', date('Y-m-d H:i:s', $start_date));
    assign('end_date', date('Y-m-d H:i:s', $end_date));
    assign('category_id', $category_id);
    assign('category_name', $category_name);
    assign('rank_header', $rank_header);
    assign('rank_rows', $rank_rows);
    assign('count_rank_header', $count_rank_header);
    assign('count_rank_rows', $count_rank_rows);

    $categories = $db->all('category', ['id', 'name', 'parent_id']);
    assign('categories', $categories);
}

//商品交易明细
if('order_detail' == $act) {
    if( !check_purview('pur_data_view', $_SESSION['business_purview']) ) {
        show_system_message('没有操作权限');
    }

    $data_set = [];

    $export = check_action('export|view', getGET('export'), 'view');
    $start_date = trim(getGET('start_date'));
    $end_date = trim(getGET('end_date'));
    $category_id = intval(getGET('category_id'));

    $page = intval(getGET('page'));
    $step = 20;

    $where = ' WHERE 1';

    if(!empty($start_date)) {
        $start_date = strtotime($start_date.' 00:00:00');
    } else {
        $start_date = strtotime('-7 days');
    }

    if(!empty($end_date)) {
        $end_date = strtotime($end_date. ' 23:59:59');
    } else {
        $end_date = time();
    }

    if($start_date > 0 && $end_date > 0) {
        if($end_date < $start_date) {
            show_system_message('结束日期不能早于开始日期');
        }

        $where .= ' AND o.`add_time` between '.$start_date.' and '.$end_date;
    } else if($start_date > 0) {
        $where .= ' AND o.`add_time`>='.$start_date;
    } else if($end_date > 0) {
        $where .= ' AND o.`add_time`<='.$end_date;
    }

    $category_ids = [];
    $category_cache = [];
    if($category_id > 0) {
        $category = $db->find('category', ['id', 'name', 'path'], ['id' => $category_id]);

        if(empty($category)) {
            show_system_message('分类不存在');
        }

        $category_ids[$category['id']] = $category['name'];
        $category_cache[$category['id']] = $category['name'];
        $sub_categories = $db->all('category', ['id', 'name'], ['path' => ['like', $category['path'].'%']]);
        if(!empty($sub_categories)) {
            while($_sub_category = array_shift($sub_categories)) {
                $category_ids[$_sub_category['id']] = $_sub_category['name'];
                $category_cache[$_sub_category['id']] = $_sub_category['name'];
            }
        }
    } else {
        $categories = $db->all('category', ['id', 'name'], null, null, ['path']);
        if(!empty($categories)) {
            while($_category = array_shift($categories)) {
                $category_ids[$_category['id']] = $_category['name'];
                $category_cache[$_category['id']] = $_category['name'];
            }
        }
    }

    $sql = 'select od.`product_sn`,od.`product_name`,od.`product_price`,od.`count`,o.`add_time`,o.`pay_time`,o.`payment_name`,o.`status` from '.
        $db->table('order_detail').' as od left join '.$db->table('order').' as o using(`order_sn`) '.$where.
        ' order by o.`add_time` DESC';

    $order_details = $db->fetchAll($sql);

    $product_category_mapper = [];
    if(!empty($order_details)) {
        foreach($order_details as $i => &$_order_detail) {
            if(!isset($product_category_mapper[$_order_detail['product_sn']])) {
                $product_category_assoc = $db->all('product_category_mapper', ['category_id'], ['product_sn' => $_order_detail['product_sn']]);
                $assocs = [];
                foreach($product_category_assoc as $_assoc) {
                    array_push($assocs, $_assoc['category_id']);
                }

                $product_category_mapper[$_order_detail['product_sn']] = $assocs;
            }

            $flag = false;
            $product_category_assoc = $product_category_mapper[$_order_detail['product_sn']];
            foreach($product_category_assoc as $_category_id) {
                if(isset($category_ids[$_category_id])) {
                    $flag = true;
                    break;
                }
            }

            if($flag) {
                $_order_detail['category_name'] = '';
                $_order_detail['assoc_category_name'] = '';
                while ($_category_id = array_shift($product_category_assoc)) {
                    if(!isset($category_cache[$_category_id])) {
                        $_category = $db->find('category', ['id', 'name'], ['id' => $_category_id]);
                        if(!empty($_category)) {
                            $category_cache[$_category_id] = $_category['name'];
                        }
                    }

                    if(empty($_order_detail['category_name'])) {
                        $_order_detail['category_name'] = $category_cache[$_category_id];
                    } else if(empty($_order_detail['assoc_category_name'])) {
                        $_order_detail['assoc_category_name'] = $category_cache[$_category_id];
                    } else {
                        $_order_detail['assoc_category_name'] .= ','.$category_cache[$_category_id];
                    }
                }
            } else {
                unset($order_details[$i]);
            }
        }
    }

    $total = count($order_details);
    $total_page = ceil($total/$step);
    $page = min($page, $total_page);
    $page = max(1, $page);

    if($total) {
        $_order_details = array_values($order_details);
        $order_details = [];
        $end = $page * $step;
        $end = min($total, $end);
        for($i = ($page - 1) * $step; $i < $end; $i++) {
            $od = $_order_details[$i];
            $order_status = '';
            switch($od['status']) {
                case 1:
                case 11:
                    $order_status = '待支付';
                    break;

                case 10:
                    $order_status = '已退款';
                    break;

                default:
                    $order_status = '已支付';
                    break;
            }
            array_push($order_details, [
                'product_sn' => $od['product_sn'],
                'product_name' => $od['product_name'],
                'category_name' => $od['category_name'],
                'assoc_category_name' => $od['assoc_category_name'],
                'product_price' => $od['product_price'],
                'payment_name' => $od['payment_name'],
                'pay_status' => $order_status,
                'count' => $od['count'],
                'amount' => $od['count'] * $od['product_price'],
                'pay_time' => empty($od['pay_time']) ? '-' : date('Y-m-d H:i:s', $od['pay_time']),
                'add_time' => date('Y-m-d H:i:s', $od['add_time'])
            ]);
        }
    }

    $header = [
        '产品编号', '产品名称', '产品分类', '关联分类', '交易价格', '支付方式', '支付状态', '销售数量', '销售金额', '支付时间', '创建时间'
    ];

    if('export' == $export) {
        $file_name = date('Ymd', $start_date).'_'.date('Ymd', $end_date).'商品交易明细'.date('YmdHis').'.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="'.$file_name.'"');
        $out = fopen('php://output', 'w');
        fwrite($out, chr(0xEF).chr(0xBB).chr(0xBF));
        fputcsv($out, $header);
        while($_order_detail = array_shift($order_details)) {
            fputcsv($out, $_order_detail);
        }
        fclose($out);
        exit;
    }

    create_pager($page, $total_page, $total);
    assign('header', $header);
    assign('order_details', $order_details);
    assign('start_date', date('Y-m-d', $start_date));
    assign('end_date', date('Y-m-d', $end_date));
    assign('category_id', $category_id);

    $categories = $db->all('category', ['id', 'name', 'parent_id']);
    assign('categories', $categories);
}

//商品交易明细汇总
if('order_detail_summary' == $act) {
    if( !check_purview('pur_data_view', $_SESSION['business_purview']) ) {
        show_system_message('没有操作权限');
    }

    $data_set = [];

    $export = check_action('export|view', getGET('export'), 'view');
    $start_date = trim(getGET('start_date'));
    $end_date = trim(getGET('end_date'));
    $category_id = intval(getGET('category_id'));

    $where = ' WHERE 1';

    if(!empty($start_date)) {
        $start_date = strtotime($start_date.' 00:00:00');
    } else {
        $start_date = strtotime('-7 days');
    }

    if(!empty($end_date)) {
        $end_date = strtotime($end_date. ' 23:59:59');
    } else {
        $end_date = time();
    }

    if($start_date > 0 && $end_date > 0) {
        if($end_date < $start_date) {
            show_system_message('结束日期不能早于开始日期');
        }

        $where .= ' AND o.`add_time` between '.$start_date.' and '.$end_date;
    } else if($start_date > 0) {
        $where .= ' AND o.`add_time`>='.$start_date;
    } else if($end_date > 0) {
        $where .= ' AND o.`add_time`<='.$end_date;
    }

    $category_ids = [];
    $category_cache = [];
    if($category_id > 0) {
        $category = $db->find('category', ['id', 'name', 'path'], ['id' => $category_id]);

        if(empty($category)) {
            show_system_message('分类不存在');
        }

        $category_ids[$category['id']] = $category['name'];
        $category_cache[$category['id']] = $category['name'];
        $sub_categories = $db->all('category', ['id', 'name'], ['path' => ['like', $category['path'].'%']]);
        if(!empty($sub_categories)) {
            while($_sub_category = array_shift($sub_categories)) {
                $category_ids[$_sub_category['id']] = $_sub_category['name'];
                $category_cache[$_sub_category['id']] = $_sub_category['name'];
            }
        }
    } else {
        $categories = $db->all('category', ['id', 'name'], null, null, ['path']);
        if(!empty($categories)) {
            while($_category = array_shift($categories)) {
                $category_ids[$_category['id']] = $_category['name'];
                $category_cache[$_category['id']] = $_category['name'];
            }
        }
    }

    $sql = 'select od.`product_sn`,od.`product_name`,od.`product_price`,od.`count`,o.`payment_name`,o.`status` from '.
        $db->table('order_detail').' as od left join '.$db->table('order').' as o using(`order_sn`) '.$where.
        ' order by o.`add_time` DESC';

    $order_details = $db->fetchAll($sql);

    $order_detail_summary = [];
    $product_category_mapper = [];
    if(!empty($order_details)) {
        while($_order_detail = array_shift($order_details)) {
            if(!isset($product_category_mapper[$_order_detail['product_sn']])) {
                $product_category_assoc = $db->all('product_category_mapper', ['category_id'], ['product_sn' => $_order_detail['product_sn']]);
                $assocs = [];
                foreach($product_category_assoc as $_assoc) {
                    array_push($assocs, $_assoc['category_id']);
                }

                $product_category_mapper[$_order_detail['product_sn']] = $assocs;
            }

            $flag = false;
            $product_category_assoc = $product_category_mapper[$_order_detail['product_sn']];
            foreach($product_category_assoc as $_category_id) {
                if(isset($category_ids[$_category_id])) {
                    $flag = true;
                    break;
                }
            }

            if($flag) {
                $order_status = '';
                $order_status_mark = 0;
                switch($_order_detail['status']) {
                    case 1:
                    case 11:
                        $order_status = '待支付';
                        $order_status_mark = 0;
                        break;

                    case 10:
                        $order_status = '已退款';
                        $order_status_mark = 2;
                        break;

                    default:
                        $order_status = '已支付';
                        $order_status_mark = 1;
                        break;
                }

                if(!isset($order_detail_summary[$_order_detail['product_sn'].'-'.$order_status_mark.'-'.$_order_detail['product_price'].$_order_detail['payment_name']])) {
                    $_order_detail['category_name'] = '';
                    $_order_detail['assoc_category_name'] = '';
                    while ($_category_id = array_shift($product_category_assoc)) {
                        if(!isset($category_cache[$_category_id])) {
                            $_category = $db->find('category', ['id', 'name'], ['id' => $_category_id]);
                            if(!empty($_category)) {
                                $category_cache[$_category_id] = $_category['name'];
                            }
                        }

                        if(empty($_order_detail['category_name'])) {
                            $_order_detail['category_name'] = $category_cache[$_category_id];
                        } else if(empty($_order_detail['assoc_category_name'])) {
                            $_order_detail['assoc_category_name'] = $category_cache[$_category_id];
                        } else {
                            $_order_detail['assoc_category_name'] .= ','.$category_cache[$_category_id];
                        }
                    }

                    $order_detail_summary[$_order_detail['product_sn'].'-'.$order_status_mark.'-'.$_order_detail['product_price'].$_order_detail['payment_name']] = [
                        'product_sn' => $_order_detail['product_sn'],
                        'product_name' => $_order_detail['product_name'],
                        'category_name' => $_order_detail['category_name'],
                        'assoc_category_name' => $_order_detail['assoc_category_name'],
                        'product_price' => $_order_detail['product_price'],
                        'payment_name' => $_order_detail['payment_name'],
                        'pay_status' => $order_status,
                        'count' => 0,
                        'amount' => 0
                    ];
                }

                $order_detail_summary[$_order_detail['product_sn'].'-'.$order_status_mark.'-'.$_order_detail['product_price'].$_order_detail['payment_name']]['count'] += $_order_detail['count'];
                $order_detail_summary[$_order_detail['product_sn'].'-'.$order_status_mark.'-'.$_order_detail['product_price'].$_order_detail['payment_name']]['amount'] += $_order_detail['count'] * $_order_detail['product_price'];
            }
        }
    }

    $header = [
        '产品编号', '产品名称', '产品分类', '关联分类', '交易价格', '支付方式', '支付状态', '销售数量', '销售金额'
    ];

    if('export' == $export) {
        $file_name = date('Ymd', $start_date).'_'.date('Ymd', $end_date).'商品交易明细'.date('YmdHis').'.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="'.$file_name.'"');
        $out = fopen('php://output', 'w');
        fwrite($out, chr(0xEF).chr(0xBB).chr(0xBF));
        fputcsv($out, $header);
        while($_order_detail = array_shift($order_detail_summary)) {
            fputcsv($out, $_order_detail);
        }
        fclose($out);
        exit;
    }

    assign('header', $header);
    assign('order_detail_summary', $order_detail_summary);
    assign('start_date', date('Y-m-d', $start_date));
    assign('end_date', date('Y-m-d', $end_date));
    assign('category_id', $category_id);

    $categories = $db->all('category', ['id', 'name', 'parent_id']);
    assign('categories', $categories);
}

//订单明细
if('order_list' == $act) {
    if( !check_purview('pur_data_view', $_SESSION['business_purview']) ) {
        show_system_message('没有操作权限');
    }

    $data_set = [];

    $export = check_action('export|view', getGET('export'), 'view');
    $start_date = trim(getGET('start_date'));
    $end_date = trim(getGET('end_date'));
    $delivery_st_date = trim(getGET('delivery_st_date'));
    $delivery_ed_date = trim(getGET('delivery_ed_date'));
    $pay_st_date = trim(getGET('pay_st_date'));
    $pay_ed_date = trim(getGET('pay_ed_date'));
    $category_id = intval(getGET('category_id'));
    $status = intval(getGET('status'));
    $page = intval(getGET('page'));
    $page = max($page, 1);
    $order_sn = trim(getGET('order_sn'));

    $conditions = [];

    if(!array_key_exists($status, $order_status)) {
        $status = 0;
    } else {
        $conditions['status'] = $status;
    }

    if(!empty($order_sn)) {
        $conditions['order_sn'] = $order_sn;
    }

    if(!empty($start_date)) {
        $start_date = strtotime($start_date.' 00:00:00');
    } else {
        $start_date = strtotime('-7 days');
    }

    if(!empty($end_date)) {
        $end_date = strtotime($end_date. ' 23:59:59');
    } else {
        $end_date = time();
    }

    if($start_date > 0 && $end_date > 0) {
        if($end_date < $start_date) {
            show_system_message('结束日期不能早于开始日期');
        }

        $conditions['add_time'] = ['between', [$start_date, $end_date]];
    } else if($start_date > 0) {
        $conditions['add_time'] = ['egt', $start_date];
    } else if($end_date > 0) {
        $conditions['add_time'] = ['elt', $end_date];
    }

    if(!empty($delivery_st_date)) {
        $delivery_st_date = strtotime($delivery_st_date.' 00:00:00');
    }

    if(!empty($delivery_ed_date)) {
        $delivery_ed_date = strtotime($delivery_ed_date.' 23:59:59');
    }

    if($delivery_ed_date > 0 && $delivery_st_date > 0) {
        if($delivery_ed_date < $delivery_st_date) {
            show_system_message('发货日期结束日期不能早于开始日期');
        }

        $conditions['delivery_time'] = ['between', [$delivery_st_date, $delivery_ed_date]];
    } else if($delivery_st_date > 0) {
        $conditions['delivery_time'] = ['egt', $delivery_st_date];
    } else if($delivery_ed_date > 0) {
        $conditions['delivery_time'] = ['elt', $delivery_ed_date];
    }

    if(!empty($pay_st_date)) {
        $pay_st_date = strtotime($pay_st_date.' 00:00:00');
    }

    if(!empty($pay_ed_date)) {
        $pay_ed_date = strtotime($pay_ed_date.' 23:59:59');
    }

    if($pay_ed_date > 0 && $pay_st_date > 0) {
        if($pay_ed_date < $pay_st_date) {
            show_system_message('发货日期结束日期不能早于开始日期');
        }

        $conditions['pay_time'] = ['between', [$pay_st_date, $pay_ed_date]];
    } else if($pay_st_date > 0) {
        $conditions['pay_time'] = ['egt', $pay_st_date];
    } else if($pay_ed_date > 0) {
        $conditions['pay_time'] = ['elt', $pay_ed_date];
    }

    $step = 2;
    $total_count = $db->getColumn('order', 'count(*)', $conditions);
    $total_page = ceil($total_count/$step);
    $page = min($page, $total_page);
    $limit = ($page - 1) * $step;
    $limit .= ','.$step;

    if('export' == $export) {
        $limit = null;
    }

    $columns = [
        'order_sn', 'add_time', 'pay_time', 'delivery_time', 'receive_time', 'province', 'city', 'district', 'group',
        'address', 'consignee', 'mobile', 'status', 'delivery_name', 'express_sn', 'express_id', 'amount', 'consignee',
        '(select count(*) from '.$db->table('order_detail').' as od where od.`order_sn`='.$db->table('order').'.`order_sn`) as count'
    ];

    $order_list = $db->all('order', $columns, $conditions, $limit, [['add_time', 'DESC']]);

    $province_cache = [];
    $city_cache = [];
    $district_cache = [];
    $group_cache = [];
    $express_cache = [];

    $orders = [];
    while($order = array_shift($order_list)) {
        if(!isset($province_cache[$order['province']])) {
            $province_cache[$order['province']] = $db->find('province', ['id', 'province_name'], ['id' => $order['province']]);
        }

        if(!isset($city_cache[$order['city']])) {
            $city_cache[$order['city']] = $db->find('city', ['id', 'city_name'], ['id' => $order['city']]);
        }

        if(!isset($district_cache[$order['district']])) {
            $district_cache[$order['district']] = $db->find('district', ['id', 'district_name'], ['id' => $order['district']]);
        }

        if(!isset($group_cache[$order['group']])) {
            $group_cache[$order['group']] = $db->find('group', ['id', 'group_name'], ['id' => $order['group']]);
        }

        if($order['express_id'] > 0 && !isset($express_cache[$order['express_id']])) {
            $express = $db->find('express', ['id', 'name'], ['id' => $order['express_id']]);

            if(!empty($express)) {
                $express_cache[$order['express_id']] = $express['name'];
            } else {
                $express_cache[$order['express_id']] = '';
            }
        }

        $address = $province_cache[$order['province']]['province_name'].' '.$city_cache[$order['city']]['city_name'].' '.$district_cache[$order['district']]['district_name'].
            ' '.$group_cache[$order['group']]['group_name'].' '.$order['address'];

        array_push($orders, [
            'order_sn' => $order['order_sn'],
            'add_time' => date('Y-m-d H:i:s', $order['add_time']),
            'pay_time' => !empty($order['pay_time']) ? date('Y-m-d H:i:s', $order['pay_time']) : '',
            'delivery_time' => !empty($order['delivery_time']) ? date('Y-m-d H:i:s', $order['delivery_time']) : '',
            'receive_time' => !empty($order['receive_time']) ? date('Y-m-d H:i:s', $order['receive_time']) : '',
            'address' => $address,
            'consignee' => $order['consignee'],
            'mobile' => $order['mobile'],
            'status' => $order_status[$order['status']],
            'delivery_name' => $order['delivery_name'],
            'express_name' => $order['express_id'] ? $express_cache[$order['express_id']] : '',
            'express_sn' => $order['express_sn'],
            'count' => $order['count'],
            'amount' => $order['amount']

        ]);
    }

    $header = [
        '订单编号', '下单时间', '支付时间', '发货时间', '确认收货时间', '收货地址', '收货人', '联系方式', '订单状态', '配送方式', '物流公司', '快递单号', '商品数量', '订单金额'
    ];

    if('export' == $export) {
        $file_name = date('Ymd', $start_date).'_'.date('Ymd', $end_date).'订单明细'.date('YmdHis').'.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="'.$file_name.'"');
        $out = fopen('php://output', 'w');
        fwrite($out, chr(0xEF).chr(0xBB).chr(0xBF));
        fputcsv($out, $header);
        while($_order = array_shift($orders)) {
            $_order['order_sn'] = "\t".$_order['order_sn'];
            $_order['mobile'] = "\t".$_order['mobile'];
            $_order['express_sn'] = "\t".$_order['express_sn'];
            fputcsv($out, $_order);
        }
        fclose($out);
        exit;
    }

    create_pager($page, $total_page, $total_count);
    assign('header', $header);
    assign('order_list', $orders);
    assign('order_sn', $order_sn);
    assign('order_status', $order_status);
    assign('status', $status);
    assign('start_date', date('Y-m-d', $start_date));
    assign('end_date', date('Y-m-d', $end_date));

    if($delivery_st_date > 0) {
        assign('delivery_st_date', date('Y-m-d', $delivery_st_date));
    } else {
        assign('delivery_st_date', '');
    }

    if($delivery_ed_date > 0) {
        assign('delivery_ed_date', date('Y-m-d', $delivery_ed_date));
    } else {
        assign('delivery_ed_date', '');
    }

    if($pay_st_date > 0) {
        assign('pay_st_date', date('Y-m-d', $pay_st_date));
    } else {
        assign('pay_st_date', '');
    }

    if($pay_ed_date > 0) {
        assign('pay_ed_date', date('Y-m-d', $pay_ed_date));
    } else {
        assign('pay_ed_date', '');
    }
}

$template .= $act.'.phtml';
$smarty->display($template);