<?php
/**
 * Created by PhpStorm.
 * User: apple
 * Date: 15/10/26
 * Time: 上午10:44
 */
include 'library/init.inc.php';
back_base_init();

$template = 'wechat_rule/';
assign('subTitle', '微信回复规则管理');

$action = 'view|add|edit|delete';
$operation = 'add|edit|remove|get_content';

$act = check_action($action, getGET('act'));
$act = ( $act == '' ) ? 'view' : $act;

$msgType_array = array(
    'text' => '文本',
    'news' => '图文',
);
assign('msgType', $msgType_array);

$opera = check_action($operation, getPOST('opera'));

if( 'get_content' == $opera ) {
    $response = array('error'=>1, 'msg'=>'', 'errmsg'=>array());

    $id = intval(getPOST('id'));
    if( 0 >= $id ) {
        $response['msg'] = '参数错误';
        echo json_encode($response);
        exit;
    }

    $get_content = 'select `title`, `description`, `original`, `id` from '.$db->table('content').' where id = \''.$id.'\' limit 1';
    $content = $db->fetchRow($get_content);
    if( $content ) {
        $content['time'] = date('Y年m月');
        $content['thumb'] = $content['original'];
        $content['url'] = '/content.php?id='.$content['id'];
        $response['content'] = $content;
        $response['error'] = 0;
    } else {
        $response['msg'] = $get_content;
    }
    echo json_encode($response);
    exit;
}

if('edit' == $opera) {
    $response = array('error'=>1, 'msg'=>'', 'errmsg'=>array());
    $eid = intval(getPOST('eid'));
    $rule = trim(getPOST('rule'));
    $response_content = trim(getPOST('response_content'));
    $name = trim(getPOST('name'));
    $order_view = intval(getPOST('order_view'));
    $enabled = intval(getPOST('enabled'));
    $match_mode = intval(getPOST('match_mode'));

    $msgType = trim(getPOST('msgType'));
    $content_id = intval(getPOST('content_id'));

    if($eid == '')
    {
        $response['msg'] = '参数错误';
    }

    if($rule == '')
    {
        $response['errmsg']['rule'] = '-请填写关键词';
    } else {
        $rule = $db->escape($rule);
    }

    if( $msgType == '' ) {
        $response['errmsg']['msgType'] = '-请选择回复类型';
    } else if( !array_key_exists($msgType, $msgType_array ) ) {
        $response['errmsg']['msgType'] = '-请选择回复类型';
    } else {
        $msgType = $db->escape($msgType);
        if( $msgType == 'news' ) {
            if( $content_id <= 0 ) {
                $content_id = 0;
            }
            if( $content_id != 0 ) {
                $check_content = 'select * from '.$db->table('content').' where id = \''.$content_id.'\' limit 1';
                $content = $db->fetchRow($check_content);
                if( empty($content) ) {
                    $content_id = 0;
                }
            }
        }
    }
    if( $msgType == 'text' ) {
        if ($response_content == '') {
            $response['errmsg']['rule'] = '-请填写回复内容';
        } else {
            $response_content = $db->escape($response_content);
        }
    }

    if($name == '')
    {
        $response['errmsg']['name'] = '-请填写规则名称';
    } else {
        $name = $db->escape($name);
    }

    if($order_view <= 0)
    {
        $order_view = 50;
    }

    if(count($response['errmsg']) == 0 && $response['msg'] == '')
    {
        $db->begin();
        $no_need = false;
        if( $msgType == 'text' ) {
            $response_data = array(
                'content' => $response_content,
                'msgType' => 'text',
                'title' => '',
                'description' => '',
                'picUrl' => '',
                'url' => '',
            );
        } elseif( $msgType == 'news' ) {
            if( $content_id != 0 ) {
                $content_arr = array($content['wap_content']);
                $title_arr = array($content['title']);
                $description_arr = array($content['description']);
                $picUrl_arr = array($content['original']);
                $url_arr = array('http://'.$_SERVER['HTTP_HOST'].'/article.php?id='.$content['id']);

                $response_data = array(
                    'msgType' => 'news',
                    'content' => serialize($content_arr),
                    'title' => serialize($title_arr),
                    'description' => serialize($description_arr),
                    'picUrl' => serialize($picUrl_arr),
                    'url' => serialize($url_arr),
                );
            } else {
                $no_need = true;
            }

        }

        $get_response_id = 'select `response_id` from '.$db->table('wx_rule').' where `id`='.$eid;
        $response_id = $db->fetchOne($get_response_id);

        if( $no_need || $db->autoUpdate('wx_response', $response_data, '`id`='.$response_id) !== false)
        {
            $rule_data = array(
                'match_mode' => $match_mode,
                'enabled' => $enabled,
                'order_view' => $order_view,
                'rule' => $rule,
                'name' => $name
            );

            if($db->autoUpdate('wx_rule', $rule_data, '`id`='.$eid) !== false)
            {
                $db->commit();
                $response['error'] = 0;
                $response['msg'] = '修改规则成功';
            } else {
                $db->rollback();
                $response['msg'] = '修改规则失败，请稍后再试';
            }
        } else {
            $response['msg'] = '系统繁忙，请稍后再试';
            $db->rollback();
        }
    }

    echo json_encode($response);
    exit;
}

if('add' == $opera)
{
    $response = array('error'=>1, 'msg'=>'', 'errmsg'=>array());

    $rule = trim(getPOST('rule'));
    $response_content = trim(getPOST('response_content'));
    $name = trim(getPOST('name'));
    $order_view = intval(getPOST('order_view'));
    $enabled = intval(getPOST('enabled'));
    $match_mode = intval(getPOST('match_mode'));

    $msgType = trim(getPOST('msgType'));
    $content_id = intval(getPOST('content_id'));
    $content = null;

    if($rule == '')
    {
        $response['errmsg']['rule'] = '-请填写关键词';
    } else {
        $rule = $db->escape($rule);
    }

    if( $msgType == '' ) {
        $response['errmsg']['msgType'] = '-请选择回复类型';
    } else if( !array_key_exists($msgType, $msgType_array ) ) {
        $response['errmsg']['msgType'] = '-请选择回复类型';
    } else {
        $msgType = $db->escape($msgType);
        if( $msgType == 'news' ) {
            if( $content_id <= 0 ) {
                $response['errmsg']['content_id'] = '-请选择资讯';
            } else {
                $check_content = 'select * from '.$db->table('content').' where id = \''.$content_id.'\' limit 1';
                $content = $db->fetchRow($check_content);
                if( empty($content) ) {
                    $response['errmsg']['content_id'] = '-请选择资讯';
                }
            }
        }
    }
    if( $msgType == 'text' ) {
        if ($response_content == '') {
            $response['errmsg']['rule'] = '-请填写回复内容';
        } else {
            $response_content = $db->escape($response_content);
        }
    }

    if($name == '')
    {
        $response['errmsg']['name'] = '-请填写规则名称';
    } else {
        $name = $db->escape($name);
    }

    if($order_view <= 0)
    {
        $order_view = 50;
    }

    if(count($response['errmsg']) == 0)
    {
        if($content)
        {
            $content_arr = array($content['wap_content']);
            $title_arr = array($content['title']);
            $description_arr = array($content['description']);
            $picUrl_arr = array($content['original']);
            $url_arr = array('http://' . $_SERVER['HTTP_HOST'] . '/article.php?id=' . $content['id']);
        }

        $db->begin();
        if( $msgType == 'text' ) {
            $response_data = array(
                'msgType' => 'text',
                'content' => $response_content
            );
        } elseif( $msgType == 'news' ) {
            $response_data = array(
                'msgType' => 'news',
                'content' => serialize($content_arr),
                'title' => serialize($title_arr),
                'description' => serialize($description_arr),
                'picUrl' => serialize($picUrl_arr),
                'url' => serialize($url_arr),
            );
        }

        if($db->autoInsert('wx_response', array($response_data)))
        {
            $response_id = $db->get_last_id();

            $rule_data = array(
                'response_id' => $response_id,
                'match_mode' => $match_mode,
                'enabled' => $enabled,
                'order_view' => $order_view,
                'rule' => $rule,
                'name' => $name
            );

            if($db->autoInsert('wx_rule', array($rule_data)))
            {
                $db->commit();
                $response['error'] = 0;
                $response['msg'] = '新增规则成功';
                $response['msgType'] = $msgType;
            } else {
                $db->rollback();
                $response['msg'] = '新增规则失败，请稍后再试';
            }
        } else {
            $response['msg'] = '系统繁忙，请稍后再试';
            $db->rollback();
        }
    }

    echo json_encode($response);
    exit;
}

if('edit' == $act)
{
    $id = intval(getGET('id'));

    if($id <= 0)
    {
        show_system_message('参数错误');
    }

    $get_rule = 'select r.*,res.content,res.msgType,res.picUrl,res.url,res.description,res.title from '.$db->table('wx_rule').' as r join '.$db->table('wx_response').' as res on r.response_id=res.id where r.`id`='.$id;
    $rule = $db->fetchRow($get_rule);

    if($rule['msgType'] == 'news')
    {
        $rule['content'] = unserialize($rule['content']);
        $rule['title'] = unserialize($rule['title']);
        $rule['description'] = unserialize($rule['description']);
        $rule['picUrl'] = unserialize($rule['picUrl']);
        $rule['url'] = unserialize($rule['url']);
    }

    assign('rule', $rule);

    //获取资讯标题
    $get_content_list = 'select `title`, `id` from '.$db->table('content').' where status = 1 order by id desc';
    $content_list = $db->fetchAll($get_content_list);
    assign('content_list', $content_list);
}

if('view' == $act)
{
    $where = '';

    $keyword = trim(getGET('keyword'));
    if( '' != $keyword ) {
        $keyword = $db->escape($keyword);
        $where .= ' and `name` like \'%'.$keyword.'%\'';
    }

    $count = intval(getGET('count'));
    $count_array = array(10, 25, 50 , 100);
    $count = ( in_array($count, $count_array) ) ? $count : 10;

    $get_total = 'select count(*) from '.$db->table('wx_rule').' as a where 1 ';
    $get_total .= $where;

    $total = $db->fetchOne($get_total);
    $total_page = ceil( $total / $count );

    $page = intval(getGET('page'));
    $page = ( $page > $total_page ) ? $total_page : $page;
    $page = ( 0 >= $page ) ? 1 : $page;

    create_pager($page, $total_page, $total);
    assign('count', $count);
    assign('keyword', $keyword);

    $offset = ($page - 1) * $count;

    $get_rule_list = 'select * from '.$db->table('wx_rule').' where 1 ';
    $get_rule_list .= $where;
    $get_rule_list .= ' order by order_view asc, id desc';
    $get_rule_list .= ' limit '.$offset.','.$count;
    $rule_list = $db->fetchAll($get_rule_list);

    assign('rule_list', $rule_list);
}

if( 'add' == $act ) {
    //获取资讯
    $get_content_list = 'select `title`, `id` from '.$db->table('content').' where status = 1 order by id desc';
    $content_list = $db->fetchAll($get_content_list);
    assign('content_list', $content_list);
}

if('delete' == $act) {
    $id = intval(getGET('id'));

    if($id <= 0)
    {
        show_system_message('参数错误');
    }

    $get_response_id = 'select `response_id` from '.$db->table('wx_rule').' where `id`='.$id;
    $response_id = $db->fetchOne($get_response_id);

    $db->autoDelete('wx_rule', '`id`='.$id);
    $db->autoDelete('wx_response', '`id`='.$response_id);

    show_system_message('删除回复规则成功');
}

$template .= $act.'.phtml';
$smarty->display($template);