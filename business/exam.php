<?php
/**
 * 测试管理
 * @author Winsen
 * @email airplace1@gmail.com
 * @date 2015-8-6
 * @version 1.0.0
 */
include 'library/init.inc.php';
global $db, $config, $log, $smarty;

business_base_init();

$template = 'exam/';
assign('subTitle', '测试管理');

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
        'title' => trim(getPOST('title')),
        'status' => intval(getPOST('status'))
    ];


    if(empty($data['title'])) {
        $response['errors']['title'] = '请填写问卷标题';
    }

    $status = max($data['status'], 0);
    $status = min(1, $data['status']);

    if(count($response['errors']) == 0 && $response['message'] == '') {
        if($db->upgrade('exam', $data, ['id' => $id]) !== false) {
            $response['error'] = 0;
            $response['message'] = '更新问卷成功';
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
        'status' => intval(getPOST('status'))
    ];


    if(empty($data['title'])) {
        $response['errors']['title'] = '请填写问卷标题';
    }

    $status = max($data['status'], 0);
    $status = min(1, $data['status']);

    if(count($response['errors']) == 0 && $response['message'] == '') {
        $data['forever'] = 1;
        if($db->create('exam', $data)) {
            $response['error'] = 0;
            $response['message'] = '创建问卷成功';
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

    $exam_list = $db->all('exam', ['id', 'title', 'status']);

    if(!empty($exam_list)) {
        foreach($exam_list as &$_exam) {
            $_exam['status_str'] = $_exam['status'] == 1 ? '启用' : '停用';
        }
    }

    assign('exam_list', $exam_list);
}

if('edit' == $act)
{
    if( !check_purview('pur_exam_edit', $_SESSION['business_purview']) ) {
        show_system_message('权限不足');
        exit;
    }

    $id = intval(getGET('id'));

    if($id <= 0) {
        show_system_message('参数错误');
    }

    $exam = $db->find('exam', ['title', 'status'], ['id' => $id]);

    assign('exam', $exam);
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

    if($db->destroy('exam', ['id' => $id])) {
        $db->destroy('question', ['exam_id' => $id]);
        $db->destroy('result', ['exam_id' => $id]);
        $db->destroy('member_exam_result', ['exam_id' => $id]);

        show_system_message('删除问卷成功');
    } else {
        show_system_message('系统繁忙，请稍后再试');
    }
}

$template .= $act.'.phtml';
$smarty->display($template);
