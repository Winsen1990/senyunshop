<?php
/**
 * Created by PhpStorm.
 * User: airpl
 * Date: 2018/10/25
 * Time: 17:02
 */
include '../library/api.inc.php';
global $db, $log, $config, $current_user;

$operation = 'add|delete|thumb_up';
$opera = check_action($operation, getPOST('opera'));

$action = 'view|show';
$act = check_action($action, getGET('act'), 'view');

$response = [
    'error' => -1,
    'message' => ''
];


//删除评论
if('delete' == $opera) {
    $id = intval(getPOST('id'));

    if($id <= 0) {
        throw new RestFulException('参数错误', 550);
    }

    $comment = $db->find('content_comment', ['id', 'content_id', 'status'], ['account' => $current_user['account'], 'id' => $id]);

    if(empty($comment)) {
        throw new RestFulException('评论不存在', 450);
    }

    if($db->destroy('content_comment', ['id' => $id]) !== false) {
        $db->destroy('content_comment', ['parent_id' => $id]);
        $db->destroy('content_comment_up', ['content_comment_id' => $id]);

        if($comment['status'] == 1) {
            $db->upgrade('content', ['comment_count' => ['exp', '`comment_count`-1']], ['id' => $comment['content_id']]);
        }

        $response['error'] = 0;
        $response['message'] = '删除评论成功';
    } else {
        $response['message'] = '删除评论失败';
    }
}

//发表评论
if('add' == $opera) {
    $id = intval(getPOST('id'));
    $comment = trim(getPOST('comment'));


    if($id <= 0 || $comment == '') {
        throw new RestFulException('参数错误', 550);
    }

    $comment_data = [
        'content_id' => $id,
        'comment' => $comment,
        'add_time' => time(),
        'account' => $current_user['account']
    ];

    if($db->create('content_comment', $comment_data)) {
        $comment_id = $db->get_last_id();

        $comment_assoc_data = [
            'path' => $comment_id.','
        ];

        $db->upgrade('content_comment', $comment_assoc_data, ['id' => $comment_id]);
//        $db->upgrade('content', ['comment_count' => ['exp', '`comment_count`+1']], ['id' => $id]);

        $response['error'] = 0;
        $response['message'] = '发表评论成功，请等待审核';
    } else {
        $response['message'] = '发表评论失败';
    }
}


//资讯评论点赞
if('thumb_up' == $opera) {
    $id = intval(getPOST('id'));

    if($id <= 0) {
        throw new RestFulException('参数错误', 550);
    }

    $comment_thumb_up_record = $db->find('content_comment_up', ['account'], [
        'content_comment_id' => $id,
        'account' => $current_user['account']
    ]);

    if(empty($comment_thumb_up_record)) {
        $data = [
            'content_comment_id' => $id,
            'account' => $current_user['account'],
            'add_time' => time()
        ];

        if($db->create('content_comment_up', $data)) {
            $db->upgrade('content_comment', ['thumb_up' => ['exp', '`thumb_up`+1']], ['id' => $id]);
            $response['error'] = 0;
            $response['message'] = '点赞成功';
        } else {
            $response['message'] = '点赞失败';
        }
    } else {
        if($db->destroy('content_comment_up', ['content_comment_id' => $id, 'account' => $current_user['account']]) !== false) {
            $db->upgrade('content_comment', ['thumb_up' => ['exp', '`thumb_up`-1']], ['id' => $id]);
            $response['error'] = 0;
            $response['message'] = '取消点赞成功';
        } else {
            $response['message'] = '取消点赞失败';
        }
    }
}

//评论列表
if('view' == $act) {

}

echo json_encode($response);