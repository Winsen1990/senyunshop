<?php
/**
 * 栏目上传处理文件
 * @author 王仁欢
 * @email wrh4285@163.com
 * @date 2015-08-07
 * @version 1.0.0
 */
include 'library/init.inc.php';
require_once 'JSON.php';

$php_path = dirname(__FILE__) . '/';
$php_url = dirname($_SERVER['PHP_SELF']) . '/';

//文件保存目录路径
$save_path = $php_path . '../upload/';
//文件保存目录URL
$save_url = $php_url . '../upload/';
//定义允许上传的文件扩展名，只允许上传图片
$ext_arr = array(
    'image' => array('gif', 'jpg', 'jpeg', 'png'),
    //'flash' => array('swf', 'flv'),
    //'media' => array('swf', 'flv', 'mp3', 'wav', 'wma', 'wmv', 'mid', 'avi', 'mpg', 'asf', 'rm', 'rmvb'),
    //'file' => array('doc', 'docx', 'xls', 'xlsx', 'ppt', 'htm', 'html', 'txt', 'zip', 'rar', 'gz', 'bz2'),
);
//最大文件大小
$max_size = 100000000;

$save_path = realpath($save_path) . '/';

//PHP上传失败
if (!empty($_FILES['imgFile']['error'])) {
    switch($_FILES['imgFile']['error']){
        case '1':
            $error = '超过php.ini允许的大小。';
            break;
        case '2':
            $error = '超过表单允许的大小。';
            break;
        case '3':
            $error = '图片只有部分被上传。';
            break;
        case '4':
            $error = '请选择图片。';
            break;
        case '6':
            $error = '找不到临时目录。';
            break;
        case '7':
            $error = '写文件到硬盘出错。';
            break;
        case '8':
            $error = 'File upload stopped by extension。';
            break;
        case '999':
        default:
            $error = '未知错误。';
    }
    alert($error);
}

//有上传文件时
if (empty($_FILES) === false) {
    //原文件名
    $file_name = $_FILES['imgFile']['name'];
    //服务器上临时文件名
    $tmp_name = $_FILES['imgFile']['tmp_name'];
    //文件大小
    $file_size = $_FILES['imgFile']['size'];
    //检查文件名
    if (!$file_name) {
        alert("请选择文件。");
    }
    //检查目录
    if (@is_dir($save_path) === false) {
        alert("上传目录不存在。");
    }
    //检查目录写权限
    if (@is_writable($save_path) === false) {
        alert("上传目录没有写权限。");
    }
    //检查是否已上传
    if (@is_uploaded_file($tmp_name) === false) {
        alert("上传失败。");
    }
    //检查文件大小
    if ($file_size > $max_size) {
        alert("上传文件大小超过限制。");
    }
    //检查目录名
    $dir_name = empty($_GET['dir']) ? 'image' : trim($_GET['dir']);
    if (empty($ext_arr[$dir_name])) {
        alert("目录名不正确。");
    }
    //获得文件扩展名
    $temp_arr = explode(".", $file_name);
    $file_ext = array_pop($temp_arr);
    $file_ext = trim($file_ext);
    $file_ext = strtolower($file_ext);
    //检查扩展名
    if (in_array($file_ext, $ext_arr[$dir_name]) === false) {
        alert("上传文件扩展名是不允许的扩展名。\n只允许" . implode(",", $ext_arr[$dir_name]) . "格式。");
    }
    //检查图片像素
    $im = null;
    switch($file_ext) {
        case 'jpg':
            $im = imagecreatefromjpeg($tmp_name);
            break;
        case 'jpeg':
            $im = imagecreatefromjpeg($tmp_name);
            break;
        case 'png':
            $im = imagecreatefrompng($tmp_name);
            break;
        case 'gif':
            $im = imagecreatefromgif($tmp_name);
            break;
        default:
            $im = imagecreatefromjpeg($tmp_name);
            break;
    }
    $pic_width = imagesx($im);
    $pic_height = imagesy($im);
    imagedestroy($im);
    if( $pic_width < intval($_GET['width']) && $pic_height < intval($_GET['height']) ) {
        alert("为保证用户体验，请使用建议尺寸的图片!");
    }
    //创建文件夹
    if ($dir_name !== '') {
        $save_path .= $dir_name . "/";
        $save_url .= $dir_name . "/";
        if (!file_exists($save_path)) {
            mkdir($save_path);
        }
    }
    $ymd = date("Ymd");
    $save_path .= $ymd . "/";
    $save_url .= $ymd . "/";
    if (!file_exists($save_path)) {
        mkdir($save_path);
    }
    //新文件名
    $new_file_name = date("YmdHis") . '_' . rand(10000, 99999) . '.' . $file_ext;
    //移动文件
    $file_path = $save_path . $new_file_name;
    if (move_uploaded_file($tmp_name, $file_path) === false) {
        alert("上传文件失败。");
    }
    @chmod($file_path, 0644);
    //创建缩略图

    $sthumb_width = intval(300);
    $sthumb_height = intval(300);

    $sthumb_width = ( 0 >= $sthumb_width ) ? null : $sthumb_width;
    $sthumb_height = ( 0 >= $sthumb_height ) ? null : $sthumb_height;

    if( $sthumb_width && $sthumb_height ) {
        create_thumb($file_path, $file_ext, $sthumb_width, $sthumb_height);
    } else if( empty($sthumb_width) && $sthumb_height) {
        create_thumb($file_path, $file_ext, null, $sthumb_height);
    } else if( $sthumb_width && empty($sthumb_height) ) {
        create_thumb($file_path, $file_ext, $sthumb_width );
    } else {
        create_thumb($file_path, $file_ext);
    }

    $file_url = $save_url . $new_file_name;

    header('Content-type: text/html; charset=UTF-8');
    $json = new Services_JSON();
    echo $json->encode(array('error' => 0, 'url' => $file_url));
    exit;
}

function alert($msg) {
    header('Content-type: text/html; charset=UTF-8');
    $json = new Services_JSON();
    echo $json->encode(array('error' => 1, 'message' => $msg));
    exit;
}


function create_thumb($filename, $type, $max_width = 75, $max_height = 75) {
    //文件保存目录路径
    $save_path = ROOT_PATH.'upload/';
    //文件保存目录URL
    $save_url = '/upload/';
    //upload下保存图片的目录
    $dir_name = 'thumb';

    $im = null;
    switch($type) {
        case 'jpg':
            $im = imagecreatefromjpeg($filename);
            break;
        case 'jpeg':
            $im = imagecreatefromjpeg($filename);
            break;
        case 'png':
            $im = imagecreatefrompng($filename);
            break;
        case 'gif':
            $im = imagecreatefromgif($filename);
            break;
        default:
            $im = imagecreatefromjpeg($filename);
            break;
    }
    $pic_width = imagesx($im);
    $pic_height = imagesy($im);

    //计算缩略图的宽和高
    if( ($max_width && $pic_width > $max_width) || ($max_height && $pic_height > $max_height) ) {
        //需要缩放宽度
        $resize_width_tag = false;
        $resize_height_tag = false;
        if( $max_width && $pic_width > $max_width ) {
            $width_ratio = $max_width/$pic_width;
            $resize_width_tag = true;
        }
        //需要缩放高度
        if( $max_height && $pic_height>$max_height ) {
            $height_ratio = $max_height/$pic_height;
            $resize_height_tag = true;
        }
        //宽度高度都要缩放，计算真正的缩放比
        if( $resize_width_tag && $resize_height_tag ) {
            if( $width_ratio < $height_ratio ) {
                $ratio = $width_ratio;
            }
            else {
                $ratio = $height_ratio;
            }
        }
        //按宽度缩放比缩放
        if( $resize_width_tag && !$resize_height_tag ) {
            $ratio = $width_ratio;
        }
        //按高度缩放比缩放
        if( $resize_height_tag && !$resize_width_tag ) {
            $ratio = $height_ratio;
        }

        //缩略图的宽度和高度
        $new_width = $pic_width * $ratio;
        $new_height = $pic_height * $ratio;

        if( function_exists("imagecopyresampled") ) {
            $new_im = imagecreatetruecolor($new_width,$new_height);
            imagecopyresampled($new_im,$im,0,0,0,0,$new_width,$new_height,$pic_width,$pic_height);
        } else {
            $new_im = imagecreate($new_width,$new_height);
            imagecopyresized($new_im,$im,0,0,0,0,$new_width,$new_height,$pic_width,$pic_height);
        }
    } else {
        $new_im = $im;
    }


    //创建文件夹
    if ($dir_name !== '') {
        $save_path .= $dir_name . "/";
        $save_url .= $dir_name . "/";
        if (!file_exists($save_path)) {
            mkdir($save_path);
        }
    }
    $ymd = date("Ymd");
    $save_path .= $ymd . "/";
    $save_url .= $ymd . "/";
    if ( !file_exists($save_path) ) {
        mkdir($save_path);
    }

    $temp = explode('/', $filename);
    $temp_length = count($temp);

    $file_path = $save_path . $temp[$temp_length - 1];
    //写入缩略图文件
    switch($type) {
        case 'jpg':
            $result = imagejpeg($new_im, $file_path);
            break;
        case 'jpeg':
            $result = imagejpeg($new_im, $file_path);
            break;
        case 'png':
            $result = imagepng($new_im, $file_path);
            break;
        case 'gif':
            $result = imagegif($new_im, $file_path);
            break;
        default:
            $result = imagejpeg($new_im, $file_path);
            break;
    }
    if( !$result ) {
        alert('生成缩略图发生错误');
    }
    return $save_url . $temp[$temp_length - 1];
}
