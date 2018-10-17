<?php
/**
 * 公共函数库
 * @author winsen
 * @version 1.0.0
 * @date 2015-07-28
 */

/**
 * 权限检查函数
 * @param int $sys_purview 系统定义的权限
 * @param int $user_purview 用户的权限
 * @return bool 拥有该权限时返回true,否则返回false
 * @author winsen
 * @date 2015-07-28
 */
function check_purview($sys_purview, $user_purview)
{
    $user_purview = json_decode($user_purview);
    $has_power = false;
    foreach( $user_purview as $key => $value ) {
        if( in_array($sys_purview, $value) ) {
            $has_power = true;
            break;
        }
    }
    return $has_power;
}

/**
 * 权限合并
 * @param array $user_purview 用户的权限
 * @param mixed $purviewList 需要合并的权限列表
 * @return int 返回合并后的权限
 * @author winsen
 * @date 2015-07-28
 */
function combile_purview($user_purview, $purview_list)
{
    if(is_array($purview_list))
    {
        $user_purview = array_merge($user_purview, $purview_list);
    }

    if(is_string($purview_list))
    {
        $user_purview[] = $purview_list;
    }

    $user_purview = array_flip($user_purview);
    $user_purview = array_flip($user_purview);
    ksort($user_purview);

    return $user_purview;
}

/**
 * smarty assign函数
 * @param string $var 参数名
 * @param mixed $value 参数值
 * @return void
 * @author winsen
 * @date 2015-07-28
 */
function assign($var, $value)
{
    global $smarty;
    $smarty->assign($var, $value);
}

/**
 * 获取GET的参数封装
 * @param string $var 参数名
 * @return mixed 返回对应的参数,如果参数不存在,则返回null
 * @author winsen
 * @date 2015-07-28
 */
function getGET($var)
{
    if(isset($_GET[$var]))
    {
        return $_GET[$var];
    } else {
        return null;
    }
}

/**
 * 获取POST的参数封装
 * @param string $var 参数名
 * @return mixed 返回对应的参数,如果参数不存在,则返回null
 * @author winsen
 * @date 2015-07-28
 */
function getPOST($var)
{
    if(isset($_POST[$var]))
    {
        return $_POST[$var];
    } else {
        return null;
    }
}

/**
 * 验证页面的act或opera值的合法性
 * @param string $needle 合法操作字符串,多个操作用|分隔开
 * @param string $search 待验证的操作
 * @param string $default 若为非法操作,则采用默认值替换
 * @author winsen
 * @date 2015-07-28
 */
function check_action($needle, $search, $default = '')
{
    if(!$needle || false === strpos($needle, $search))
    {
        return $default;
    } else {
        return $search;
    }
}

/**
 * 显示系统信息
 * @param string $msg 系统提示的文本信息
 * @param mixed $links 自动跳转以及其他链接
 * @param int $time 自动跳转计时
 * @return void
 * @author winsen
 * @date 2015-07-28
 */
function show_system_message($msg, $links = array(), $time = 5)
{
    global $smarty;
    assign('message', $msg);
    if(count($links) > 0)
    {
    	assign('link', $links[0]['link']);
        assign('links', $links);
    } else {
    	assign('link', $_SERVER['HTTP_REFERER']);
        assign('links', array(array('alt'=>'返回上一页', 'link'=> $_SERVER['HTTP_REFERER'])));
    }
    assign('time', $time);
    assign('page_title', '系统信息');
    $smarty->display('public/message.phtml');
    exit;
}

/**
 * 备份数据库
 * @param mixed $tables
 * @param bool $with_struct
 * @param bool $with_drop_table
 * @return string
 * @author winsen
 * @date 2015-07-28
 */
function backup($tables = null)
{
    global $db;

    $file_name = 'backup/db-backup-'.date('YmdHis').'.sql';

    if(!dir('backup'))
    {
        mkdir('backup');
    }

    $content = '';

    if(!$tables)
    {
        $tables = array();
        //不指定要备份的表，默认为完整备份整个数据库
        $get_tables = 'show tables;';

        $tables_tmp = $db->fetchAll($get_tables);
        foreach($tables_tmp as $value)
        {
            foreach($value as $table)
            {
                $tables[] = $table;
            }
        }
    } else if(is_string($tables)) {
        $tables = array($tables);
    }

    //备份结构和数据
    $create_sql_format = '%s;'."\n\n%s\n\n\n";
    $get_table_struct = 'show create table %s;';
    $get_table_data = 'select * from %s;';

    foreach($tables as $table)
    {
        $get_table_struct_sql = sprintf($get_table_struct, $table);
        $get_table_data_sql = sprintf($get_table_data, $table);

        $create_table_sql = $db->fetchRow($get_table_struct_sql);
        $cnt = 0;
        $table_type = 'table';
        foreach($create_table_sql as $key=>$value)
        {
            if($cnt == 1)
            {
                $create_table_sql .= $value;
                break;
            } else {
                if($key == 'Table')
                {
                    $create_table_sql = 'DROP TABLE IF EXISTS `'.$table.'`;'."\n"; 
                    $table_type = 'table';
                } else if($key == 'View') {
                    $create_table_sql = 'DROP VIEW IF EXISTS `'.$table.'`;'."\n";
                    $table_type = 'view';
                }
            }
            $cnt++;
        }

        $data_set = $db->fetchAll($get_table_data_sql);
        $record_count = count($data_set);
        $data_sql = '';
        if($record_count && $table_type == 'table')
        {
            for($i = 0; $i < $record_count; $i++)
            {
                if($i%256 == 0)
                {
                    $data_sql .= 'INSERT INTO `'.$table.'` VALUES (';
                } else {
                    $data_sql .= ' (';
                }

                foreach($data_set[$i] as $value)
                {
                    $data_sql .= '\''.addslashes($value).'\',';
                }
                $data_sql = substr($data_sql, 0, strlen($data_sql)-1);
                $data_sql .= ')';

                if($i != $record_count-1 && (($i+1)%256 != 0 || $i == 0))
                {
                    $data_sql .= ",\n";
                } else {
                    $data_sql .= ";\n";
                }
            }
        }

        $content .= sprintf($create_sql_format, $create_table_sql, $data_sql);
    }

    $handler = fopen($file_name, 'w');
    fwrite($handler, $content);
    fclose($handler);

    return $file_name;
}

/**
 * 检测管理员是否已登陆
 * @author 王仁欢
 * @date 2015-08-05
 * @return bool
 */
function check_admin_login()
{
    if(isset($_SESSION['purview']) && isset($_SESSION['admin_account']))
    {
        return true;
    } else {
       return false;
    }
}

/**
 * 重定向
 * @param $url
 * @author 王仁欢
 * @date 2015-08-05
 * @return void
 */
function redirect($url) {
    header('Location:'.$url);
    exit;
}

/**
 * 后台文件初始化，检查是否已登陆，根据权限生成菜单，assign通用信息
 * @author 王仁欢
 * @date 2015-08-05
 * @return void
 */
function back_base_init() {
    global $config;
    //是否已登陆
    if( !check_admin_login() ) {
        show_system_message('请先登陆', array(array('link' => 'index.php', 'alt' => '登陆')));
        exit;
    }

    $activeNav = get_active_nav();
    $realMenus = create_menu();
    if( $activeNav != 'main.php') {
        $is_main = false;
    } else {
        $is_main = true;
    }
    global $menus;
    $menuMark = array();
    foreach( $menus as $key => $menu ) {
        if( $activeNav == $menu['url'] ) {
            $menuMark['name'] = $realMenus[$menu['parent']]['key'];
            $menuMark['count'] = $realMenus[$menu['parent']]['count'];
            break;
        }
    }

    assign('menuMark', $menuMark);

    assign('is_main', $is_main);
    assign('activeNav', $activeNav);
    assign('pageTitle', $config['site_name'].'-管理后台');
    assign('currentAdmin', $_SESSION['admin_name']);

    //待处理商户数量
    global $db;
    $get_business_exam_count = 'select count(*) from '.$db->table('business').' where status = 1';
    $business_exam_count = $db->fetchOne($get_business_exam_count);

    $get_business_auth_count = 'select count(*) from '.$db->table('auth').' where status = 0';
    $business_auth_count = $db->fetchOne($get_business_auth_count);

    $business_deal_count = $business_auth_count + $business_exam_count;
    assign('business_deal_count', $business_deal_count);

    //待处理产品数量
    $get_product_exam_count = 'select count(*) from '.$db->table('product').' where status = 2';
    $product_exam_count = $db->fetchOne($get_product_exam_count);
    assign('product_exam_count', $product_exam_count);

    //待处理提现
    $get_member_withdraw_deal_count = 'select count(*) from'.$db->table('withdraw').' where status = 0';
    $member_withdraw_deal_count = $db->fetchOne($get_member_withdraw_deal_count);

    $get_business_withdraw_deal_count = 'select count(*) from'.$db->table('business_withdraw').' where status = 0';
    $business_withdraw_deal_count = $db->fetchOne($get_business_withdraw_deal_count);

    $withdraw_deal_count = $member_withdraw_deal_count + $business_withdraw_deal_count;

    //待处理充值
    $get_recharge_deal_count = 'select count(*) from '.$db->table('recharge').' where status = 2 and `type` = 1';
    $recharge_deal_count = $db->fetchOne($get_recharge_deal_count);

    $finance_count = $withdraw_deal_count + $recharge_deal_count;

    assign('member_withdraw_count', $member_withdraw_deal_count);
    assign('business_withdraw_count', $business_withdraw_deal_count);
    assign('withdraw_deal_count', $withdraw_deal_count);
    assign('recharge_deal_count', $recharge_deal_count);
    assign('finance_count', $finance_count);
}


/**
 * 生成后台菜单
 * @author 王仁欢
 * @date 2015-08-05
 * @return mixed
 */
function create_menu() {
    global $menus;
    global $topMenus;
    $purview = $_SESSION['purview'];
    $purview = json_decode($purview);
//    $menu = array();
    foreach($purview as $key => $value) {
        if( count($value) > 0 && isset($menus[$key])) {
            $menu = $menus[$key];
            $temp = $menus[$key]['parent'];//menu_nav
            if( isset( $topMenu[$temp]) ) {//exists
                $topMenu[$temp]['count']++;
                $topMenu[$temp]['children'][] = $menu;
            } else {
                $topMenu[$temp] = $topMenus[$temp];
                $topMenu[$temp]['key'] = $temp;
                $topMenu[$temp]['url'] = $menu['url'];
                $topMenu[$temp]['count'] = 1;
                $topMenu[$temp]['children'][] = $menu;
            }
        }
    }
    assign('menus', $topMenu);
    return $topMenu;
}

/**
 * @return string 当前文件名
 * @author 王仁欢
 *
 */
function get_active_nav() {
    $url = $_SERVER['PHP_SELF'];
    $filename= substr( $url , strrpos($url , '/')+1 );
    return $filename;
}

/**
 * 生成分页
 * @param $page 当前页
 * @param $total_page   总页数
 * @param $total    记录总数
 * @author 王仁欢
 * @return void
 *
 */
function create_pager($page, $total_page, $total) {
    $show_page = array();
    if( $page == 1 ) {
        for($i = 1; $i <= $total_page && $i <= 3; $i++) {
            $show_page[] = $i;
        }
        $go_first = false;  //首页
        $has_prev = false;  //上一页
        $has_many_prev = false; //前页省略号
        $has_next = ($total_page > 1) ? true : false;    //下一页
        $has_many_next = ($total_page > 3) ? true : false;   //后页省略号
        $go_last = ($total_page > 1) ? true : false; //末页
    } else if( $page == $total_page ) {   //必然不是第一页
        $i = ($total_page < 3) ? $page - 1 : $page - 2;
        for( ; $i <= $total_page; $i++ ) {
            $show_page[] = $i;
        }
        $go_first = true;
        $has_prev = true;
        $has_many_prev = ($total_page > 3) ? true : false;
        $has_next = false;
        $has_many_next = false;
        $go_last = false;
    } else {
        for($i = $page - 1; $i <= $total_page && $i <= $page + 1; $i++ ) {
            $show_page[] = $i;
        }
        $go_first = true;
        $has_prev = true;
        $has_many_prev = ($page > 3) ? true : false;
        $has_many_next = ( ($total_page - $page) > 2 ) ? true : false;
        $has_next = true;
        $go_last = true;
    }
    assign('show_page', $show_page);
    assign('go_first', $go_first);
    assign('has_prev', $has_prev);
    assign('has_many_prev', $has_many_prev);
    assign('has_many_next', $has_many_next);
    assign('has_next', $has_next);
    assign('go_last', $go_last);

    assign('page', $page);
    assign('total', $total);
    assign('totalPage', $total_page );
}

/**
 * CURL的GET方法
 * @param string $url 目标链接
 * @param string $params 附带的参数，格式为 param1=value1&param2=value2
 * @return string 通过GET方法获取到的网页数据
 * @author winsen
 * @date 2014-10-24
 */
function get($url, $params = '')
{
    return httpRequest($url, $params, 'get');
}

/**
 * CURL的POST方法
 * @param string $url 目标链接
 * @param string $params 附带的参数，格式为 param1=value1&param2=value2 或数组 array(param1=>value1,param2=>value2)
 * @param bool $encode 若参数为数组，则使用true（默认）
 * @return string 通过POST方法获取到的网页数据
 * @author winsen
 * @date 2014-10-24
 */
function post($url, $params = array(), $encode = true, $ssl_cert = null, $ssl_key = null)
{
    return httpRequest($url, $params, 'post', $encode, false, $ssl_cert, $ssl_key);
}

function httpRequest($url, $data, $method, $encode = true, $need_header = false, $ssl_cert = null, $ssl_key = null) {
    global $log;

    if(is_array($data) || is_object($data)) {
        $log->record_array($data);
    } else {
        $log->record($data);
    }

    $protocol = 'http';
    if(strpos($url, '://') !== false)
    {
        $explode_arr = explode('://', $url);
        $protocol = $explode_arr[0];
    }
    $method = strtoupper($method);
    $data_fields = '';
    if($encode && (is_array($data) || is_object($data)))
    {
        $data_fields = http_build_query($data);
    } else {
        $data_fields = $data;
    }
    if($method == 'GET' && $encode && $data_fields)
    {
        $url .= '?'.$data_fields;
    }
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    if($need_header)
    {
        curl_setopt($curl, CURLOPT_HEADER, 1);
    } else {
        curl_setopt($curl, CURLOPT_HEADER, 0);
    }
    switch ($method){
        case 'GET' :
            curl_setopt($curl, CURLOPT_HTTPGET, true);
            break;
        case 'POST':
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data_fields);
            break;
        case 'PATCH':
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PATCH');
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data_fields);
            break;
        case 'PUT':
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data_fields);
            break;
        case 'DELETE':
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data_fields);
            break;
    }
    if($protocol == 'https')
    {
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($curl, CURLOPT_SSLVERSION, 1);
    }

    if($ssl_cert) {
        curl_setopt($curl,CURLOPT_SSLCERTTYPE,'PEM');
        curl_setopt($curl,CURLOPT_SSLCERT, ROOT_PATH.$ssl_cert);
    }

    if($ssl_key) {
        curl_setopt($curl,CURLOPT_SSLKEYTYPE,'PEM');
        curl_setopt($curl,CURLOPT_SSLKEY, ROOT_PATH.$ssl_key);
    }

    $data = curl_exec($curl);
    curl_close($curl);
    return $data;
}

/**
 * 验证银行卡是否正确
 * @param $bank_card
 * @return bool
 */
function luhm_check($bank_card) {
    $length = strlen($bank_card);

    $last_num = substr($bank_card, $length-1, 1);
    $first_several_num = substr($bank_card, 0, $length - 1);
    $new_array = array();
    for( $i = $length - 1; $i >= 0; $i-- ) {
        array_push($new_array, substr($first_several_num, $i, 1));
    }
    $sum = 0;
    foreach( $new_array as $k => $v ) {
        if( ($k) % 2 == 1 ) {    //奇数位
            $temp = $v * 2;
            if( $temp <= 9 ) {
                $sum += $temp;
            } else {
                $sum += intval($temp / 10);
                $sum += $temp % 10;
            }
        } else {
            $sum += $v;
        }
    }
    //计算luhm
    $subtractor = ( ($sum % 10) == 0 ) ? 10 : ($sum % 10);
    $luhm = 10 - $subtractor;

    if( $last_num == $luhm ) {
        return true;
    } else {
        return false;
    }

}

/**
 * 图片缩放
 * @param $filename
 * @param int $max_width
 * @param int $max_height
 * @param $type
 */
function resize_image($filename, $type, $max_width = 100, $max_height = 75) {
    //文件保存目录路径
    $save_path = ROOT_PATH.'upload/';
    //文件保存目录URL
    $save_url = '/upload/';
    //upload下保存图片的目录
    $dir_name = 'image';

    $im = null;
    switch($type) {
        case 'jpg': $im = imagecreatefromjpeg(ROOT_PATH.$filename);break;
        case 'jpeg': $im = imagecreatefromjpeg(ROOT_PATH.$filename);break;
        case 'png': $im = imagecreatefrompng(ROOT_PATH.$filename);break;
        case 'gif': $im = imagecreatefromgif(ROOT_PATH.$filename);break;
        default: $im = imagecreatefromjpeg(ROOT_PATH.$filename);break;
    }
    $pic_width = imagesx($im);
    $pic_height = imagesy($im);

    if(($max_width && $pic_width > $max_width) || ($max_height && $pic_height > $max_height))
    {
        if($max_width && $pic_width>$max_width)
        {
            $widthratio = $max_width/$pic_width;
            $resizewidth_tag = true;
        }

        if($max_height && $pic_height>$max_height)
        {
            $heightratio = $max_height/$pic_height;
            $resizeheight_tag = true;
        }

        if($resizewidth_tag && $resizeheight_tag)
        {
            if($widthratio<$heightratio)
                $ratio = $widthratio;
            else
                $ratio = $heightratio;
        }

        if($resizewidth_tag && !$resizeheight_tag)
            $ratio = $widthratio;
        if($resizeheight_tag && !$resizewidth_tag)
            $ratio = $heightratio;

        $new_width = $pic_width * $ratio;
        $new_height = $pic_height * $ratio;

        if(function_exists("imagecopyresampled"))
        {
            $new_im = imagecreatetruecolor($new_width,$new_height);
            imagecopyresampled($new_im,$im,0,0,0,0,$new_width,$new_height,$pic_width,$pic_height);
        }
        else
        {
            $new_im = imagecreate($new_width,$new_height);
            imagecopyresized($new_im,$im,0,0,0,0,$new_width,$new_height,$pic_width,$pic_height);
        }
        //新文件名
        $new_file_name = date("YmdHis") . '_' . rand(10000, 99999) . '.' . $type;
    }
    else
    {
        //新文件名
        $new_file_name = date("YmdHis") . '_' . rand(10000, 99999) . '.' . $type;
        $new_im = $im;
    }


    //创建文件夹
    if ($dir_name !== '')
    {
        $save_path .= $dir_name . "/";
        $save_url .= $dir_name . "/";
        if (!file_exists($save_path))
        {
            mkdir($save_path);
        }
    }
    $ymd = date("Ymd");
    $save_path .= $ymd . "/";
    $save_url .= $ymd . "/";
    if (!file_exists($save_path))
    {
        mkdir($save_path);
    }

    $file_path = $save_path . $new_file_name;

    switch($type) {
        case 'jpg': imagejpeg($new_im, $file_path);break;
        case 'jpeg': imagejpeg($new_im, $file_path);break;
        case 'png': imagepng($new_im, $file_path);break;
        case 'gif': imagegif($new_im, $file_path);break;
        default: imagejpeg($new_im, $file_path);break;
    }
    return $save_url . $new_file_name;
}

/**
 * 判断是否跨域
 * @return bool 跨域返回true
 * @author 王仁欢
 */
function check_cross_domain() {
    $server_name = $_SERVER['SERVER_NAME'];//当前运行脚本所在服务器主机的名字。
    $sub_from = $_SERVER["HTTP_REFERER"];//链接到当前页面的前一页面的 URL 地址
    $sub_len = strlen($server_name);//统计服务器的名字长度。
    $check_from = substr($sub_from,8,$sub_len);//截取提交到前一页面的url，不包含http:://的部分。

    if( $check_from != $server_name ) {
        return true;
    } else {
        return false;
    }
}

/**
 *  快递100查询接口
 * @param $type 快递类型
 * @param $postid 快递单号
 * @param int $id 默认为1，作用暂时未知
 * @param string $valicode 默认为空
 * @return string json
 */
function query_express($type, $postid, $id = 1, $valicode = '') {
    $url = 'http://www.kuaidi100.com/query';
    $temp = rand(0, 100000000);
    $temp /= 100000000;
    $params = 'type='.$type.'&postid='.$postid.'&id='.$id.'&valicode='.$valicode.'&temp='.$temp;
    return get($url, $params);
}

/**
 * 验证手机号是否正确
 * @author 范鸿飞
 * @param INT $mobile
 * @return bool
 */
function is_mobile($mobile) {
    if (!is_numeric($mobile)) {
        return false;
    }
    return preg_match('#^13[\d]{9}$|^14[5,7]{1}\d{8}$|^15[^4]{1}\d{8}$|^17[0,6,7,8]{1}\d{8}$|^18[\d]{9}$#', $mobile) ? true : false;
}

function mobile_mask($mobile) {
    return substr($mobile, 0, 3).'****'.substr($mobile, -4);
}

/**
 * @param $mobile
 * @param $message
 * @return bool
 */
function sendSMS($mobile, $message)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "http://sms-api.luosimao.com/v1/send.json");

    curl_setopt($ch, CURLOPT_HTTP_VERSION  , CURL_HTTP_VERSION_1_0 );
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_HEADER, FALSE);

    curl_setopt($ch, CURLOPT_HTTPAUTH , CURLAUTH_BASIC);
    curl_setopt($ch, CURLOPT_USERPWD  , 'api:key-f62333d82eb7c7118c262c8286fc9a34');

    curl_setopt($ch, CURLOPT_POST, TRUE);
    curl_setopt($ch, CURLOPT_POSTFIELDS, array('mobile' => $mobile,'message' => $message.'【舞娇娘】'));

    $res = curl_exec( $ch );
    curl_close( $ch );
    //$res  = curl_error( $ch );
    global $log;
    $log->record('发送短信:'.$res);

    return true;
}

function decodeUnicode($str)
{
    return preg_replace_callback('/\\\\u([0-9a-f]{4})/i',
        create_function(
            '$matches',
            'return mb_convert_encoding(pack("H*", $matches[1]), "UTF-8", "UCS-2BE");'
        ),
        $str);
}

function is_email($email) {
    $pattern = "/^([0-9A-Za-z\\-_\\.]+)@([0-9a-z]+\\.[a-z]{2,3}(\\.[a-z]{2})?)$/i";
    return preg_match($pattern, $email);
}

function build_url($url) {
    if( strpos($_SERVER['SERVER_NAME'], 'localhost') ) {  //apache无虚拟主机
        return '../../'.$url;
    } elseif( is_numeric(strpos($_SERVER['SCRIPT_NAME'], '/pc')) ) { //手机域名访问pc端
        return '../'.$url;
    } else {    //pc端虚拟主机
        if( $_SERVER['SERVER_NAME'] == 'www.sbx3721.com' ) {
//            return 'http://sbx.kwanson.com/' . $url;
            return 'http://m.sbx3721.com/'.$url;
        } else {
            return $url;
        }
    }
}

/**
 *身份证号码检查接口函数
 *@params string $idcard 身份证号码
 *@return bool 是否正确
 */
function check_identity_num($identity_num) {
    if(strlen($identity_num) == 15 || strlen($identity_num) == 18){
        if(strlen($identity_num) == 15){
            $identity_num = idcard_15to18($identity_num);
        }
        if(idcard_checksum18($identity_num)){
            return true;
        }else{
            return false;
        }
    }else{
        return false;
    }
}

function idcard_verify_number($idcard_base){
    if (strlen($idcard_base) != 17){
        return false;
    }
    $factor = array(7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2); //debug 加权因子
    $verify_number_list = array('1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2'); //debug 校验码对应值
    $checksum = 0;
    for ($i = 0; $i < strlen($idcard_base); $i++){
        $checksum += substr($idcard_base, $i, 1) * $factor[$i];
    }
    $mod = $checksum % 11;
    $verify_number = $verify_number_list[$mod];
    return $verify_number;
}

function idcard_15to18($idcard){
    if (strlen($idcard) != 15){
        return false;
    }else{// 如果身份证顺序码是996 997 998 999，这些是为百岁以上老人的特殊编码
        if (array_search(substr($idcard, 12, 3), array('996', '997', '998', '999')) !== false){
            $idcard = substr($idcard, 0, 6) . '18'. substr($idcard, 6, 9);
        }else{
            $idcard = substr($idcard, 0, 6) . '19'. substr($idcard, 6, 9);
        }
    }
    $idcard = $idcard . idcard_verify_number($idcard);
    return $idcard;
}


function idcard_checksum18($idcard){
    if (strlen($idcard) != 18){ return false; }
    $idcard_base = substr($idcard, 0, 17);
    if (idcard_verify_number($idcard_base) != strtoupper(substr($idcard, 17, 1))){
        return false;
    }else{
        return true;
    }
}

function image_merge($source, $dest, $qrcode_file, $text, $dest_file)
{
    $src = imagecreatefromjpeg($source);
    $dest = imagecreatefromjpeg($dest);
    $qrcode = imagecreatefromjpeg($qrcode_file);

    list($width, $height) = getimagesize($source);
    $image_p = imagecreatetruecolor(130, 130);
    imagecopyresampled($image_p, $src, 0, 0, 0, 0, 130, 130, $width, $height);
    $src = $image_p;

    list($qwidth, $qheight) = getimagesize($qrcode_file);
    $image_qp = imagecreatetruecolor(185, 185);
    imagecopyresampled($image_qp, $qrcode, 0, 0, 0, 0, 185, 185, $qwidth, $qheight);
    $qrcode = $image_qp;

    if($text != '')
    {
        // Allocate colors
        $font_color = imagecolorallocate($dest, 249, 150, 10);

        // Write the font to the image
        imagefttext($dest, 22, 0, 270, 70, $font_color, ROOT_PATH.'library/font/Yahei.ttf', $text);
    }

    imagecopymerge($dest, $src, 48,18,0,0,130,130,100);
    imagedestroy($src);

    imagecopymerge($dest, $qrcode, 270,856,0,0,185,185,100);
    imagedestroy($qrcode);

    if(imagepng($dest, $dest_file))
    {
        return $dest_file;
        imagedestroy($dest);
    }

    imagedestroy($dest);
}

//判断是否是手机
function is_mobile_agent()
{
    $agent = strtolower($_SERVER['HTTP_USER_AGENT']);
    $is_pc = (strpos($agent, 'windows nt')) ? true : false;
    $is_mac = (strpos($agent, 'mac os')) ? true : false;
    $is_iphone = (strpos($agent, 'iphone')) ? true : false;
    $is_android = (strpos($agent, 'android')) ? true : false;
    $is_ipad = (strpos($agent, 'ipad')) ? true : false;



    if($is_iphone){
        return true;
    }

    if($is_android){
        return true;
    }

    if($is_ipad){
        return true;
    }

    if($is_pc){
        return  false;
    }

    if($is_mac){
        return  false;
    }
}