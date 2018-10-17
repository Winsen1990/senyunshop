<?php
/**
 * 导航管理
 * @author 王仁欢
 * @email wrh4285@163.com
 * @date 2015-8-6
 * @version 1.0.0
 */
include 'library/init.inc.php';
back_base_init();

$template = 'nav/';
assign('subTitle', '导航管理');

$action = 'edit|add|view|delete';
$operation = 'edit|add';

$act = check_action($action, getGET('act'));
$act = ( $act == '' ) ? 'view' : $act;

$opera = check_action($operation, getPOST('opera'));
//===========================================================================

//添加导航
if( 'add' == $opera ) {
    if(!check_purview('pur_nav_add', $_SESSION['purview'])) {
        show_system_message('权限不足', array());
        exit;
    }

    $name = trim(getPOST('menuName'));
    $parent_id = trim(getPOST('parentId'));
    $url = trim(getPOST('menuUrl'));
    $order_view = trim(getPOST('menuSort'));
    $position = trim(getPOST('position'));
    //新增导航条
    $error = '';
    if('' == $name) {
        $error .= '-导航栏名称不能为空'."\n";
    } else {
        $name = $db->escape(htmlspecialchars($name));
    }

    if(0 > $parent_id) {
        $error .= '-父级导航栏参数错误'."\n";
    } else {
        $parent_id = intval($parent_id);
    }

    if('' == $url) {
        $error .= '-URL不能为空'."\n";
    } else {
        $url = $db->escape(htmlspecialchars($url));
    }

    if(false === strpos('middle|top|bottom', $position)) {
        $position = 'middle';
    } else {
        $position = $db->escape($position);
    }

    $order_view = intval($order_view);
    $order_view = $order_view < 0 ? 50 : $order_view;

    if('' != $error) {
        show_system_message($error, array());
        exit;
    }

    $data = array(
        'name' => $name,
        'url' => $url,
        'order_view' => $order_view,
        'position' => $position,
        'parent_id' => $parent_id,
    );
    if(!$db->autoInsert('nav', array($data))) {
        show_system_message(':新增导航栏失败，请联系管理员', array(), 5);
        exit;
    } else {
        $links = array(
            array('alt'=>'查看导航条', 'link'=>'nav.php?act=view'),
            array('alt'=>'继续新增导航条', 'link'=>'nav.php?act=add')
        );
        show_system_message('新增导航条成功', $links);
        exit;
    }
}

//编辑导航
if( 'edit' == $opera ) {
    if(!check_purview('pur_nav_edit', $_SESSION['purview'])) {
        show_system_message('权限不足', array());
        exit;
    }

    $id = intval(getPOST('id'));
    if( 0 >= $id ) {
        show_system_message('参数错误', array());
        exit;
    }

    $getNav = 'select * from `'.DB_PREFIX.'nav` where `id` = \''.$id.'\' limit 1';
    $nav = $db->fetchRow($getNav);

    if( empty($nav) ) {
        show_system_message('导航不存在', array());
        exit;
    }

    $name = trim(getPOST('menuName'));
    $parent_id = trim(getPOST('parentId'));
    $url = trim(getPOST('menuUrl'));
    $order_view = trim(getPOST('menuSort'));
    $position = trim(getPOST('position'));

    $error = '';
    if('' == $name) {
        $error .= '-导航栏名称不能为空'."\n";
    } else {
        $name = $db->escape(htmlspecialchars($name));
    }

    if( 0 > $parent_id ) {
        $error .= '-父级导航栏参数错误'."\n";
    } else {
        $parent_id = intval($parent_id);
    }

    if('' == $url) {
        $error .= '-URL不能为空'."\n";
    } else {
        $url = $db->escape(htmlspecialchars($url));
    }

    if(false === strpos('middle|top|bottom', $position)) {
        $position = 'middle';
    } else {
        $position = $db->escape($position);
    }

    $order_view = intval($order_view);
    $order_view = $order_view < 0 ? 50 : $order_view;

    if('' != $error) {
        show_system_message($error, array());
        exit;
    }

    $data = array(
        'name' => $name,
        'url' => $url,
        'order_view' => $order_view,
        'position' => $position,
        'parent_id' => $parent_id,
    );
    $where = '`id` = \''.$id.'\'';
    $order = '';
    $limit = '1';

    if( $db->autoUpdate('nav', $data, $where, $order, $limit) ) {

        $links = array(
            array('alt'=>'查看导航条', 'link'=>'nav.php?act=view'),
            array('alt'=>'新增导航条', 'link'=>'nav.php?act=add')
        );
        show_system_message('成功更新导航栏', $links);
        exit;
    } else {
        show_system_message('更新导航栏失败，请联系管理员', array(), 5);
        exit;
    }


}

//===========================================================================

//添加导航
if( 'add' == $act ) {
    if(!check_purview('pur_nav_add', $_SESSION['purview'])) {
        show_system_message('权限不足', array());
        exit;
    }

    $getNavs  = 'select * from `'.DB_PREFIX.'nav`';
    $getNavs .= ' order by `position` desc,  `order_view` asc' ;
    $navs = $db->fetchAll($getNavs);

    if( $navs ) {
        //初始化入栈标志
        foreach ($navs as $k => $v) {
            $navs[$k]['in'] = false;
        }

        $stack = array();
        $result = array();

        foreach ($navs as $k => $v) {
            if ($v['parent_id'] != 0) {
                continue;
            }
            //入栈
            $v['depth'] = 0;
            $navs[$k]['in'] = true;
            $v['depth'] = 0;
            array_push($stack, $v);
            array_push($result, $v);

            while (!empty($stack)) {
                $index = count($stack) - 1;
                $temp = $stack[$index];
                $no_children = true;

                foreach ($navs as $k1 => $v1) {
                    if ($v1['in'] == true) {
                        continue;
                    }
                    if ($v1['parent_id'] == $temp['id']) { //存在下级导航，下级导航入栈
                        $navs[$k1]['in'] = true;
                        $v1['depth'] = $temp['depth'] + 1;
                        array_push($stack, $v1);
                        array_push($result, $v1);
                        $no_children = false;
                        break;
                    }
                }
                if ($no_children) {    //不存在下级导航，弹栈
                    array_pop($stack);
                }
            }

        }


        foreach ($result as $k => $v) {
            $prefix = '';
            $flag = false;
            while ($v['depth']-- != 0) {
                $prefix .= '&nbsp;&nbsp;';
                $flag = true;
            }
            $prefix .= '|--';
            $result[$k]['name'] = $flag ? $prefix . $v['name'] : $v['name'];
        }
    }

    assign('navs', $result);
    assign('navs_str', json_encode($result));

}

//编辑导航
if( 'edit' == $act ) {

    if(!check_purview('pur_nav_edit', $_SESSION['purview'])) {
        show_system_message('权限不足', array());
        exit;
    }

    $id = intval(getGET('id'));
    if( 0 >= $id ) {
        show_system_message('参数错误', array());
    }

    $getNav = 'select * from `'.DB_PREFIX.'nav` where `id` = \''.$id.'\' limit 1';
    assign('nav', $db->fetchRow($getNav));


    $getNavs  = 'select * from `'.DB_PREFIX.'nav` where id <> \''.$id.'\' and parent_id <> \''.$id.'\'';
    $getNavs .= ' order by `position` desc,  `order_view` asc' ;
    $navs = $db->fetchAll($getNavs);
    if( $navs ) {
        //初始化入栈标志
        foreach ($navs as $k => $v) {
            $navs[$k]['in'] = false;
        }

        $stack = array();
        $result = array();

        foreach ($navs as $k => $v) {
            if ($v['parent_id'] != 0) {
                continue;
            }
            //入栈
            $v['depth'] = 0;
            $navs[$k]['in'] = true;
            $v['depth'] = 0;
            array_push($stack, $v);
            array_push($result, $v);

            while (!empty($stack)) {
                $index = count($stack) - 1;
                $temp = $stack[$index];
                $no_children = true;

                foreach ($navs as $k1 => $v1) {
                    if ($v1['in'] == true) {
                        continue;
                    }
                    if ($v1['parent_id'] == $temp['id']) { //存在下级导航，下级导航入栈
                        $navs[$k1]['in'] = true;
                        $v1['depth'] = $temp['depth'] + 1;
                        array_push($stack, $v1);
                        array_push($result, $v1);
                        $no_children = false;
                        break;
                    }
                }
                if ($no_children) {    //不存在下级导航，弹栈
                    array_pop($stack);
                }
            }

        }

        foreach ($result as $k => $v) {
            $prefix = '';
            $flag = false;
            while ($v['depth']-- != 0) {
                $prefix .= '&nbsp;&nbsp;';
                $flag = true;
            }
            $prefix .= '|--';
            $result[$k]['name'] = $flag ? $prefix . $v['name'] : $v['name'];
        }
    }

    assign('navs', $result);
    assign('navs_str', json_encode($result));
}


//删除导航
if( 'delete' == $act ) {
    if(!check_purview('pur_nav_del', $_SESSION['purview'])) {
        show_system_message('权限不足', array());
        exit;
    }

    $id = intval(getGET('id'));
    if( 0 >= $id ) {
        show_system_message('参数错误', array());
        exit;
    }

    $checkNav = 'select `id` from `'.DB_PREFIX.'nav` where `parent_id`='.$id;
    if($db->fetchRow($checkNav)) {
        show_system_message('当前导航条还有子栏目，请先删除子栏目', array());
        exit;
    } else {
        $deleteNav = 'delete from `'.DB_PREFIX.'nav` where `id`='.$id.' limit 1';
        if($db->delete($deleteNav)) {
            show_system_message('删除成功', array());
            exit;
        } else {
            show_system_message('删除导航条失败，请稍后再试', array());
            exit;
        }
    }
}


//导航列表
if( 'view' == $act ) {
    if(!check_purview('pur_nav_view', $_SESSION['purview'])) {
        show_system_message('权限不足', array());
        exit;
    }

    $getNavs  = 'select * from `'.DB_PREFIX.'nav`';
    $getNavs .= ' order by `position` desc,  `order_view` asc' ;
    $navs = $db->fetchAll($getNavs);

    if( $navs ) {
        //初始化入栈标志
        foreach ($navs as $k => $v) {
            $navs[$k]['in'] = false;
        }
    }

    $stack = array();
    $result = array();

    if( $navs ) {
        foreach ($navs as $k => $v) {
            if ($v['parent_id'] != 0) {
                continue;
            }
            //入栈
            $v['depth'] = 0;
            $navs[$k]['in'] = true;
            $v['depth'] = 0;
            array_push($stack, $v);
            array_push($result, $v);

            while (!empty($stack)) {
                $index = count($stack) - 1;
                $temp = $stack[$index];
                $no_children = true;

                foreach ($navs as $k1 => $v1) {
                    if ($v1['in'] == true) {
                        continue;
                    }
                    if ($v1['parent_id'] == $temp['id']) { //存在下级导航，下级导航入栈
                        $navs[$k1]['in'] = true;
                        $v1['depth'] = $temp['depth'] + 1;
                        array_push($stack, $v1);
                        array_push($result, $v1);
                        $no_children = false;
                        break;
                    }
                }
                if ($no_children) {    //不存在下级导航，弹栈
                    array_pop($stack);
                }
            }

        }
    }

    foreach( $result as $k => $v ) {
        $prefix = '';
        $flag = false;
        while( $v['depth']-- != 0 ) {
            $prefix .= '&nbsp;&nbsp;';
            $flag = true;
        }
        $prefix .= '|--';
        $result[$k]['name'] = $flag ? $prefix.$v['name'] : $v['name'];
    }

    assign('navs', $result);
}



$template .= $act.'.phtml';
$smarty->display($template);

