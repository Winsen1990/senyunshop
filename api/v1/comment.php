<?php
/**
 * Created by PhpStorm.
 * User: pengyuansen
 * Date: 2018/6/25
 * Time: 13:45
 */
include '../library/api.inc.php';
global $db, $log, $config, $current_user;

$action = 'view|show';
$act = check_action($action, getGET('act'), 'view');

$response = [
    'error' => -1,
    'message' => ''
];

//====================== 评论列表 =================================
if('view' == $act) {
    $response = [
        'error' => -1,
        'message' => '',
        'comments' => []
    ];

    $page = intval(getGET('page'));
    $page = max(1, $page);

    $page_size = intval(getGET('page_size'));
    $page_size = max(5, $page_size);

    $product_sn = getGET('product_sn');

    $tag = getGET('tag');
    $tags = 'all|good|normal|bad';
    $tag = check_action($tags, $tag, 'all');
    $where = '';

    switch($tag) {
        case 'good':
            $where .= ' and c.`star`>=4';
            break;

        case 'normal':
            $where .= ' and c.`star`>=2 and c.`star`<=3';
            break;

        case 'bad':
            $where .= ' and c.`star`<=1';
            break;
    }

    if(empty($product_sn)) {
        throw new RestFulException('参数错误', 403);
    }
    $product_sn = $db->escape($product_sn);

    //获取评论
    $get_comments = 'select c.`id`,c.`comment`,c.`star`,c.`add_time`,c.`avatar`,m.`headimg`,if(c.`nickname` is not null, c.`nickname`, m.`nickname`) as nickname,c.`img`,c.`account` from ' . $db->table('comment') . ' as c' .
        ' left join ' . $db->table('member') . ' as m using(`account`) where c.`parent_id`=0 and `product_sn`=\'' . $product_sn . '\' '.$where.
        ' order by c.`add_time` DESC limit '.(($page - 1) * $page_size).','.$page_size;

    $comments = $db->fetchAll($get_comments);

    if ($comments) {
        while ($comment = array_shift($comments)) {
            $replies = $db->all('comment', ['id', 'comment', 'add_time'], ['parent_id' => $comment['id']]);
            if($replies) {
                foreach($replies as &$_reply) {
                    $_reply['add_time'] = date('Y-m-d', $_reply['add_time']);
                }
            }

            array_push($response['comments'], [
                'id' => $comment['id'],
                'content' => $comment['comment'],
                'star' => intval($comment['star']),
                'img' => $comment['img'],
                'account' => $comment['account'],
                'add_time' => date('Y-m-d', $comment['add_time']),
                'nickname' => $comment['nickname'],
                'avatar' => !empty($comment['avatar']) ? $comment['avatar'] : (empty($comment['headimg']) ? '/assets/images/user-unlogin.png' : $comment['headimg']),
                'reply' => $replies
            ]);
        }
    }

    $response['error'] = 0;
}

echo json_encode($response);