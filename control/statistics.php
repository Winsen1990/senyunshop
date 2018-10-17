<?php
/**
 * 统计
 * @author 王仁欢
 * @email wrh4285@163.com
 * @date 2015-10-20
 * @version 1.0.0
 */

include 'library/init.inc.php';
back_base_init();

$template = 'statistics/';
assign('subTitle', '站点统计');

$action = 'view|detail';
$operation = '';

$act = check_action($action, getGET('act'));
$act = ( $act == '' ) ? 'view' : $act;
$opera = check_action($operation, getPOST('opera'));

if( 'view' == $act ) {
    if( !check_purview('pur_statistics_view', $_SESSION['purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }

    $date = trim(getGET('date'));

    if( '' != $date ) {
        if(preg_match('#^(?:(?!0000)[0-9]{4}-(?:(?:0[1-9]|1[0-2])-(?:0[1-9]|1[0-9]|2[0-8])|(?:0[13-9]|1[0-2])-(?:29|30)|(?:0[13578]|1[02])-31)|(?:[0-9]{2}(?:0[48]|[2468][048]|[13579][26])|(?:0[48]|[2468][048]|[13579][26])00)-02-29)$#', $date)) {
            $day = $date;
            $day_end = strtotime($day) + 3600 * 24;
            $dateTime = explode(' ', $date);
            $date = explode('-', $dateTime[0]);
            $date = $date[0].$date[1];
        } else {
            $day = date('Y-m-d', time());
            $day_end = strtotime($day) + 3600 * 24;
            $date = date('Ym', time());
        }
    } else {
        $day = date('Y-m-d', time());
        $day_end = strtotime($day) + 3600 * 24;
        $date = date('Ym', time());
    }

    $table = 'statistics'.$date;
    //表是否存在
    $check_table = 'SHOW TABLES LIKE \'%'.DB_PREFIX.$table.'%\';';
    $exists = $db->fetchOne($check_table);

    if( $exists ) {

        $day_start = strtotime($day);

        //pv一天各个时间访问量
        $get_pv_list = 'select `ip`, `request_time` from '.$db->table($table);
        $get_pv_list .= ' where `request_time` > '.$day_start.' and `request_time` < '.$day_end;
        $get_pv_list .= ' order by `id` asc';
        $pv_list = $db->fetchAll($get_pv_list);
        $pv_target = array(0,0,0,0,0,0,0,0,0,0,0,0);
        if( $pv_list ) {
            foreach( $pv_list as $pv ) {
                if( $pv['request_time'] >= $day_start && $pv['request_time'] <= ($day_start + 3600 * 2) ) {
                    $pv_target[0]++;
                }
                if( $pv['request_time'] >= ($day_start + 3600 * 2) && $pv['request_time'] <= ($day_start + 3600 * 4) ) {
                    $pv_target[1]++;
                }
                if( $pv['request_time'] >= ($day_start + 3600 * 4) && $pv['request_time'] <= ($day_start + 3600 * 6) ) {
                    $pv_target[2]++;
                }
                if( $pv['request_time'] >= ($day_start + 3600 * 6) && $pv['request_time'] <= ($day_start + 3600 * 8) ) {
                    $pv_target[3]++;
                }
                if( $pv['request_time'] >= ($day_start + 3600 * 8) && $pv['request_time'] <= ($day_start + 3600 * 10) ) {
                    $pv_target[4]++;
                }
                if( $pv['request_time'] >= ($day_start + 3600 * 10) && $pv['request_time'] <= ($day_start + 3600 * 12) ) {
                    $pv_target[5]++;
                }
                if( $pv['request_time'] >= ($day_start + 3600 * 12) && $pv['request_time'] <= ($day_start + 3600 * 14) ) {
                    $pv_target[6]++;
                }
                if( $pv['request_time'] >= ($day_start + 3600 * 14) && $pv['request_time'] <= ($day_start + 3600 * 16) ) {
                    $pv_target[7]++;
                }
                if( $pv['request_time'] >= ($day_start + 3600 * 16) && $pv['request_time'] <= ($day_start + 3600 * 18) ) {
                    $pv_target[8]++;
                }
                if( $pv['request_time'] >= ($day_start + 3600 * 18) && $pv['request_time'] <= ($day_start + 3600 * 20) ) {
                    $pv_target[9]++;
                }
                if( $pv['request_time'] >= ($day_start + 3600 * 20) && $pv['request_time'] <= ($day_start + 3600 * 22) ) {
                    $pv_target[10]++;
                }
                if( $pv['request_time'] >= ($day_start + 3600 * 22) && $pv['request_time'] <= ($day_start + 3600 * 24) ) {
                    $pv_target[11]++;
                }
            }
        }
//        foreach( $pv_target as $key => $pv ) {
//            if( $pv_target[$key] == 0 ) {
//                unset($pv_target[$key] );
//            }
//        }

        //uv一天各个时间访问量
        $get_uv_list = 'select `request_time`, `cookie` from '.$db->table($table);
        $get_uv_list .= ' where `request_time` > '.$day_start.' and `request_time` < '.$day_end;
        $get_uv_list .= ' group by `ip`';
        $get_uv_list .= ' order by `id` asc';
        $uv_list = $db->fetchAll($get_uv_list);
        $uv_target = array(0,0,0,0,0,0,0,0,0,0,0,0);
        if( $uv_list ) {
            foreach ($uv_list as $uv) {
                if ($uv['request_time'] >= $day_start && $uv['request_time'] <= ($day_start + 3600 * 2)) {
                    $uv_target[0]++;
                }
                if ($uv['request_time'] >= ($day_start + 3600 * 2) && $uv['request_time'] <= ($day_start + 3600 * 4)) {
                    $uv_target[1]++;
                }
                if ($uv['request_time'] >= ($day_start + 3600 * 4) && $uv['request_time'] <= ($day_start + 3600 * 6)) {
                    $uv_target[2]++;
                }
                if ($uv['request_time'] >= ($day_start + 3600 * 6) && $uv['request_time'] <= ($day_start + 3600 * 8)) {
                    $uv_target[3]++;
                }
                if ($uv['request_time'] >= ($day_start + 3600 * 8) && $uv['request_time'] <= ($day_start + 3600 * 10)) {
                    $uv_target[4]++;
                }
                if ($uv['request_time'] >= ($day_start + 3600 * 10) && $uv['request_time'] <= ($day_start + 3600 * 12)) {
                    $uv_target[5]++;
                }
                if ($uv['request_time'] >= ($day_start + 3600 * 12) && $uv['request_time'] <= ($day_start + 3600 * 14)) {
                    $uv_target[6]++;
                }
                if ($uv['request_time'] >= ($day_start + 3600 * 14) && $uv['request_time'] <= ($day_start + 3600 * 16)) {
                    $uv_target[7]++;
                }
                if ($uv['request_time'] >= ($day_start + 3600 * 16) && $uv['request_time'] <= ($day_start + 3600 * 18)) {
                    $uv_target[8]++;
                }
                if ($uv['request_time'] >= ($day_start + 3600 * 18) && $uv['request_time'] <= ($day_start + 3600 * 20)) {
                    $uv_target[9]++;
                }
                if ($uv['request_time'] >= ($day_start + 3600 * 20) && $uv['request_time'] <= ($day_start + 3600 * 22)) {
                    $uv_target[10]++;
                }
                if ($uv['request_time'] >= ($day_start + 3600 * 22) && $uv['request_time'] <= ($day_start + 3600 * 24)) {
                    $uv_target[11]++;
                }
            }
        }


        assign('uv_target', json_encode($uv_target));
        assign('pv_target', json_encode($pv_target));
        assign('day', $day);



        if( empty($pv_list) ) {
            assign('total', 0);
        } else {
            assign('total', count($pv_list));
        }


    } else {
        assign('', 0);
    }
}

if( 'detail' == $act ) {
    if( !check_purview('pur_statistics_view', $_SESSION['purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }
    $date = trim(getGET('date'));
    $page = intval(getGET('page'));

    if( '' != $date ) {
        if(preg_match('#^(?:(?!0000)[0-9]{4}-(?:(?:0[1-9]|1[0-2])-(?:0[1-9]|1[0-9]|2[0-8])|(?:0[13-9]|1[0-2])-(?:29|30)|(?:0[13578]|1[02])-31)|(?:[0-9]{2}(?:0[48]|[2468][048]|[13579][26])|(?:0[48]|[2468][048]|[13579][26])00)-02-29)$#', $date)) {
            $day = $date;
            $day_end = strtotime($day) + 3600 * 24;
            $dateTime = explode(' ', $date);
            $date = explode('-', $dateTime[0]);
            $date = $date[0].$date[1];
        } else {
            $day = date('Y-m-d', time());
            $day_end = strtotime($day) + 3600 * 24;
            $date = date('Ym', time());
        }
    } else {
        $day = date('Y-m-d', time());
        $day_end = strtotime($day) + 3600 * 24;
        $date = date('Ym', time());
    }

    $table = 'statistics'.$date;
    //表是否存在
    $check_table = 'SHOW TABLES LIKE \'%'.DB_PREFIX.$table.'%\';';
    $exists = $db->fetchOne($check_table);

    if( $exists ) {

        $day_start = strtotime($day);
        //当天pv总量，分页
        $get_total = 'select count(*) from '.$db->table($table);
        $get_total .= ' where `request_time` > ' . $day_start . ' and `request_time` < ' . $day_end;
        $total = $db->fetchOne($get_total);
        $count = 20;
        $total_page = ceil( $total / $count );
        $page = $page > $total_page ? $total_page : $page;
        $page = $page <= 0 ? 1 : $page;
        $offset = ( $page - 1 ) * $count;

        assign('count', $count);
        create_pager($page, $total_page, $total);
        assign('day', $day);

        //pv一天访问明细
        $get_pv_list = 'select * from ' . $db->table($table);
        $get_pv_list .= ' where `request_time` > ' . $day_start . ' and `request_time` < ' . $day_end;
        $get_pv_list .= ' order by `id` asc';
        $get_pv_list .= ' limit '.$offset.','.$count;
        $pv_list = $db->fetchAll($get_pv_list);
        if ($pv_list) {
            foreach ($pv_list as $key => $pv) {
                $pv_list[$key]['request_time_str'] = date('H:i:s', $pv['request_time']);
                $pv_list[$key]['source'] = $pv['source'] == '' ? '直接输入网址或微信链接' : $pv['source'];
                $pv_list[$key]['agent'] = strlen($pv['agent']) > 60 ? substr($pv['agent'], 0, 60).'<br />'.substr($pv['agent'], 60) : $pv['agent'];
            }
        }
        assign('pv_list', $pv_list);

    } else {
        assign('pv_list', null);
    }
}

$template .= $act.'.phtml';
$smarty->display($template);

