<?php
/**
 * Created by PhpStorm.
 * User: apple
 * Date: 15/10/26
 * Time: 上午9:50
 */
include 'library/init.inc.php';
back_base_init();

$template = 'wechat_menu/';
assign('subTitle', '微信菜单管理');

$action = 'view';
$operation = 'post|save|remove|get_oa_url';

$act = check_action($action, getGET('act'));
$act = ( $act == '' ) ? 'view' : $act;

$opera = check_action($operation, getPOST('opera'));

if('get_oa_url' == $opera)
{
    $response = array('msg' => '', 'error' => 1);

    $url = getPOST('url');

    $oathor_url = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=%s&redirect_uri=%s&response_type=code&scope=snsapi_userinfo&state=2048#wechat_redirect';
    $url = sprintf($oathor_url, $config['appid'], urlencode($url));

    $response['msg'] = $url;
    $response['error'] = 0;

    echo json_encode($response);
    exit;
}

if('save' == $opera)
{
    header("Content-Type: text/html;charset=utf-8");
    $response = array('msg' => '', 'error' => 1, 'data' => null);

    $item = array();
    $data = getPOST('data');
    if($data == '' || count($data) == 0) {
        $response['msg'] = '参数错误';
    } else {
        $name = $data['name'];
        $type = $data['type'];
        $parent_id = intval($data['parent_id']);
        $id = intval($data['id']);
        $key = $data['key'];
        $path = '';

        if($name == '') {
            $response['msg'] = '菜单名不能为空';
        } else {
            $name = $db->escape($name);
        }

        if($type == '') {
            $response['msg'] = '参数错误';
        } else {
            if($type != 'click' && $type != 'view') {
                $type = 'view';
            } else {
                $type = $db->escape($type);
            }
        }

        if($key == '') {
            $response['msg'] = '菜单值不能为空';
        } else {
            $key = $db->escape($key);
        }

        if($parent_id > 0) {
            $checkMenu = 'select `id`,`path` from '.$db->table('wx_menu').' where `id`='.$parent_id;
            $parent = $db->fetchRow($checkMenu);
            if($parent['id'] > 0)
            {
                $path = $parent['path'].',';
                $parent_id = $parent['id'];
            } else {
                $parent_id = 0;
            }
        } else {
            $parent_id = 0;
        }

        if($response['msg'] == '')
        {
            $sql = '';
            if($id > 0) {
                $checkMenu = 'select `id` from '.$db->table('wx_menu').' where `id`='.$id;
                $id = $db->fetchOne($checkMenu);

                if($id > 0) {
                    $item['name'] = $name;
                    $item['id'] = $id;
                    $item['parent_id'] = $parent_id;
                    $item['type'] = $type;
                    $item['key'] = $key;

                    $sql = 'update '.$db->table('wx_menu').' set `name`=\'%s\',`key`=\'%s\',`type`=\'%s\',`parent_id`=%d,`path`=\'%s\' where `id`=%d';
                    $sql = sprintf($sql, $name, $key, $type, $parent_id, $path.$id, $id);

                    if($db->update($sql))
                    {
                        $response['msg'] = '菜单保存成功';
                        $response['error'] = 0;
                        $response['data'] = $item;
                    } else {
                        $response['msg'] = '菜单保存失败';
                    }
                } else {
                    $response['msg'] = '参数错误';
                }
            } else {
                $sql = 'insert into '.$db->table('wx_menu').' (`name`,`key`,`type`,`parent_id`,`path`) values (\'%s\',\'%s\',\'%s\',%d, \'%s\')';
                $sql = sprintf($sql, $name, $key, $type, $parent_id, '');

                if($db->insert($sql))
                {
                    $id = $db->get_last_id();
                    $updatePath = 'update '.$db->table('wx_menu').' set `path`=\''.$path.$id.'\' where `id`='.$id;
                    $db->update($updatePath);

                    $item['name'] = $name;
                    $item['id'] = $id;
                    $item['parent_id'] = $parent_id;
                    $item['type'] = $type;
                    $item['key'] = $key;
                    $response['data'] = $item;
                    $response['error'] = 0;

                    $response['msg'] = '菜单保存成功';
                } else {
                    $response['msg'] = '菜单保存失败';
                }
            }
        }
    }

    echo json_encode($response);
    exit;
}

if('remove' == $opera)
{
    $id = intval(getPOST('id'));
    $response = array('error'=>1, 'msg'=>'');

    $checkMenu = 'select `id` from '.$db->table('wx_menu').' where `id`='.$id;
    if($db->fetchOne($checkMenu))
    {
        $checkChild = 'select count(*) from '.$db->table('wx_menu').' where `parent_id`='.$id;
        if(!$db->fetchOne($checkChild))
        {
            $deleteMenu = 'delete from '.$db->table('wx_menu').' where `id`='.$id;

            if($db->delete($deleteMenu))
            {
                $response['msg'] = '删除菜单成功';
                $response['error'] = 0;
            } else {
                $response['msg'] = '删除菜单失败';
            }
        } else {
            $response['msg'] = '该菜单有子菜单不能删除';
        }
    } else {
        $response['msg'] = '参数错误';
    }

    echo json_encode($response);
    exit;
}

if('post' == $opera)
{
    $response = array('msg'=>'');
    $getMenu = 'select `id`,`name`,`key`,`type` from '.$db->table('wx_menu').' where `parent_id`=0';
    $menus = $db->fetchAll($getMenu);

    if(!$menus)
    {
        $response['msg'] = '尚未定义菜单';
    } else {
        //构造格式化数据
        $format = array();
        foreach($menus as $item)
        {
            $getChildren = 'select `name`,`type`,`key` from '.$db->table('wx_menu').' where `parent_id`='.$item['id'];
            $children = $db->fetchAll($getChildren);

            if($children)
            {
                $subButton = array();

                foreach($children as $button)
                {
                    if($button['type'] == 'click')
                    {
                        $subButton[] = array('type'=>$button['type'], 'name'=>urlencode($button['name']), 'key'=>urlencode($button['key']));
                    } else {
                        $subButton[] = array('type'=>$button['type'], 'name'=>urlencode($button['name']), 'url'=>urlencode($button['key']));
                    }

                }
                $format[] = array('name'=>urlencode($item['name']), 'sub_button'=>$subButton);
            } else {
                if($item['type'] == 'click')
                {
                    $format[] = array('type'=>$item['type'], 'name'=>urlencode($item['name']), 'key'=>urlencode($item['key']));
                } else {
                    $format[] = array('type'=>$item['type'], 'name'=>urlencode($item['name']), 'url'=>urlencode($item['key']));
                }
            }
        }

        //发送请求
        //1.获得access_token
        $access_token = get_access_token($config['appid'], $config['appsecret']);

        if($access_token)
        {
            //2.发送创建菜单请求
            $url = 'https://api.weixin.qq.com/cgi-bin/menu/create?access_token='.$access_token;

            $content = urldecode(json_encode(array('button'=>$format)));
            $response['content'] = $content;
            $data = post($url, $content, false);
            $response['data'] = $data;
            $data = json_decode($data);
            //3.判断状态码
            if($data->errcode == 0)
            {
                $response['msg'] = '发布菜单成功，24小时之内将可在微信上看到结果';
            } else {
                $response['msg'] = $data->errcode.':'.$data->errmsg;
            }
        } else {
            $response['msg'] = '获取access_token失败';
        }
    }
    echo json_encode($response);
    exit;
}

if('view' == $act)
{
    $getMenu = 'select `id`,`name`,`key`,`type`,`parent_id` from '.$db->table('wx_menu').' order by `path` ASC';
    $menu = $db->fetchAll($getMenu);

    if($menu == '')
    {
        $menu = array();
    }

    $wechat_menus = array();
    foreach($menu as $key=>$m)
    {
        $wechat_menus[$m['id']] = $m;
    }

    if(count($wechat_menus) == 0)
    {
        $wechat_menus = '';
    }

    $smarty->assign('wechat_menus', json_encode($wechat_menus));
}

$template .= $act.'.phtml';
$smarty->display($template);
