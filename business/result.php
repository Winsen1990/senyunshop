<?php
/**
 * 结论管理
 * @author Winsen
 * @email airplace1@gmail.com
 * @date 2015-8-6
 * @version 1.0.0
 */
include 'library/init.inc.php';
global $db, $config, $log, $smarty;

business_base_init();

$template = 'result/';
assign('subTitle', '结论管理');

$action = 'edit|add|view|delete';
$operation = 'edit|add';

$act = check_action($action, getGET('act'));
$act = ( $act == '' ) ? 'view' : $act;

$opera = check_action($operation, getPOST('opera'));

if('edit' == $opera)
{
    $response = ['error'=>1, 'message'=>'', 'errors' => []];

    if(!check_purview('pur_exam_edit', $_SESSION['business_purview']))
    {
        throw new RestFulException('没有操作权限', 530);
    }

    $id = intval(getPOST('id'));

    if($id <= 0) {
        throw new RestFulException('参数错误', 550);
    }

    $data = [
        'recommend_product' => getPOST('recommend_product'),
        'conclusion' => trim(getPOST('conclusion'))
    ];

    if(empty($data['conclusion'])) {
        $response['errors']['conclusion'] = '请填写结论';
    }

    if(!empty($data['recommend_product'])) {
        foreach($data['recommend_product'] as $index => &$_product_sn) {
            $_product_sn = trim($_product_sn);
            if(empty($_product_sn)) {
                unset($data['recommend_product'][$index]);
               continue;
            }
        }
    }

    if(count($response['errors']) == 0 && $response['message'] == '') {
        $data['recommend_product'] = json_encode($data['recommend_product']);
        if($db->upgrade('result', $data, ['id' => $id]) !== false) {
            $response['error'] = 0;
            $response['message'] = '更新结论成功';
        } else {
            $response['message'] = '系统繁忙，请稍后再试';
        }
    }

    echo json_encode($response);
    exit;
}

if('add' == $opera)
{
    $response = ['error'=>1, 'message'=>'', 'errors' => []];

    if(!check_purview('pur_exam_add', $_SESSION['business_purview']))
    {
        throw new RestFulException('没有操作权限', 530);
    }

    $data = [
        'answer_series' => trim(getPOST('answer_series')),
        'recommend_product' => getPOST('recommend_product'),
        'conclusion' => trim(getPOST('conclusion')),
        'exam_id' => intval(getPOST('exam_id'))
    ];

    if($data['exam_id'] <= 0) {
        throw new RestFulException('参数错误', 550);
    }

    $exam = $db->find('exam', ['id'], ['id' => $data['exam_id']]);

    if(empty($exam)) {
        throw new RestFulException('问卷不存在', 550);
    }

    if(empty($data['answer_series'])) {
        throw new RestFulException('参数错误', 550);
    }

    if(empty($data['conclusion'])) {
        $response['errors']['conclusion'] = '请填写结论';
    }

    if(!empty($data['recommend_product'])) {
        foreach($data['recommend_product'] as $index => &$_product_sn) {
            $_product_sn = trim($_product_sn);
            if(empty($_product_sn)) {
                unset($data['recommend_product'][$index]);
                continue;
            }
        }
    }

    if(count($response['errors']) == 0 && $response['message'] == '') {
        $data['recommend_product'] = json_encode($data['recommend_product']);
        if($db->create('result', $data)) {
            $response['error'] = 0;
            $response['message'] = '创建结论成功';
        } else {
            $response['message'] = '系统繁忙，请稍后再试';
        }
    }

    echo json_encode($response);
    exit;
}

//=====================================================================================================
if('view' == $act) {
    if (!check_purview('pur_exam_view', $_SESSION['business_purview'])) {
        show_system_message('权限不足', array());
    }

    $exam_id = intval(getGET('exam_id'));

    if($exam_id <= 0) {
        show_system_message('参数错误');
    }

    $exam = $db->find('exam', ['id', 'title'], ['id' => $exam_id]);

    if(empty($exam)) {
        show_system_message('问卷不存在');
    }

    $questions = $db->all('question', ['id', 'title', 'answers', 'answer_mode']);
    $results = $db->all('result', ['id', 'answer_series', 'recommend_product', 'conclusion'], ['exam_id' => $exam_id]);

    $result_map = [];
    $result_explain = [];
    if(!empty($results)) {
        foreach($results as $_result) {
            $result_map[$_result['answer_series']] = $_result;
        }
    }

    if(!empty($questions)) {
        $full_result_set = [];
        $full_result_explain_set = [];
        foreach($questions as $_question) {
            $answer_series = '';
            $answers = json_decode($_question['answers'], true);
            $prefix = [];
            if(empty($full_result_set)) {
                foreach($answers as $index => $_answer) {
                    array_push($full_result_set, $_question['id'].'-'.$index);
                    array_push($full_result_explain_set, chr(65 + $index));
                }
            } else {
                $prefix = $full_result_set;
                $explain_prefix = $full_result_explain_set;
                $full_result_set = [];
                $full_result_explain_set = [];
                foreach($answers as $index => $_answer) {
                    foreach($prefix as $i => $_pre) {
                        array_push($full_result_set, $_pre.':'.$_question['id'] . '-' . $index);
                        array_push($full_result_explain_set, $explain_prefix[$i].';'.chr(65 + $index));
                    }
                }
            }
        }

        foreach($full_result_set as $i => $_answer_series) {
            if(!isset($result_map[$_answer_series])) {
                $result_map[$_answer_series] = [
                    'id' => 0,
                    'answer_series' => $_answer_series,
                    'recommend_product' => '',
                    'conclusion' => ''
                ];
            }

            $result_map[$_answer_series]['answer_series_str'] = $full_result_explain_set[$i];
        }

        ksort($result_map);
        $results = array_values($result_map);
    }

    assign('results', $results);
    assign('exam', $exam);
    assign('questions', $questions);
}

if('add' == $act)
{
    if(!check_purview('pur_exam_add', $_SESSION['business_purview'])) {
        show_system_message('权限不足');
    }

    $answer_series = trim(getGET('series'));
    $exam_id = intval(getGET('exam_id'));

    if($exam_id <= 0) {
        show_system_message('参数错误');
    }

    $exam = $db->find('exam', ['id', 'title'], ['id' => $exam_id]);

    if(empty($exam)) {
        show_system_message('问卷不存在');
    }

    if(empty($answer_series)) {
        show_system_message('参数错误');
    }

    $answers = explode(':', $answer_series);
    if(empty($answers)) {
        show_system_message('答案序列错误');
    } else {
        $answer_series_str = '';
        foreach($answers as $_answer) {
            list($q_id, $a_index) = explode('-', $_answer);

            $a_index = max(0, $a_index);
            $a_index = min(25, $a_index);

            if($answer_series_str != '') {
                $answer_series_str .= ';';
            }
            $answer_series_str .= chr(65 + $a_index);
        }

        assign('answer_series_str', $answer_series_str);
    }

    assign('answer_series', $answer_series);
    assign('exam', $exam);
}

if('edit' == $act)
{
    if( !check_purview('pur_exam_edit', $_SESSION['business_purview']) ) {
        show_system_message('权限不足');
    }

    $id = intval(getGET('id'));

    if($id <= 0) {
        show_system_message('参数错误');
    }

    $result = $db->find('result', ['id', 'answer_series', 'conclusion', 'recommend_product'], ['id' => $id]);

    if(empty($result)) {
        show_system_message('结论不存在');
    }

    $answers = explode(':', $result['answer_series']);
    $answer_series_str = '';
    foreach($answers as $_answer) {
        list($q_id, $a_index) = explode('-', $_answer);

        $a_index = max(0, $a_index);
        $a_index = min(25, $a_index);

        if($answer_series_str != '') {
            $answer_series_str .= ';';
        }
        $answer_series_str .= chr(65 + $a_index);
    }

    if(!empty($result['recommend_product'])) {
        $result['recommend_product'] = json_decode($result['recommend_product']);
        $result['recommend_product'] = implode(',', $result['recommend_product']);
    }

    assign('answer_series_str', $answer_series_str);
    assign('result', $result);
}

if('delete' == $act)
{
    if( !check_purview('pur_exam_del', $_SESSION['business_purview']) ) {
        show_system_message('权限不足');
        exit;
    }

    $id = intval(getGET('id'));

    if($id <= 0) {
        show_system_message('参数错误');
    }

    if($db->destroy('result', ['id' => $id])) {

        show_system_message('删除结论成功');
    } else {
        show_system_message('系统繁忙，请稍后再试');
    }
}

$template .= $act.'.phtml';
$smarty->display($template);
