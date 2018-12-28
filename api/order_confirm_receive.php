<?php
/**
 * Created by PhpStorm.
 * User: Winsen
 * Date: 2017/12/15
 * Time: 上午11:22
 */
include 'library/init.inc.php';

global $db, $config, $loader;

$loader->includeScript('transaction');

$crontab_log = new Logs(true, 'crontab_'.date('Ymd').'.log');

$condition = array(
    'delivery_time' => array('elt', strtotime('-15 days')),
    'status' => 6
);

$order_list = $db->all('order', array('order_sn', 'account'), $condition);
$result = array('success' => 0, 'fail' => 0);

if($order_list) {
    foreach($order_list as $order) {
        $order_sn = $order['order_sn'];
        $account = $order['account'];
        //自动确认收货
        $data = array(
            'status' => 12,
            'receive_time'=>time()
        );

        if($db->autoUpdate('order', $data, '`order_sn`=\''.$order_sn.'\' and `status`=6'))
        {
            add_order_log($order_sn, $account, 12, '系统自动完成订单');
            //将款项打到商家账户中
            //读取担保交易记录，并将款项打入到商家的余额中
            $get_trade = 'select `id`,`business_account`,`trade` from '.$db->table('trade').' where `remark`=\''.$order_sn.'\' and `status`=0';
            $trade = $db->fetchRow($get_trade);

            if(add_business_exchange($trade['business_account'], $trade['trade'], -1*$trade['trade'], $account, '系统自动完成订单'))
            {
                $trade_status = array(
                    'status' => 1,
                    'solve_time' => time()
                );

                $db->autoUpdate('trade', $trade_status, '`id`='.$trade['id']);
            }

            //读取佣金,将佣金发放到用户账户里
            $get_member_rewards = 'select `id`,`remark`,`integral`,`account`,`reward` from '.$db->table('member_reward').' where `status`=0 and `assoc`=\''.$order_sn.'\'';
            $member_rewards = $db->fetchAll($get_member_rewards);
            if($member_rewards) {
                foreach ($member_rewards as $reward) {
                    if (add_memeber_exchange_log($reward['account'], 0, $reward['reward'], $reward['integral'], -1 * $reward['integral'], -1 * $reward['reward'], $account, $order_sn . '奖金发放')) {
                        $reward_status = array(
                            'status' => 1,
                            'solve_time' => time()
                        );

                        $db->autoUpdate('member_reward', $reward_status, '`id`=' . $reward['id']);
                    }
                }
            }

            //自动好评
            $order_details = $db->all('order_detail', ['product_sn'], ['order_sn' => $order['order_sn']]);

            if($order_details) {
                foreach($order_details as $_detail) {
                    $product_sn = $_detail['product_sn'];
                    $comment_data = array(
                        'add_time' => time(),
                        'comment' => '好评',
                        'star' => 5,
                        'product_sn' => $product_sn,
                        'account' => $order['account'],
                        'parent_id' => 0
                    );

                    if ($db->create('comment', $comment_data)) {
                        $id = $db->get_last_id();

                        $comment_data = array('path' => $id . ',');

                        $db->autoUpdate('comment', $comment_data, '`id`=' . $id);

                        //获取评论数量和星级
                        $get_comment_info = 'SELECT count(*) AS c, sum(`star`) AS star FROM ' . $db->table('comment') . ' WHERE `product_sn`=\'' . $product_sn . '\'';
                        $comment_info = $db->fetchRow($get_comment_info);
                        $product_data = array('star' => $comment_info['star'] / $comment_info['c']);

                        $db->autoUpdate('product', $product_data, '`product_sn`=\'' . $product_sn . '\'');
                    }
                }
            }

            $result['success']++;
        } else {
            $result['fail']++;
        }
    }
}

$caller = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'UNKNOWN';
$crontab_log->record('exec by '.$caller.' success: '.$result['success'].', fail:'.$result['fail']);
