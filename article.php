<?php
/**
 * Created by PhpStorm.
 * User: apple
 * Date: 15/10/10
 * Time: 上午10:06
 */
include 'library/init.inc.php';
$id = intval(getGET('id'));

if($id <= 0)
{
    redirect('index.php');
}

$get_article = 'select `id`,`description`,`original`, `wap_content`,`title`,`add_time`,`author`,`section_id` from '.$db->table('content').' where `id`='.$id;
$article = $db->fetchRow($get_article);

assign('article', $article);

$get_section_name = 'select `section_name` from '.$db->table('section').' where `id`='.$article['section_id'];
assign('section_name', $db->fetchOne($get_section_name));

//生成资讯推广链接
$param = array('url'=>'article.php?id='.$article['id'], 'opera'=>'get_url', 'account'=>$_SESSION['account']);
$get_url_response = post('http://'.$_SERVER['HTTP_HOST'].'/'.BASE_DIR.'d/index.php', $param);

$get_url_response = json_decode($get_url_response);
if($get_url_response->error == 0)
{
    assign('recommend_url', $get_url_response->url);
} else {
    assign('recommend_url', 'http://'.$_SERVER['HTTP_HOST'].'/article.php?id='.$id);
}

//若是推广，生成关注二维码
$member_id = isset($_SESSION['parent_id']) ? $_SESSION['parent_id'] : 0;

assign('qrcode', $config['qr_code']);
if( $member_id > 0 )
{
    $get_user_info = 'select * from '.$db->table('member').' where `id`=\''.$member_id.'\'';
    $user_info = $db->fetchRow($get_user_info);
    assign('user_info', $user_info);

    if($user_info['expired'] <= time())
    {
        $access_token = get_access_token($config['appid'], $config['appsecret']);
        $qrcode = get_qrcode($user_info['openid'], $access_token);

        if ($qrcode) {
            assign('qrcode', $qrcode);
        }
    }
}

assign('mobile_agent', is_mobile_agent());
$smarty->display('news-detail.phtml');