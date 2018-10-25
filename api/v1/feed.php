<?php
/**
 * Created by PhpStorm.
 * User: Winsen
 * Date: 2018/6/30
 * Time: 19:34
 */
include '../library/api.inc.php';
global $db, $log, $config, $current_user;

$operation = 'thumb_up|update_view_count';
$action = 'view|show|get_default';

$opera = check_action($operation, getPOST('opera'));
$act = check_action($action, getGET('act'));

$response = [
    'error' => -1,
    'message' => ''
];

//更新浏览次数
if('update_view_count' == $opera) {
    $id = intval(getPOST('id'));

    if($id <= 0) {
        throw new RestFulException('参数错误', 550);
    }

    if($db->upgrade('content', ['view_count' => ['exp', '`view_count`+1']], ['id' => $id]) !== false) {
        $response['error'] = 0;
        $response['message'] = '更新浏览次数成功';
    } else {
        $response['message'] = '更新浏览次数失败';
    }
}

//资讯点赞
if('thumb_up' == $opera) {
    $id = intval(getPOST('id'));

    if($id <= 0) {
        throw new RestFulException('参数错误', 550);
    }

    if(empty($current_user)) {
        throw new RestFulException('请先登录', 503);
    }

    $thumb_up_record = $db->find('content_up', ['account'], ['content_id' => $id, 'account' => $current_user['account']]);

    if(empty($thumb_up_record)) {
        $data = [
            'content_id' => $id,
            'account' => $current_user['account'],
            'add_time' => time()
        ];

        if($db->create('content_up', $data)) {
            $db->upgrade('content', ['thumb_up' => ['exp', '`thumb_up`+1']], ['id' => $id]);
            $response['error'] = 0;
            $response['message'] = '点赞成功';
        } else {
            $response['message'] = '点赞失败';
        }
    } else {
        if($db->destroy('content_up', ['content_id' => $id, 'account' => $current_user['account']]) !== false) {
            $db->upgrade('content', ['thumb_up' => ['exp', '`thumb_up`-1']], ['id' => $id]);
            $response['error'] = 0;
            $response['message'] = '取消点赞成功';
        } else {
            $response['message'] = '取消点赞失败';
        }
    }
}

//资讯详情
if($act == 'show') {
    $id = intval(getGET('id'));

    $columns = [
        'id',
        'author',
        'title',
        'description',
        'wap_content',
        'view_count',
        'thumb_up',
        'add_time',
        'comment_count'
    ];

    $feed = $db->find('content', $columns, ['id' => $id]);

    if(!empty($feed)) {
        $feed['desc'] = $feed['description'];
        unset($feed['description']);

        $feed['content'] = $feed['wap_content'];
        unset($feed['wap_content']);

        $feed['thumb_up'] = intval($feed['thumb_up']);

        $feed['add_time'] = date('c', $feed['add_time']);

        if(empty($current_user)) {
            $feed['self_thumb_up'] = false;
        } else {
            //检查是否点赞
            $self_thumb_up = $db->getColumn('content_up', 'account', ['content_id' => $id, 'account' => $current_user['account']]);

            if ($self_thumb_up) {
                $feed['self_thumb_up'] = true;
            } else {
                $feed['self_thumb_up'] = false;
            }
        }

        //获取评论
        $get_comments = 'select c.`id`,c.`top`,c.`thumb_up`,c.`comment`,c.`parent_id`,m.`headimg`,m.`nickname` from '.
                        $db->table('content_comment').' as c left join '.$db->table('member').' as m '.
                        'using(`account`) where c.`content_id`='.$id.' and c.`status`=1 order by c.`path` ASC,c.`top` DESC,c.`add_time` DESC';

        $comments = $db->fetchAll($get_comments);

        $feed['comments'] = [];
        if($comments) {
            foreach($comments as $comment) {
                $self_thumb_up = null;

                if(!empty($current_user)) {
                    //检查评论点赞情况
                    $self_thumb_up = $db->getColumn('content_comment_up', 'account', [
                        'content_comment_id' => $comment['id'],
                        'account' => $current_user['account']
                    ]);
                }

                if($comment['parent_id'] == 0) {
                    array_push($feed['comments'], [
                        'id' => $comment['id'],
                        'avatar' => $comment['headimg'],
                        'nickname' => $comment['nickname'],
                        'top' => $comment['top'] == 1,
                        'thumb_up' => intval($comment['thumb_up']),
                        'comment' => $comment['comment'],
                        'self_thumb_up' => !empty($self_thumb_up),
                        'has_reply' => false,
                        'reply' => null
                    ]);
                } else {
                    $feed['comments'][count($feed['comments']) - 1]['has_reply'] = true;
                    $feed['comments'][count($feed['comments']) - 1]['reply'] = [
                        'id' => $comment['id'],
                        'content' => $comment['comment'],
                        'thumb_up' => intval($comment['thumb_up']),
                        'self_thumb_up' => !empty($self_thumb_up)
                    ];
                }
            }

            usort($feed['comments'], function($a, $b) {
               return $a['top'] == 0;
            });
        }
    }

    $response['error'] = 0;
    $response['feed'] = $feed;
}

//资讯列表
if($act == 'view') {
    $page = intval(getGET('page'));
    $page = max($page, 1);

    $size = 2;
    $limit = ($page - 1) * $size . ',' . $size;

    $feeds = $db->all('content', ['id', 'title', 'original', 'add_time', 'description'], ['section_id' => 1], $limit, [['add_time', 'DESC']]);

    $response['feeds'] = [];

    if ($feeds) {
        foreach($feeds as $feed) {
            array_push($response['feeds'], [
                'id' => intval($feed['id']),
                'title' => $feed['title'],
                'cover' => $feed['original'],
                'desc' => $feed['description'],
                'publish_time' => date('c', $feed['add_time'])
            ]);
        }
    }

    $response['error'] = 0;
}

echo json_encode($response);