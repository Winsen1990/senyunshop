<?php
/**
 * 问题管理
 * @author Winsen
 * @email airplace1@gmail.com
 * @date 2015-8-6
 * @version 1.0.0
 */
include 'library/init.inc.php';
global $db, $config, $log, $smarty;

business_base_init();

$template = 'question/';
assign('subTitle', '问题管理');

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

    $question = $db->find('question', ['id', 'answers', 'exam_id'], ['id' => $id]);

    if(empty($question)) {
        throw new RestFulException('问题不存在', 550);
    }

    $data = [
        'title' => trim(getPOST('title')),
        'answer_mode' => 1,//intval(getPOST('answer_mode')),
        'answers' => getPOST('answers')
    ];

    if(empty($data['title'])) {
        $response['errors']['title'] = '请填写问题';
    }

    $data['answer_mode'] = max($data['answer_mode'], 1);
    $data['answer_mode'] = min(2, $data['answer_mode']);

    if(!is_array($data['answers']) || count($data['answers']) < 2) {
        $response['errors']['answers'] = '请设置最少2个答案选项';
    } else {
        foreach($data['answers'] as $index => $_answer) {
            $_answer = trim($_answer);
            if (empty($_answer)) {
                unset($data['answers'][$index]);
                continue;
            }

            $data['answers'][$index] = $_answer;
        }

        if(!is_array($data['answers']) || count($data['answers']) < 2) {
            $response['errors']['answers'] = '请设置最少2个答案选项';
        }
    }

    if(count($response['errors']) == 0 && $response['message'] == '') {
        $question['answers'] = json_decode($question['answers']);
        $new_answer_count = count($data['answers']);
        $data['answers'] = json_encode($data['answers']);
        if($db->upgrade('question', $data, ['id' => $id]) !== false) {
            if(count($question['answers']) > $new_answer_count) {
                //答案数量改变，需要剔除多余的结论
                $results = $db->all('result', ['id', 'answer_series'], ['exam_id' => $question['exam_id']]);

                if(!empty($results)) {
                    foreach($results as $_result) {
                        $_result['answer_series'] = explode(':', $_result['answer_series']);
                        foreach($_result['answer_series'] as $_answer_series) {
                            list($q_id, $a_index) = explode($_answer_series, '-');

                            if($q_id == $id && $a_index >= $new_answer_count) {
                                $db->destroy('result', ['id' => $_result['id']]);
                            }
                        }
                    }
                }
            }

            $response['error'] = 0;
            if(count($question['answers']) != $new_answer_count) {
                $response['message'] = '更新问题成功，答案数量发生改变，请稍后调整问题结论';
            } else {
                $response['message'] = '更新问题成功';
            }
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
        'title' => trim(getPOST('title')),
        'answer_mode' => 1,//intval(getPOST('answer_mode')),
        'answers' => getPOST('answers'),
        'exam_id' => intval(getPOST('exam_id'))
    ];

    if($data['exam_id'] <= 0) {
        throw new RestFulException('参数错误', 550);
    }

    $exam = $db->find('exam', ['id'], ['id' => $data['exam_id']]);
    if(empty($exam)) {
        throw new RestFulException('问卷不存在', 550);
    }

    if(empty($data['title'])) {
        $response['errors']['title'] = '请填写问题';
    }

    $data['answer_mode'] = max($data['answer_mode'], 1);
    $data['answer_mode'] = min(2, $data['answer_mode']);

    if(!is_array($data['answers']) || count($data['answers']) < 2) {
        $response['errors']['answers'] = '请设置最少2个答案选项';
    } else {
        foreach($data['answers'] as $index => $_answer) {
            $_answer = trim($_answer);
            if (empty($_answer)) {
                unset($data['answers'][$index]);
                continue;
            }

            $data['answers'][$index] = $_answer;
        }

        if(!is_array($data['answers']) || count($data['answers']) < 2) {
            $response['errors']['answers'] = '请设置最少2个答案选项';
        }
    }

    if(count($response['errors']) == 0 && $response['message'] == '') {
        $data['answers'] = json_encode($data['answers']);
        if($db->create('question', $data)) {
            $response['error'] = 0;
            $response['message'] = '创建问题成功';
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

    if(!empty($questions)) {
        foreach($questions as &$_question) {
            $_question['answer_mode_str'] = $_question['answer_mode'] == 1 ? '单选' : '多选';
            $_question['answers'] = implode('<br/>', json_decode($_question['answers'], true));
        }
    }

    assign('exam', $exam);
    assign('questions', $questions);
}

if('add' == $act)
{
    if( !check_purview('pur_exam_add', $_SESSION['business_purview']) ) {
        show_system_message('权限不足');
    }

    $exam_id = intval(getGET('exam_id'));

    if($exam_id <= 0) {
        show_system_message('参数错误');
    }

    $exam = $db->find('exam', ['id', 'title'], ['id' => $exam_id]);

    if(empty($exam)) {
        show_system_message('问卷不存在');
    }

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

    $question = $db->find('question', ['id', 'answers', 'title', 'answer_mode'], ['id' => $id]);

    if(empty($question)) {
        show_system_message('问题不存在');
    }

    $question['answers'] = json_decode($question['answers']);

    assign('question', $question);
}

if('delete' == $act)
{
    if( !check_purview('pur_exam_del', $_SESSION['business_purview']) ) {
        show_system_message('权限不足');
    }

    $id = intval(getGET('id'));

    if($id <= 0) {
        show_system_message('参数错误');
    }

    if($db->destroy('question', ['id' => $id])) {
        show_system_message('删除问题成功，稍后请调整问卷结论');
    } else {
        show_system_message('系统繁忙，请稍后再试');
    }
}

$template .= $act.'.phtml';
$smarty->display($template);
