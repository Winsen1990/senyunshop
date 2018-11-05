<?php
/**
 * Created by PhpStorm.
 * User: Winsen
 * Date: 2018/11/4
 * Time: 11:09 AM
 */
include '../library/api.inc.php';
global $db, $current_user;

$operation = 'submit';
$opera = check_action($operation, getPOST('opera'));

$action = 'show';
$act = check_action($action, getGET('act'));
$response = [
    'error' => -1,
    'message' => ''
];

//提交问题
if('submit' == $opera) {
    $id = intval(getPOST('id'));
    $answer_series = trim(getPOST('answer_series'));

    if($id <= 0) {
        throw new RestFulException('参数错误', 550);
    }

    if(empty($answer_series)) {
        throw new RestFulException('答案不能为空', 403);
    }

    $result = $db->find('result', ['conclusion', 'recommend_product'], ['exam_id' => $id, 'answer_series' => $answer_series]);

    if($result) {
        $member_result = [
            'account' => $current_user['account'],
            'exam_id' => $id,
            'answer_series' => $answer_series,
            'add_time' => time(),
            'conclusion' => $result['conclusion'],
            'recommend_product' => $result['recommend_product']
        ];

        $db->create('member_exam_result', $member_result);

        if(!empty($result['recommend_product'])) {
            $recommend_products = $db->all('product', ['id', 'product_sn', 'price', 'img', 'name'], [
                'product_sn' => ['in', json_decode($result['recommend_product'], true)],
                'status' => 4
            ], null, ['order_view']);

            $response['recommend_products'] = $recommend_products;
            $response['conclusion'] = $result['conclusion'];
            $result = [
                'recommend_products' => $recommend_products,
                'conclusion' => $result['conclusion']
            ];

            $response['result'] = $result;
            $response['error'] = 0;
        }
    } else {
        $response['message'] = '没有匹配的结论';
    }
}

//问题详情
if('show' == $act) {
    /*
    $id = intval(getGET('id'));

    if($id <= 0) {
        throw new RestFulException('参数错误', 550);
    }
    */

    $exam = $db->find('exam', ['id', 'title'], [
//        'id' => $id,
        'status' => 1,
        'forever' => 1
    ]);

    if(empty($exam)) {
        throw new RestFulException('参数错误', 550);
    }

    $response['exam'] = $exam;

    $id = $exam['id'];

    $questions = $db->all('question', ['id', 'title', 'answer_mode', 'answers'], ['exam_id' => $id]);
    if($questions) {
        foreach($questions as &$_question) {
            $_question['answers'] = json_decode($_question['answers'], true);
        }
    }
    $response['questions'] = $questions;

    $result = $db->find('member_exam_result', ['answer_series', 'recommend_product', 'conclusion'], [
        'exam_id' => $id,
        'account' => $current_user['account']
    ], [['add_time', 'DESC']]);

    if(!empty($result)) {
        $response['answer_series'] = $result['answer_series'];

        if(!empty($result['recommend_product'])) {
            $recommend_products = $db->all('product', ['id', 'product_sn', 'price', 'img', 'name'], [
                'product_sn' => ['in', json_decode($result['recommend_product'], true)],
                'status' => 4
            ], null, ['order_view']);

            $response['recommend_products'] = $recommend_products;
            $response['conclusion'] = $result['conclusion'];
            $result = [
                'recommend_products' => $recommend_products,
                'conclusion' => $result['conclusion']
            ];
        }
    }
    $response['result'] = $result;

    $response['error'] = 0;
}

echo json_encode($response);
exit;