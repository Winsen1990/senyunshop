<?php
/**
 *
 * @author 王仁欢
 * @email wrh4285@163.com
 * @date 2015-09-02
 * @version 1.0.0
 */

include 'library/init.inc.php';

if( !isset($_SESSION['account']) ) {
    echo json_decode(array(
        'error' => 1,
        'message' => '请先登陆',
    ));
    exit;
}

if( check_cross_domain() ) {
    echo json_decode(array(
        'error' => 1,
        'message' => '请从本站提交数据',
    ));
    exit;
}

$operation = 'get_children';
$opera = check_action($operation, getPOST('opera'));

if( 'get_children' == $opera ) {

    $account = trim(getPOST('account'));
    if( '' == $account ) {

        $current = trim(getPOST('current'));
        if( '' == $current ) {
            echo json_encode(array(
                'error' => 1,
                'message' => '参数错误',
            ));
            exit;
        } else {
            $current = $db->escape($current);
            $get_member = 'select id, account, parent_id, nickname as parentId from '.$db->table('member');
            $get_member .= ' where account = \''.$current.'\' limit 1';
            $member = $db->fetchRow($get_member);
            if( $member ) {
                $member['name'] = $member['account'].'-'.$member['nickname'];
                $member['isParent'] = true;
                echo json_encode(array(
                    'error' => 0,
                    'message' => '成功',
                    'data' => $member,
                ));
                exit;
            } else {
                echo json_encode(array(
                    'error' => 1,
                    'message' => '会员不存在',
                ));
                exit;
            }
        }
        $account = $current;
    }
    $account = $db->escape($account);

    $get_account = 'select * from '.$db->table('member').' where account = \''.$account.'\' limit 1';
    $account = $db->fetchRow($get_account);
    if( empty($account) ) {
        echo json_encode(array(
            'error' => 1,
            'message' => '会员不存在',
        ));
        exit;
    }

    $get_children = 'select id,account,parent_id as parentId,nickname from '.$db->table('member');
    $get_children .= ' where parent_id = '.$account['id'];

    $children = $db->fetchAll($get_children);
    if( $children ) {
        foreach( $children as $key => $value ) {
            $children[$key]['name'] = $value['account'].'-'.$value['nickname'];
            $children[$key]['isParent'] = true;
        }

        echo json_encode(array(
            'error' => 0,
            'message' => '成功',
            'data' => $children,
        ));
        exit;
    } else {
        echo json_encode(array(
            'error' => 0,
            'message' => '成功',
            'data' => array(),
        ));
        exit;
    }
}

echo json_encode(array(
    'error' => 1,
    'message' => '参数错误',
));
exit;