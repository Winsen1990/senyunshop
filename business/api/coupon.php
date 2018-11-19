<?php
/**
 * Created by PhpStorm.
 * User: Winsen
 * Date: 2018/7/8
 * Time: 下午6:42
 */
include 'library/init.inc.php';

global $log, $db;

$response = array(
    'error' => -1,
    'message' => ''
);

//优惠券类型
$coupon_types = [
    1 => '折扣券',
    2 => '代金券',
    3 => '满减券',
];

//调用位置
$hook_list = [
    'after_trade' => '交易完成后',
    'manual' => '手动领取'
];

//===========================================================================
// 添加优惠券
if($_SERVER['REQUEST_METHOD'] == 'POST')
{
    $coupon = [
        'prefix' => getPOST('prefix'),
        'name' => getPOST('name'),
        'type' => intval(getPOST('type')),
        'min_amount' => floatval(getPOST('min_amount')),
        'decrement_limit' => floatval(getPOST('decrement_limit')),
        'discount' => intval(getPOST('discount')),
        'decrement' => floatval(getPOST('decrement')),
        'category_scope' => getPOST('category_scope'),
        'product_scope' => getPOST('product_scope'),
        'ignore_all' => getPOST('ignore_all') == 'true' ? 1 : 0,
        'forever' => getPOST('forever') == 'true' ? 1 : 0,
        'begin_time' => getPOST('begin_time'),
        'end_time' => getPOST('end_time'),
        'channels' => getPOST('channels'),
        'number' => intval(getPOST('number')),
        'cycle' => getPOST('cycle') == 'true' ? 1 : 0,
        'expired_time' => getPOST('expired_time'),
        'active_time' => intval(getPOST('active_time')),
        'member_levels' => getPOST('member_levels'),
        'status' => intval(getPOST('status')),
        'cost' => floatval(getPOST('cost')),
        'hook' => trim(getPOST('hook')),
        'desc' => trim(getPOST('desc'))
    ];

    $errors = [];

    $coupon['name'] = trim($coupon['name']);
    if(empty($coupon['name'])) {
        array_push($errors, '请填写优惠券名称');
    }

    if(!array_key_exists($coupon['type'], $coupon_types)) {
        array_push($errors, '请选择优惠券类型');
    }

    if(!array_key_exists($coupon['hook'], $hook_list)) {
        array_push($errors, '请选择优惠券调用位置');
    }

    //优惠券成本
    if($coupon['cost'] < 0) {
        array_push($errors, '请填写单张优惠券成本');
    }

    $coupon['min_amount'] = max(0, $coupon['min_amount']);
    switch($coupon['type'])
    {
        case 1:
            //折扣券
            unset($coupon['decrement']);
            if($coupon['discount'] <= 0) {
                array_push($errors, '折扣必须大于0');
            } else if($coupon['discount'] >= 100) {
                array_push($errors, '折扣必须小于100');
            }

            if($coupon['decrement_limit'] <= 0.001 && $coupon['decrement_limit'] != -1) {
                array_push($errors, '最大减免金额必须大于0');
            }
            break;

        case 2:
            //代金券
            unset($coupon['decrement_limit']);
            unset($coupon['discount']);

            if($coupon['decrement'] <= 0.001) {
                array_push($errors, '减免金额必须大于0');
            }
            break;

        case 3:
            //满减券
            unset($coupon['decrement_limit']);
            unset($coupon['discount']);
            if($coupon['min_amount'] < $coupon['decrement']) {
                array_push($errors, '减免金额不能超过最低起用金额');
            }

            if($coupon['decrement'] <= 0.001) {
                array_push($errors, '减免金额必须大于0');
            }
            break;
    }

    //发放总量
    $coupon['number'] = max(0, $coupon['number']);
    if($coupon['number'] <= 0) {
        array_push($errors, '发放总数必须大于0');
    }

    //发放时间
    $coupon['begin_time'] = strtotime($coupon['begin_time']);
    if($coupon['begin_time'] <= 0 || $coupon['begin_time'] <= time()) {
        array_push($errors, '开始发放时间不能早于当前时间');
    }

    $coupon['end_time'] = strtotime($coupon['end_time']);
    if($coupon['end_time'] <= $coupon['begin_time']) {
        array_push($errors, '结束发放时间不能早于开始发放时间');
    }

    //生效时间
    if($coupon['forever']) {
        $coupon['expired_time'] = 0;
        $coupon['active_time'] = 0;
    } else {
        if (!empty($coupon['expired_time'])) {
            $coupon['expired_time'] = strtotime($coupon['expired_time']);
            if ($coupon['expired_time'] <= 0 || $coupon['expired_time'] <= time()) {
                array_push($errors, '过期时间不能早于当前时间');
            } else if ($coupon['expired_time'] <= $coupon['begin_time']) {
                array_push($errors, '过期时间不能早于开始发放时间');
            } else if ($coupon['expired_time'] < $coupon['end_time']) {
                array_push($errors, '过期时间不能早于结束发放时间');
            }
            $coupon['active_time'] = 0;
        } else {
            $coupon['active_time'] = max(0, $coupon['active_time']);
            if ($coupon['active_time'] == 0) {
                array_push($errors, '优惠券有效时间必须大于0分钟');
            }
            $coupon['expired_time'] = 0;
        }
    }

    //适用会员等级
    if(empty($coupon['member_levels'])) {
        $coupon['member_levels'] = '';
    } else {
        $levels = $db->all('level', ['id']);
        $level_scope = [];
        if($levels) {
            while($_level = array_shift($levels)) {
                array_push($level_scope, $_level['id']);
            }
        }
        if(!empty($coupon['member_levels']) && is_array($coupon['member_levels']) && count($coupon['member_levels'])) {
            $member_levels = [];
            foreach($coupon['member_levels'] as $i => $level_id) {
                $level_id = intval($level_id);
                if(in_array($level_id, $level_scope) && !in_array($level_id, $member_levels)) {
                    array_push($member_levels, $level_id);
                }
            }

            if(count($member_levels)) {
                $coupon['member_levels'] = $member_levels;
            } else {
                array_push($errors, '适用会员等级无效');
            }
        } else {
            array_push($errors, '适用会员等级无效');
        }
    }

    //适用品类
    if($coupon['ignore_all']) {
        $coupon['category_scope'] = '';
        $coupon['product_scope'] = '';
    } else {
        //适用分类
        if (!empty($coupon['category_scope']) && is_array($coupon['category_scope']) && count($coupon['category_scope'])) {
            foreach ($coupon['category_scope'] as $i => $category_id) {
                $category_id = intval($category_id);
                if ($category_id > 0) {
                    $coupon['category_scope'][$i] = $category_id;
                } else {
                    unset($coupon['category_scope'][$i]);
                }
            }

            $category_conditions = [
                'id' => ['in', $coupon['category_scope']]
            ];

            $categories = $db->all('category', ['id'], $category_conditions);

            if (!empty($categories)) {
                $category_scope = [];
                foreach ($categories as $category) {
                    array_push($category_scope, $category['id']);
                }
                $coupon['category_scope'] = $category_scope;
            } else {
                array_push($errors, '适用产品分类无效');
            }
        } else {
            $coupon['category_scope'] = '';
        }

        //适用产品
        if (!empty($coupon['product_scope']) && is_array($coupon['product_scope']) && count($coupon['product_scope'])) {
            foreach ($coupon['product_scope'] as $i => $_product_sn) {
                $_product_sn = trim($_product_sn);
                if (!empty($_product_sn)) {
                    $coupon['product_scope'][$i] = $_product_sn;
                } else {
                    unset($coupon['product_scope'][$i]);
                }
            }

            $product_conditions = [
                'product_sn' => ['in', $coupon['product_scope']],
                'status' => 4 //仅能对已上架的产品进行设置
            ];

            if(!empty($coupon['category_scope'])) {
                $product_sn_list = $db->all('product_category_mapper', ['distinct(`product_sn`)'], ['category_id' => ['in', $coupon['category_scope']]]);

                if($product_sn_list) {
                    $product_sn_scope = [];
                    while($product_map = array_shift($product_sn_list)) {
                        array_push($product_sn_scope, $product_map['product_sn']);
                    }

                    $product_sn_scope = array_intersect($coupon['product_scope'], $product_sn_scope);
                    $product_conditions['product_sn'] = ['in', $product_sn_scope];
                }
            }

            $products = $db->all('product', ['id'], $product_conditions);

            if (!empty($products)) {
                $product_scope = [];
                foreach ($products as $product) {
                    array_push($product_scope, $product['id']);
                }
                $coupon['product_scope'] = $product_scope;
            } else {
                array_push($errors, '适用产品无效');
            }
        } else {
            $coupon['product_scope'] = '';
        }
    }

    if(empty($coupon['desc'])) {
        array_push($errors, '请填写使用规则');
    }

    $coupon['status'] = $coupon['status'] == 1 ? 1 : 0;

    if(empty($errors)) {
        $coupon_rules = [
            'category_scope' => null,
            'product_scope' => null,
            'member_levels' => null
        ];

        //1. 数据格式转化
        if(!empty($coupon['member_levels'])) {
            $coupon_rules['member_levels'] = $coupon['member_levels'];
            $coupon['member_levels'] = implode(',', $coupon['member_levels']);
        } else {
            $coupon_rules['member_levels'] = [0];
        }

        if(!empty($coupon['category_scope'])) {
            $coupon_rules['category_scope'] = $coupon['category_scope'];
            $coupon['category_scope'] = implode(',', $coupon['category_scope']);
        } else {
            $coupon_rules['category_scope'] = [0];
        }

        if(!empty($coupon['product_scope'])) {
            $coupon_rules['product_scope'] = $coupon['product_scope'];
            $coupon['product_scope'] = implode(',', $coupon['product_scope']);
        } else {
            $coupon_rules['product_scope'] = [0];
        }

        //2. 创建优惠券记录
        $coupon['add_time'] = time();
        $coupon['last_modify'] = time();
        $coupon['remain'] = $coupon['number'];
        $coupon['used'] = 0;

        $create_flag = false;
        if(!empty($coupon['prefix'])) {
            $create_flag = $db->create('coupon', $coupon);

            if(!$create_flag) {
                $response['message'] = '该前缀已被其他优惠券使用';
            }
        } else {
            $cnt = 10;
            do {
                $coupon['prefix'] = random_str(COUPON_PREFIX_LENGTH);
                $create_flag = $db->create('coupon', $coupon);
            } while(!$create_flag && $cnt--);
        }

        if($create_flag) {
            //3. 创建索引
            $coupon_id = $db->get_last_id();
            $coupon_rule_data = [];
            $rule_data = [
                'coupon_id' => $coupon_id,
                'category_id' => 0,
                'product_id' => 0,
                'level_id' => 0,
                'begin_time' => $coupon['begin_time'],
                'end_time' => $coupon['end_time'],
                'status' => $coupon['status']
            ];

            foreach($coupon_rules['category_scope'] as $rule_category_id) {
                $rule_data['category_id'] = $rule_category_id;

                foreach($coupon_rules['product_scope'] as $rule_product_id) {
                    $rule_data['product_id'] = $rule_product_id;

                    foreach($coupon_rules['member_levels'] as $rule_level_id) {
                        $rule_data['level_id'] = $rule_level_id;

                        array_push($coupon_rule_data, $rule_data);
                    }
                }
            }

            $db->autoInsert('coupon_rule_mapper', $coupon_rule_data);

            //4. 发放优惠券码
            alloc_coupon_sn($coupon['prefix'], $coupon_id, $coupon['number']);

            $response['id'] = $coupon_id;
            $response['message'] = '创建优惠券成功';
            $response['error'] = 0;
        } else {
            if(empty($response['message'])) {
                $response['message'] = '创建优惠券失败';
            }
        }
    } else {
        $response['message'] = implode("\n", $errors);
        $response['error'] = $errors;
    }
}

// 编辑优惠券
if($_SERVER['REQUEST_METHOD'] == 'PUT' && !empty($_POST['id']))
{
    $id = intval(getPOST('id'));
    $coupon = [
        'prefix' => getPOST('prefix'),
        'name' => getPOST('name'),
        'type' => intval(getPOST('type')),
        'min_amount' => floatval(getPOST('min_amount')),
        'decrement_limit' => floatval(getPOST('decrement_limit')),
        'discount' => intval(getPOST('discount')),
        'decrement' => floatval(getPOST('decrement')),
        'category_scope' => getPOST('category_scope'),
        'product_scope' => getPOST('product_scope'),
        'ignore_all' => getPOST('ignore_all') == 'true' ? 1 : 0,
        'forever' => getPOST('forever') == 'true' ? 1 : 0,
        'begin_time' => getPOST('begin_time'),
        'end_time' => getPOST('end_time'),
        'channels' => getPOST('channels'),
        'number' => intval(getPOST('number')),
        'cycle' => getPOST('cycle') == 'true' ? 1 : 0,
        'expired_time' => getPOST('expired_time'),
        'active_time' => intval(getPOST('active_time')),
        'member_levels' => getPOST('member_levels'),
        'status' => intval(getPOST('status')),
        'cost' => floatval(getPOST('cost')),
        'hook' => trim(getPOST('hook')),
        'desc' => trim(getPOST('desc'))
    ];

    $response['request'] = $coupon;

    if($id <= 0) {
        throw new RestFulException('参数错误', 400);
    }

    $old_coupon = $db->find('coupon', '*', ['id' => $id]);

    if(empty($old_coupon)) {
        throw new RestFulException('优惠券不存在', 400);
    }

    $errors = [];

    $coupon['name'] = trim($coupon['name']);
    if(empty($coupon['name'])) {
        array_push($errors, '请填写优惠券名称');
    }

    if(!array_key_exists($coupon['type'], $coupon_types)) {
        array_push($errors, '请选择优惠券类型');
    }

    if(!array_key_exists($coupon['hook'], $hook_list)) {
        array_push($errors, '请选择优惠券调用位置');
    }

    //优惠券成本
    if($coupon['cost'] < 0) {
        array_push($errors, '请填写单张优惠券成本');
    }

    $coupon['min_amount'] = max(0, $coupon['min_amount']);
    switch($coupon['type'])
    {
        case 1:
            //折扣券
            $coupon['decrement'] = 0;
            if($coupon['discount'] <= 0) {
                array_push($errors, '折扣必须大于0');
            } else if($coupon['discount'] >= 100) {
                array_push($errors, '折扣必须小于100');
            }

            if($coupon['decrement_limit'] <= 0.001 && $coupon['decrement_limit'] != -1) {
                array_push($errors, '最大减免金额必须大于0');
            }
            break;

        case 2:
            //代金券
            $coupon['decrement_limit'] = 0;
            $coupon['discount'] = 0;

            if($coupon['decrement'] <= 0.001) {
                array_push($errors, '减免金额必须大于0');
            }
            break;

        case 3:
            //满减券
            $coupon['decrement_limit'] = 0;
            $coupon['discount'] = 0;
            if($coupon['min_amount'] < $coupon['decrement']) {
                array_push($errors, '减免金额不能超过最低起用金额');
            }

            if($coupon['decrement'] <= 0.001) {
                array_push($errors, '减免金额必须大于0');
            }
            break;
    }

    //发放总量
    $coupon['number'] = max(0, $coupon['number']);
    if($coupon['number'] <= 0) {
        array_push($errors, '发放总数必须大于0');
    }

    //发放时间
    $coupon['begin_time'] = strtotime($coupon['begin_time']);
    if($coupon['begin_time'] <= 0) {
        array_push($errors, '开始发放时间不能早于当前时间');
    }

    $coupon['end_time'] = strtotime($coupon['end_time']);
    if($coupon['end_time'] <= $coupon['begin_time']) {
        array_push($errors, '结束发放时间不能早于开始发放时间');
    }

    //生效时间
    if($coupon['forever']) {
        $coupon['expired_time'] = 0;
        $coupon['active_time'] = 0;
    } else {
        if (!empty($coupon['expired_time'])) {
            $coupon['expired_time'] = strtotime($coupon['expired_time']);
            if ($coupon['expired_time'] <= 0 || $coupon['expired_time'] <= time()) {
                array_push($errors, '过期时间不能早于当前时间');
            } else if ($coupon['expired_time'] <= $coupon['begin_time']) {
                array_push($errors, '过期时间不能早于开始发放时间');
            } else if ($coupon['expired_time'] < $coupon['end_time']) {
                array_push($errors, '过期时间不能早于结束发放时间');
            }
            $coupon['active_time'] = 0;
        } else {
            $coupon['active_time'] = max(0, $coupon['active_time']);
            if ($coupon['active_time'] == 0) {
                array_push($errors, '优惠券有效时间必须大于0分钟');
            }
            $coupon['expired_time'] = 0;
        }
    }

    //适用会员等级
    if(empty($coupon['member_levels'])) {
        $coupon['member_levels'] = '';
    } else {
        $levels = $db->all('level', ['id']);
        $level_scope = [];
        if($levels) {
            while($_level = array_shift($levels)) {
                array_push($level_scope, $_level['id']);
            }
        }
        if(!empty($coupon['member_levels']) && is_array($coupon['member_levels']) && count($coupon['member_levels'])) {
            $member_levels = [];
            foreach($coupon['member_levels'] as $i => $level_id) {
                $level_id = intval($level_id);
                if(in_array($level_id, $level_scope) && !in_array($level_id, $member_levels)) {
                    array_push($member_levels, $level_id);
                }
            }

            if(count($member_levels)) {
                $coupon['member_levels'] = $member_levels;
            } else {
                array_push($errors, '适用会员等级无效');
            }
        } else {
            array_push($errors, '适用会员等级无效');
        }
    }

    //适用品类
    if($coupon['ignore_all']) {
        $coupon['category_scope'] = '';
        $coupon['product_scope'] = '';
    } else {
        //适用分类
        if (!empty($coupon['category_scope']) && is_array($coupon['category_scope']) && count($coupon['category_scope'])) {
            foreach ($coupon['category_scope'] as $i => $category_id) {
                $category_id = intval($category_id);
                if ($category_id > 0) {
                    $coupon['category_scope'][$i] = $category_id;
                } else {
                    unset($coupon['category_scope'][$i]);
                }
            }

            $category_conditions = [
                'id' => ['in', $coupon['category_scope']]
            ];

            $categories = $db->all('category', ['id'], $category_conditions);

            if (!empty($categories)) {
                $category_scope = [];
                foreach ($categories as $category) {
                    array_push($category_scope, $category['id']);
                }
                $coupon['category_scope'] = $category_scope;
            } else {
                array_push($errors, '适用产品分类无效');
            }
        } else {
            $coupon['category_scope'] = '';
        }

        //适用产品
        if (!empty($coupon['product_scope']) && is_array($coupon['product_scope']) && count($coupon['product_scope'])) {
            foreach ($coupon['product_scope'] as $i => $_product_sn) {
                $_product_sn = trim($_product_sn);
                if (!empty($_product_sn)) {
                    $coupon['product_scope'][$i] = $_product_sn;
                } else {
                    unset($coupon['product_scope'][$i]);
                }
            }

            $product_conditions = [
                'product_sn' => ['in', $coupon['product_scope']],
                'status' => 4 //仅能对已上架的产品进行设置
            ];

            if(!empty($coupon['category_scope'])) {
                $product_sn_list = $db->all('product_category_mapper', ['distinct(`product_sn`)'], ['category_id' => ['in', $coupon['category_scope']]]);

                if($product_sn_list) {
                    $product_sn_scope = [];
                    while($product_map = array_shift($product_sn_list)) {
                        array_push($product_sn_scope, $product_map['product_sn']);
                    }

                    $product_sn_scope = array_intersect($coupon['product_scope'], $product_sn_scope);
                    $product_conditions['product_sn'] = ['in', $product_sn_scope];
                }
            }

            $products = $db->all('product', ['id'], $product_conditions);

            if (!empty($products)) {
                $product_scope = [];
                foreach ($products as $product) {
                    array_push($product_scope, $product['id']);
                }
                $coupon['product_scope'] = $product_scope;
            } else {
                array_push($errors, '适用产品无效');
            }
        } else {
            $coupon['product_scope'] = '';
        }
    }

    $coupon['status'] = $coupon['status'] == 1 ? 1 : 0;

    if(!$coupon['cycle'] && $old_coupon['used'] > $coupon['number']) {
        array_push($errors, '优惠券发放数量少于已使用数量');
    }

    if(empty($coupon['desc'])) {
        array_push($errors, '请填写使用规则');
    }

    if(empty($errors)) {
        $coupon_rules = [
            'shop_scope' => null,
            'category_scope' => null,
            'product_scope' => null,
            'member_levels' => null
        ];

        //1. 数据格式转化
        if(!empty($coupon['member_levels'])) {
            $coupon_rules['member_levels'] = $coupon['member_levels'];
            $coupon['member_levels'] = implode(',', $coupon['member_levels']);
        } else {
            $coupon_rules['member_levels'] = [0];
        }

        if(!empty($coupon['shop_scope'])) {
            $coupon_rules['shop_scope'] = $coupon['shop_scope'];
            $coupon['shop_scope'] = implode(',', $coupon['shop_scope']);
        } else {
            $coupon_rules['shop_scope'] = [$active_shop['id']];
        }

        if(!empty($coupon['category_scope'])) {
            $coupon_rules['category_scope'] = $coupon['category_scope'];
            $coupon['category_scope'] = implode(',', $coupon['category_scope']);
        } else {
            $coupon_rules['category_scope'] = [0];
        }

        if(!empty($coupon['product_scope'])) {
            $coupon_rules['product_scope'] = $coupon['product_scope'];
            $coupon['product_scope'] = implode(',', $coupon['product_scope']);
        } else {
            $coupon_rules['product_scope'] = [0];
        }

        //2. 创建优惠券记录
        $coupon['last_modify'] = time();
        $coupon['remain'] = ['exp', '`number`-`used`'];

        if(empty($coupon['prefix'])) {
            unset($coupon['prefix']);
        }

        $update_flag = $db->upgrade('coupon', $coupon, ['id' => $id]);

        if($update_flag !== false) {
            //3. 重建索引
            $db->destroy('coupon_rule_mapper', ['coupon_id' => $id]);
            $coupon_rule_data = [];
            foreach($coupon_rules['shop_scope'] as $rule_shop_id) {
                $rule_data = [
                    'coupon_id' => $id,
                    'shop_id' => $rule_shop_id,
                    'category_id' => 0,
                    'product_id' => 0,
                    'level_id' => 0,
                    'begin_time' => $coupon['begin_time'],
                    'end_time' => $coupon['end_time'],
                    'status' => $coupon['status']
                ];

                foreach($coupon_rules['category_scope'] as $rule_category_id) {
                    $rule_data['category_id'] = $rule_category_id;

                    foreach($coupon_rules['product_scope'] as $rule_product_id) {
                        $rule_data['product_id'] = $rule_product_id;

                        foreach($coupon_rules['member_levels'] as $rule_level_id) {
                            $rule_data['level_id'] = $rule_level_id;

                            array_push($coupon_rule_data, $rule_data);
                        }
                    }
                }
            }

            $db->autoInsert('coupon_rule_mapper', $coupon_rule_data);

            //4. 发放优惠券号
            if(!isset($coupon['prefix'])) {
                $coupon['prefix'] = $old_coupon['prefix'];
            }

            $increment = $coupon['number'] - $old_coupon['number'];
            if($increment > 0) {
                alloc_coupon_sn($coupon['prefix'], $id, $increment);
            }


            $response['id'] = $id;
            $response['message'] = '编辑优惠券成功';
            $response['error'] = 0;
        } else {
            $response['message'] = '编辑优惠券失败';
        }
    } else {
        $response['message'] = implode("\n", $errors);
        $response['error'] = $errors;
    }
}

// 删除优惠券
if($_SERVER['REQUEST_METHOD'] == 'DELETE')
{
    $id = intval(getPOST('id'));

    if($id <= 0) {
        $response['message'] = '参数错误';
    } else {
        $coupon = $db->find('coupon', '*', ['id' => $id]);

        if(!empty($coupon)) {
            if($coupon['number'] - $coupon['remain'] || $coupon['used']) {
                $coupon_data = [
                    'status' => -1
                ];

                if($db->autoUpdate('coupon', $coupon_data, '`id`='.$coupon['id'])) {
                    $db->destroy('coupon_rule_mapper', ['coupon_id' => $coupon['id']]);

                    $response['error'] = 0;
                    $response['message'] = '优惠券删除成功';
                } else {
                    $response['message'] = '删除优惠券失败';
                }
            } else {
                if ($db->destroy('coupon', ['id' => $id])) {
                    $db->destroy('coupon_rule_mapper', ['coupon_id' => $coupon['id']]);

                    $response['error'] = 0;
                    $response['message'] = '优惠券删除成功';
                } else {
                    $response['message'] = '删除优惠券失败';
                }
            }
        } else {
            $response['message'] = '优惠券不存在';
        }
    }
}

//更新优惠券状态
if($_SERVER['REQUEST_METHOD'] == 'PATCH' && !empty($_GET['opera']) && getGET('opera') == 'status') {
    if (!check_purview('pur_coupon_edit', $_SESSION['business_purview'])) {
        throw new RestFulException('没有操作权限', 503);
    }

    $id = intval(getPOST('id'));
    $status = intval(getPOST('status')) == 1 ? 1 : 0;

    if($id <= 0) {
        throw new RestFulException('参数错误', 400);
    }

    $coupon = $db->find('coupon', ['id'], ['id' => $id]);

    if(empty($coupon)) {
        throw new RestFulException('优惠券不存在', 401);
    }

    if($db->upgrade('coupon', ['status' => $status], ['id' => $id]) !== false) {
        $db->upgrade('coupon_rule_mapper', ['status' => $status], ['coupon_id' => $id]);
        $response['error'] = 0;
        $response['message'] = '更新优惠券成功';
    } else {
        $response['message'] = '更新优惠券失败';
    }
}

//===========================================================================
// 获取单个优惠券
if($_SERVER['REQUEST_METHOD'] == 'GET' && !empty($_GET['id']))
{
    $id = intval(getGET('id'));

    if($id <= 0) {
        $response['message'] = '参数错误';
    } else {
        $coupon = $db->find('coupon', '*', ['id' => $id]);

        if($coupon) {
            if(!empty($coupon['member_levels'])) {
                $coupon['member_levels'] = explode(',', $coupon['member_levels']);
            }

            if(!empty($coupon['shop_scope'])) {
                $coupon['shop_scope'] = explode(',', $coupon['shop_scope']);
            }

            if(!empty($coupon['category_scope'])) {
                $coupon['category_scope'] = explode(',', $coupon['category_scope']);
            }

            if(!empty($coupon['product_scope'])) {
                $coupon['product_scope'] = explode(',', $coupon['product_scope']);
            }

            $response['error'] = 0;
            $response['message'] = '';
            $response['coupon'] = $coupon;
        } else {
            $response['message'] = '优惠券不存在';
        }
    }
}

// 获取优惠券列表
if($_SERVER['REQUEST_METHOD'] == 'GET' && empty($_GET['id']))
{
    $page = intval(getGET('page'));
    $page_size = intval(getGET('page_size'));
    $page_size = max($page_size, 10);
    $page = max(1, $page);
    $offset = ($page - 1) * $page_size;
    $conditions = [];

    $keyword = getGET('keyword');

    if(!empty($keyword)) {
        $conditions['name'] = ['like', '%'.$keyword.'%'];
    }

    $begin_time = getGET('begin_time');
    $end_time = getGET('end_time');

    if(!empty($begin_time)) {
        $begin_time = strtotime(getGET('begin_time') . ' 00:00:00');
    } else {
        $begin_time = 0;
    }

    if(!empty($end_time)) {
        $end_time = strtotime(getGET('end_time') . ' 23:59:59');
    } else {
        $end_time = 0;
    }

    if($begin_time > 0) {
        $conditions['begin_time'] = ['egt', $begin_time];
    }

    if($end_time > 0 && $end_time > $begin_time) {
        $conditions['end_time'] = ['elt', $end_time];
    }

    $response['total'] = $db->getColumn('coupon', 'count(*)', $conditions);
    $coupons = $db->all('coupon', '*', $conditions, $offset.','.$page_size);


    $response['error'] = 0;
    $response['coupons'] = $coupons;
}

echo json_encode($response);