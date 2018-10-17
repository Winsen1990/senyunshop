<?php
/**
 * Created by PhpStorm.
 * User: apple
 * Date: 15/10/26
 * Time: 上午10:44
 */
include 'library/init.inc.php';
back_base_init();

$template = 'wechat_kf/';
assign('subTitle', '微信客服管理');

$action = 'view|edit|delete|add';
$operation = 'add|edit';

$act = check_action($action, getGET('act'));
$act = ( $act == '' ) ? 'view' : $act;

$opera = check_action($operation, getPOST('opera'));

if('edit' == $opera)
{
    $response = array('error'=>1, 'msg'=>'', 'errmsg'=>array());

    $kf_account = trim(getPOST('kf_account'));
    $password = trim(getPOST('password'));
    $nickname = trim(getPOST('nickname'));

    if($kf_account == '')
    {
        $response['errmsg']['kf_account'] = '-请输入客服账号';
    } else {
        $kf_account = $db->escape($kf_account);
    }

    if($nickname == '')
    {
        $response['errmsg']['nickname'] = '-请输入客服昵称';
    } else {
        $nickname = $db->escape($nickname);
    }

    if($password == '')
    {
        $get_password = 'select `password` from '.$db->table('wx_kf').' where `kf_account`=\''.$kf_account.'\'';
        $password = $db->fetchOne($get_password);
    } else {
        $password = md5($password);
    }

    if(count($response['errmsg']) == 0)
    {
        $kf_data = array(
            'kf_account' => $kf_account,
            'password' => $password,
            'nickname' => $nickname
        );

        $access_token = get_access_token($config['appid'], $config['appsecret']);

        if($access_token)
        {
            $url = 'https://api.weixin.qq.com/customservice/kfaccount/update?access_token=' . $access_token;

            $res = post($url, json_encode($kf_data), false);
            if($res)
            {
                $res = json_decode($res);
                if($res->errcode == 0)
                {
                    $db->autoUpdate('wx_kf', $kf_data, '`kf_account`=\''.$kf_account.'\'');
                    $response['error'] = 0;
                    $response['msg'] = '修改客服成功';
                } else {
                    $response['msg'] = '系统繁忙，请稍后再试'.$res->errcode;
                }
            } else {
                $response['msg'] = '通信失败';
            }
        } else {
            $response['msg'] = '获取access_token失败';
        }
    }

    echo json_encode($response);
    exit;
}

if('add' == $opera)
{
    $response = array('error'=>1, 'msg'=>'', 'errmsg'=>array());

    $kf_account = trim(getPOST('kf_account'));
    $password = trim(getPOST('password'));
    $nickname = trim(getPOST('nickname'));

    if($kf_account == '')
    {
        $response['errmsg']['kf_account'] = '-请输入客服账号';
    } else {
        $kf_account = $db->escape($kf_account);
        //检查是否重名
        $check_id = 'select `id` from '.$db->table('wx_kf').' where `kf_account`=\''.$kf_account.'\'';
        if($db->fetchOne($check_id))
        {
            $response['errmsg']['kf_account'] = '-该账号已被使用';
        }
    }

    if($nickname == '')
    {
        $response['errmsg']['nickname'] = '-请输入客服昵称';
    } else {
        $nickname = $db->escape($nickname);
    }

    if($password == '')
    {
        $response['errmsg']['password'] = '-请输入客服密码';
    } else {
        $password = md5($password);
    }

    if(count($response['errmsg']) == 0)
    {
        $kf_data = array(
            'kf_account' => $kf_account.'@'.$config['wechat_account'],
            'password' => $password,
            'nickname' => $nickname
        );

        $access_token = get_access_token($config['appid'], $config['appsecret']);

        if($access_token)
        {
            $url = 'https://api.weixin.qq.com/customservice/kfaccount/add?access_token=' . $access_token;

            $res = post($url, json_encode($kf_data), false);
            if($res)
            {
                $res = json_decode($res, true);
                if($res['errcode'] == 0)
                {
                    $db->autoInsert('wx_kf', array($kf_data));
                    $response['error'] = 0;
                    $response['msg'] = '添加客服成功';
                } else {
                    $log->record_array($res);
                    $response['msg'] = '系统繁忙，请稍后再试';
                }
            } else {
                $response['msg'] = '通信失败';
            }
        } else {
            $response['msg'] = '获取access_token失败';
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

    $get_kf = 'select * from '.$db->table('wx_kf').' where `id`='.$id;
    $kf = $db->fetchRow($get_kf);
    assign('kf', $kf);
}

if('add' == $act)
{

}

if('view' == $act)
{
    $get_kf_list = 'select `id`,`kf_account`,`nickname` from '.$db->table('wx_kf');
    $kf_list = $db->fetchAll($get_kf_list);
    assign('kf_list', $kf_list);
}
$template .= $act.'.phtml';
$smarty->display($template);