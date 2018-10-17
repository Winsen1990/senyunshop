<?php
include('library/init.inc.php');

global $log, $config, $db, $loader;
//接收信息
$xml = file_get_contents('php://input');
$log->record_array($_GET);
$log->record($xml);
$data = simplexml_load_string($xml);
$temp = '';

//检查请求是否来自微信服务器
if(isset($data->ToUserName))
{
    $public_account = $data->ToUserName;//公众号原始ID

    if($public_account != $config['public_account'])
    {
        echo '目标服务号不存在';
        exit;
    }

    $token = $config['token'];//公众号token

    if(!check_signature($_GET['signature'], $_GET['timestamp'], $_GET['nonce'], $token))
    {
        echo '请求服务器错误';
        exit;
    }
}

$openid = isset($data->FromUserName) ? $data->FromUserName:'';
$openid = $db->escape($openid);

$public_account = isset($data->ToUserName) ? $data->ToUserName:'';
$public_account = $db->escape($public_account);
//处理请求信息
$response_id = array();
$response = '';
$msg_type = '';
if(isset($data->MsgType))
{
    $msg_type = strtolower($data->MsgType);
}
switch($msg_type)
{
//文本消息
    case 'text':
        $get_rules  = 'select `response_id`,`rule`,`match_mode` from '.$db->table('wx_rule').'';
        $get_rules .= ' where `enabled`=1 order by `order_view`';

        $log->record('text :'.$get_rules);
        $rules = $db->fetchAll($get_rules);
        foreach($rules as $rule)
        {
            if(1 == $rule['match_mode'])//精确匹配
            {
                $log->record('match rule '.$rule['rule'].':'.$data->Content);
                if($rule['rule'] == $data->Content)
                {
                    array_push($response_id, $rule['response_id']);
                }
            } else {//正则匹配
                $log->record('mass match rule '.$rule['rule'].':'.$data->Content);
                if(preg_match('/'.$rule['rule'].'/', $data->Content))
                {
                    array_push($response_id, $rule['response_id']);
                }
            }
        }

        if(empty($response_id))
        {
            $log->record('没有响应的文字规则'.$data->Content);
        }
        break;
//事件消息
    case 'event':
        switch(strtolower($data->Event))
        {
            /*
            <xml>
                <ToUserName><![CDATA[toUser]]></ToUserName>
                <FromUserName><![CDATA[FromUser]]></FromUserName>
                <CreateTime>123456789</CreateTime>
                <MsgType><![CDATA[event]]></MsgType>
                <Event><![CDATA[subscribe]]></Event>
                <EventKey><![CDATA[qrscene_123123]]></EventKey>
                <Ticket><![CDATA[TICKET]]></Ticket>
            </xml>
            */
            case 'subscribe'://关注事件
                //检查用户是否已存在
                $check_member = 'select `id` from '.$db->table('member').' where `openid`=\''.$openid.'\'';
                $user = $db->fetchRow($check_member);
                if(!$user)
                {
                    $scene_id = isset($data->EventKey) ? str_replace('qrscene_', '', $data->EventKey) : 0;
                    //用户不存在，新增用户
                    $post_data = array(
                        'openid' => $openid,
                        'timestamp' => time(),
                        'scene_id' => $scene_id
                    );
                    post('http://'.$_SERVER['HTTP_HOST'].'/api/new_user.php', $post_data);
                } else {
                    //取消用户取消关注时间
                    $update_member = 'update '.$db->table('member').' set `leave_time`=0 where `id`='.$user['id'];
                    $db->update($update_member);
                }


                $get_response_ids  = 'select `response_id` from '.$db->table('wx_rule').' where `rule`=\'subscribe\'';

                $response_ids = $db->fetchAll($get_response_ids);

                if($response_ids) {
                    foreach($response_ids as $_response) {
                        array_push($response_id, $_response['response_id']);
                    }
                }

                if(empty($response_id))
                {
                    //没有符合的规则
                    $log->record('没有关注回复事件，默认不处理');
                }
                break;
            case 'unsubscribe'://取消关注事件
                //设置取消关注时间
                $member_data = array(
                    'leave_time' => time()
                );

                $db->autoUpdate('member', $member_data, '`openid`=\''.$openid.'\'');
                break;
            case 'location'://上报地理位置
                /*
                <xml>
                    <ToUserName><![CDATA[gh_e415303998c0]]></ToUserName>
                    <FromUserName><![CDATA[oY8_kjpNBdSXBSij0C3Po11ZBolA]]></FromUserName>
                    <CreateTime>1414133877</CreateTime>
                    <MsgType><![CDATA[event]]></MsgType>
                    <Event><![CDATA[LOCATION]]></Event>
                    <Latitude>23.131355</Latitude>纬度
                    <Longitude>113.352715</Longitude>经度
                    <Precision>65.000000</Precision>
                </xml>
                */
                //更新用户的地理位置信息
                $member_data = array(
                    'longitude' => floatval($data->Longitude),
                    'latitude' => floatval($data->Latitude)
                );
                $db->autoUpdate('member', $member_data, '`openid`=\''.$openid.'\'');
                break;
            case 'scan'://已关注用户扫描推广二维码
                /*
                <xml>
                    <ToUserName><![CDATA[toUser]]></ToUserName>
                    <FromUserName><![CDATA[FromUser]]></FromUserName>
                    <CreateTime>123456789</CreateTime>
                    <MsgType><![CDATA[event]]></MsgType>
                    <Event><![CDATA[SCAN]]></Event>
                    <EventKey><![CDATA[SCENE_VALUE]]></EventKey>
                    <Ticket><![CDATA[TICKET]]></Ticket>
                </xml>
                 */
                //目前不做处理
                break;
            case 'click'://点击事件菜单
                /*
                <xml>
                    <ToUserName><![CDATA[toUser]]></ToUserName>
                    <FromUserName><![CDATA[FromUser]]></FromUserName>
                    <CreateTime>123456789</CreateTime>
                    <MsgType><![CDATA[event]]></MsgType>
                    <Event><![CDATA[CLICK]]></Event>
                    <EventKey><![CDATA[EVENTKEY]]></EventKey>
                </xml>
                 */
                $get_response_ids  = 'select `response_id` from '.$db->table('wx_rule').' where ';
                $get_response_ids .= ' `enabled`=1 and `rule`=\'click_'.$data->EventKey.'\'';
                $log->record('click event:'.$get_response_ids);

                $response_ids = $db->fetchAll($get_response_ids);
                if($response_ids) {
                    foreach($response_ids as $_response) {
                        array_push($response_id, $_response['response_id']);
                    }
                }

                if(empty($response_id))
                {
                    //没有符合的规则
                    $log->record('没有相应的事件'.$data->EventKey);
                }
                break;
            case 'view'://点击链接菜单
                /*
                <xml>
                    <ToUserName><![CDATA[toUser]]></ToUserName>
                    <FromUserName><![CDATA[FromUser]]></FromUserName>
                    <CreateTime>123456789</CreateTime>
                    <MsgType><![CDATA[event]]></MsgType>
                    <Event><![CDATA[VIEW]]></Event>
                    <EventKey><![CDATA[www.qq.com]]></EventKey>
                </xml>
                 */
                break;
        }
        break;
//图片消息
    case 'image':
//语音消息
    case 'voice':
//视频消息
    case 'video':
//地理位置消息
    case 'location':
//连接消息
    case 'link':
        break;
    default:
        echo htmlspecialchars($_GET['echostr']);
        exit;
}

//根据response_id进行响应
if(count($response_id))
{
    $id = array_shift($response_id);
    $get_response  = 'select `msgType`,`content`,`title`,`description`,`musicUrl`,`HQMusicUrl`,`url`,`picUrl`,`mediaId`,`thumbMediaId` from ';
    $get_response .= $db->table('wx_response').' where `id`='.$id;

    $response_rule = $db->fetchRow($get_response);

    switch($response_rule['msgType'])
    {
        case 'text':
            //文本信息回复
            $responseObj = new TextResponse($public_account, $openid, $response_rule['content']);
            $response = $responseObj->__toString();
            break;
        case 'news':
            //图文信息回复
            $title = unserialize($response_rule['title']);
            $description = unserialize($response_rule['description']);
            $picUrl = unserialize($response_rule['picUrl']);
            $url = unserialize($response_rule['url']);

            $items = array();
            foreach($title as $key=>$value)
            {
                $items[] = array(
                    'title' => $value,
                    'description' => $description[$key],
                    'picUrl' => $picUrl[$key],
                    'url' => $url[$key]
                );
            }

            $responseObj = new NewsResponse($public_account, $openid, $items);
            $response = $responseObj->__toString();
            break;
        case 'themes':
            //事件处理
            $content = $response_rule['content'];
            $log->record('themes :'.$content);

            $loader->includeClass($content);
            $log->record('load themes success');
            $responseObj = new $content($public_account, $openid, $data->Content);
            $log->record('init themes success');
            $responseObj->run();
            $log->record('themes run');
            $response = $responseObj->__toString();
            $log->record('themes toString');

            break;
    }
} else {
    if($msg_type != 'event') {
        //多客服
        $get_kf_account = 'SELECT k.`kf_account` FROM ' . $db->table('wx_kf') . ' AS k JOIN ' . $db->table('member') . ' AS m ON m.`kf_id`=k.`id`' .
            ' WHERE m.`openid`=\'' . $openid . '\'';
        $kf_account = $db->fetchOne($get_kf_account);
        $content = 'MultiServerTransfer';
        $responseObj = new $content($public_account, $openid, $kf_account);
        $response = $responseObj->__toString();
    }
}

$log->record('微信响应:'.$response);
echo $response;
flush();

while($id = array_shift($response_id)) {
    $get_response  = 'select `msgType`,`content`,`title`,`description`,`musicUrl`,`HQMusicUrl`,`url`,`picUrl`,`mediaId`,`thumbMediaId` from ';
    $get_response .= $db->table('wx_response').' where `id`='.$id;

    $response_rule = $db->fetchRow($get_response);

    switch($response_rule['msgType'])
    {
        case 'text':
            //文本信息回复
            send_customer_message($openid, 'text', $response_rule['content']);
            break;

        case 'news':
            //图文信息回复
            $title = unserialize($response_rule['title']);
            $description = unserialize($response_rule['description']);
            $picUrl = unserialize($response_rule['picUrl']);
            $url = unserialize($response_rule['url']);

            $items = array();
            foreach($title as $key=>$value)
            {
                $items[] = array(
                    'title' => $value,
                    'description' => $description[$key],
                    'picUrl' => $picUrl[$key],
                    'url' => $url[$key]
                );
            }

            send_customer_message($openid, 'news', $items);
            break;

        case 'themes':
            //事件处理
            $content = $response_rule['content'];

            $loader->includeClass($content);
            $responseObj = new $content($public_account, $openid, $data->Content);
            $responseObj->run();
            $responseObj->send();
            break;
    }
}
