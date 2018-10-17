<?php
/**
 * Created by PhpStorm.
 * User: apple
 * Date: 15/9/23
 * Time: 下午3:07
 */
include 'library/init.inc.php';

global $log, $config, $db, $smarty;

$operation = 'get_fav|verify_pic_code|verify_message_code|get_message_code|verify_mobile|login|register|get_qrcode';
$opera = check_action($operation, getPOST('opera'));

$response = array('error'=>1, 'msg'=>'');

if($opera == 'get_qrcode')
{
    $response = array('error'=>1, 'msg'=>'');

    if(!check_cross_domain())
    {
        $url = getPOST('url');
        $account = getPOST('account');

        if($url == '')
        {
            $response['msg'] = '参数为空';
        } else {
            if(!empty($_SESSION['account']))
            {
                $get_member_id = 'select `id` from '.$db->table('member').' where `account`=\''.$_SESSION['account'].'\'';
                $member_id = $db->fetchOne($get_member_id);
                //获取链接的二维码
                $param = array('url'=>$url, 'opera'=>'get_url', 'account'=>$_SESSION['account']);
                $get_url_response = post('http://'.$_SERVER['HTTP_HOST'].'/'.BASE_DIR.'d/index.php', $param);

                $get_url_response = json_decode($get_url_response);
                if($get_url_response->error == 0)
                {
                    $loader->includeClass('Qrcode');

                    $save_file = 'upload/qrcode/'.$member_id.'.png';
                    if(QRcode::png($get_url_response->url, $save_file, QR_ECLEVEL_M, 6))
                    {
                        $response['error'] = 0;
                        $response['url'] = $save_file;
                    } else {
                        $response['msg'] = '系统繁忙，请稍后再试';
                    }
                } else {
                    $response['msg'] = $get_url_response->msg;
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

if('login' == $opera)
{
    $account = getPOST('account');
    $password = getPOST('password');
    $code = getPOST('code');
    $code = strtolower($code);
    $ref = getPOST('ref');

    $column = '`account`';

    if(!isset($_SESSION['code']) || $code != $_SESSION['code'])
    {
        $response['msg'] .= '-图片验证码错误<br/>';
    }

    if($account == '')
    {
        $response['msg'] .= '-请填写手机号码/会员卡号<br/>';
    } else {
        $account = $db->escape($account);
        if(is_mobile($account))
        {
            $column = '`mobile`';
        }
    }

    if($password == '')
    {
        $response['msg'] .= '-请填写密码<br/>';
    } else {
        $password = md5($password.PASSWORD_END);
    }

    if($response['msg'] == '')
    {
        $get_user_info = 'select `password`,`account`,`openid` from '.$db->table('member').' where '.$column.'=\''.$account.'\'';

        $user_info = $db->fetchRow($get_user_info);

        if($user_info['password'] == $password)
        {
            $_SESSION['account'] = $user_info['account'];
            $_SESSION['openid'] = $user_info['openid'];
            $response['error'] = 0;
            $response['msg'] = '登录成功';

            if(isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'] ,'login.php') === false)
            {
                $response['referer'] = $_SERVER['HTTP_REFERER'];
            } else {
                $response['referer'] = 'index.php';
            }
            $response['referer'] = $ref;
        } else {
            $response['msg'] = '账号信息错误';
        }
    }
}

if('verify_mobile' == $opera)
{
    $mobile = getPOST('mobile');
    $mode = getPOST('mode');

    if(is_mobile($mobile))
    {
        if($mode == 'unique')
        {
            $mobile = $db->escape($mobile);
            $check_mobile = 'select `mobile` from '.$db->table('member').' where `mobile`=\''.$mobile.'\' and `account`<>\''.$_SESSION['account'].'\'';

            $flag = $db->fetchOne($check_mobile);

            if($flag)
            {
                $response['msg'] = '手机号码已被使用';
            } else {
                $response['error'] = 0;
                $response['mobile'] = $mobile;
                $response['mobile_mask'] = substr($mobile, 0, 3) . '****' . substr($mobile, -4);
            }
        } else {
            $response['error'] = 0;
            $response['mobile'] = $mobile;
            $response['mobile_mask'] = substr($mobile, 0, 3) . '****' . substr($mobile, -4);
        }
    } else {
        $response['msg'] = '手机号码格式不正确';
    }
}

if('verify_message_code' == $opera)
{
    $mobile = getPOST('mobile');
    $code = getPOST('code');

    if(is_mobile($mobile) && isset($_SESSION['token']))
    {
        $mobile = $db->escape($mobile);
        //检查验证码池是否存在该手机
        $check_code = 'select `code`,`expire` from '.$db->table('message_code').' where `mobile`=\''.$mobile.'\'';
        $message_code = $db->fetchRow($check_code);

        if($code != $message_code['code'])
        {
            $response['msg'] = '短信验证码错误';
        } else {
            $response['msg'] = '验证码校验通过';
            $response['error'] = 0;
            $_SESSION['token'] = 'verify message code success.';
        }
    } else {
        $response['msg'] = '手机号码格式不正确';
    }
}

if('get_message_code' == $opera)
{
    $mobile = getPOST('mobile');

    if(is_mobile($mobile) && isset($_SESSION['token']))
    {
        $mobile_code = rand(100000, 999999);

        $mobile = $db->escape($mobile);
        //检查验证码池是否存在该手机
        $check_code = 'select `code`,`expire` from '.$db->table('message_code').' where `mobile`=\''.$mobile.'\'';
        $message_code = $db->fetchRow($check_code);

        if($message_code && $message_code['expire'] >= time())
        {
            //验证码存在且未过期
            $response['msg'] = '请不要重复获取验证码';
            $response['timer'] = $message_code['expire'] - time();
            $_SESSION['mobile_code'] = $message_code['code'];
        } else {
            if(sendSMS($mobile, '您的手机验证码为:'.$mobile_code))
            {
                //验证码发送成功
                if($message_code)
                {
                    $data = array(
                        'code' => $mobile_code,
                        'expire' => time()+60
                    );

                    if($db->autoUpdate('message_code', $data))
                    {
                        $response['error'] = 0;
                        $response['timer'] = 60;
                        $_SESSION['mobile_code'] = $mobile_code;
                        $response['code'] = '';
                        $_SESSION['token'] = 'send message code success.';
                    } else {
                        $response['msg'] = '001:参数错误';
                    }
                } else {
                    $data = array(
                        'code' => $mobile_code,
                        'expire' => time()+60,
                        'mobile' => $mobile
                    );

                    if($db->autoInsert('message_code', array($data)))
                    {
                        $response['error'] = 0;
                        $response['timer'] = 60;
                        $_SESSION['mobile_code'] = $mobile_code;
                        $response['code'] = $mobile_code;
                        $_SESSION['token'] = 'send message code success.';
                    } else {
                        $response['msg'] = '002:参数错误';
                    }
                }
            } else {
                $response['msg'] = '获取验证码失败,系统繁忙,请稍后再试';
            }
        }
    } else {
        $response['msg'] = '手机号码格式不正确';
    }
}

if('verify_pic_code' == $opera)
{
    $code = getPOST('code');
    $code = strtolower($code);

    if($code != $_SESSION['code'])
    {
        $response['msg'] = '图片验证码错误';
    } else {
        $response['error'] = 0;
    }
}

if('get_fav' == $opera)
{
    $now = time();
    $page = intval(getPOST('page'));
    $page = ( $page <= 0 ) ? 1 : $page;
    $page_count = intval(getPOST('count'));
    $page_count = $page_count <= 0 ? 3 : $page_count;
    $offset = ($page - 1) * $page_count;

    $get_total = 'select count(*) from '.$db->table('product').' where status = 4';
    $total = $db->fetchOne($get_total);
    $total_page = ceil($total / $page_count);
    if( $page > $total_page ) {
        echo json_encode($response);
        exit;
    }

    $get_fav_products = 'select `given_integral`,`shop_price`,`name`,if(`promote_end`>'.$now.',`promote_price`,`price`) as `price`,`img`,`id` from '.$db->table('product').
                        ' where `status`=4 '.
                        'order by `order_view` ASC limit '.$offset.','.$page_count;
    $fav_products = $db->fetchAll($get_fav_products);
    assign('product_list', $fav_products);

    $response['error'] = 0;
    $response['page'] = $page + 1;
    $response['content'] = $smarty->fetch('product-item.phtml');
}

echo json_encode($response);
exit;