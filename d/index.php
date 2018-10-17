<?php
/**
 * Created by PhpStorm.
 * User: apple
 * Date: 15/9/16
 * Time: 下午2:04
 */
include 'library/init.inc.php';

$code = getGET('code');
$log->record_array($_GET);
if($code != '')
{
    $code = $db->escape($code);
    $url = $db->fetchOne('select `url` from '.$db->table('short_link').' where `hash`=\''.$code.'\'');
    $log->record($url.','.$_SERVER['REQUEST_URI']);
    $url = 'http://'.$_SERVER['HTTP_HOST'].preg_replace('/d\/[a-zA-Z].*$/', $url, $_SERVER['REQUEST_URI']);

    $log->record('target url:'.$url);
    if(is_weixin() && $_SESSION['openid'] == '')
    {
        $oathor_url = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=%s&redirect_uri=%s&response_type=code&scope=snsapi_userinfo&state=2048#wechat_redirect';
        $url = sprintf($oathor_url, $config['appid'], urlencode($url));
        redirect($url);
    } else {
        redirect($url);
    }
    exit;
}

$opera = getPOST('opera');
if($opera == 'get_url')
{
    $response = array('error'=>1, 'msg'=>'');

    if(true || !check_cross_domain())
    {
        $url = getPOST('url');
        $account = getPOST('account');

        if($url == '')
        {
            $response['msg'] = '参数为空';
        } else {
            if(!empty($_SESSION['account']))
            {
                $account = $_SESSION['account'];
            } else {
                $account = $db->escape($account);
            }

            if(!empty($account))
            {
                $get_member_id = 'select `id` from '.$db->table('member').' where `account`=\''.$account.'\'';
                $member_id = $db->fetchOne($get_member_id);
                //检查url是否带有用户参数
                if(strpos($url, '?') === false)
                {
                    $url .= '?ukey='.$member_id;
                } else {
                    $url .= '&ukey='.$member_id;
                }

                //检查连接是否已存在
                $url = $db->escape($url);
                $check_short_link = 'select `hash` from ' . $db->table('short_link') . ' where `url`=\'' . $url . '\'';

                if ($hash = $db->fetchOne($check_short_link))
                {
                    $response['url'] = 'http://'.$_SERVER['HTTP_HOST'].str_replace('index.php', 'w'.$hash, $_SERVER['REQUEST_URI']);
                    $response['error'] = 0;
                } else {
                    $hash = get_hash_map();

                    $data = array(
                        'url' => $url,
                        'hash' => $hash
                    );

                    if ($db->autoInsert('short_link', array($data)))
                    {
                        $response['url'] = 'http://'.$_SERVER['HTTP_HOST'].str_replace('index.php', 'w'.$hash, $_SERVER['REQUEST_URI']);
                        $response['error'] = 0;
                    } else {
                        $response['msg'] = '生成短连接失败';
                    }
                }
            } else {
                $response['msg'] = '请先登录';
            }
        }
    } else {
        $response['msg'] = '404:参数错误';
    }

    echo json_encode($response);
    exit;
}

function get_hash_map()
{
    global $db;

    $seed = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

    $strlen = strlen($seed)-1;
    $hashlen = 5;

    $hash = '';

    do
    {
        $count = $hashlen;
        while($count--)
        {
            $index = rand(0, $strlen);
            $hash .= $seed[$index];
        }

        $check_hash = 'select `url` from '.$db->table('short_link').' where `hash`=\''.$hash.'\'';
    } while($db->fetchOne($check_hash));

    return $hash;
}
