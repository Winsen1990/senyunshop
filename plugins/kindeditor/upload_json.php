<?php
/**
 * KindEditor PHP
 *
 * 本PHP程序是演示程序，建议不要直接在实际项目中使用。
 * 如果您确定直接使用本程序，使用之前请仔细确认相关安全设置。
 *
 */

require_once 'JSON.php';

session_start();
if( !isset($_SESSION['business_account']) ) {
    alert('请先登陆商户管理后台');
}
$business_account = $_SESSION['business_account'];

$php_path = dirname(__FILE__) . '/';
$php_url = dirname($_SERVER['PHP_SELF']) . '/';

//文件保存目录路径
$save_path = $php_path . '../../upload/';
//文件保存目录URL
$save_url = $php_url . '../../upload/';
//定义允许上传的文件扩展名
$ext_arr = array(
	'image' => array('gif', 'jpg', 'jpeg', 'png', 'bmp'),
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

	//创建文件夹
	if ($dir_name !== '') {
		$save_path .= $dir_name . "/";
		$save_url .= $dir_name . "/";
		if (!file_exists($save_path)) {
			mkdir($save_path);
		}
        $save_path .= $business_account .'/';
        $save_url .= $business_account . '/';
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
    $file_url = $save_url . $new_file_name;
    $file_url = resize(realpath($file_path), $file_ext, $file_url);

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

function resize($filename, $type, $file_url) {

    $php_path = dirname(__FILE__) . '/';
    $php_url = dirname($_SERVER['PHP_SELF']) . '/';

    //文件保存目录路径
    $save_path = $php_path . '../../upload/';
    //文件保存目录URL
    $save_url = $php_url . '../../upload/';

    //upload下保存图片的目录
    $dir_name = 'image';

    //检查图片像素
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
    $limit_width = isset($_GET['width']) ? intval($_GET['width']) : 0;
    $limit_height = isset($_GET['height']) ? intval($_GET['height']) : 0;
    if( 0 >= $limit_width && 0 >= $limit_height ) {
        return $file_url;
    }

    //合并空白图片补白
    if( $pic_width <= $limit_width || $pic_height <= $limit_height ) {

        //计算起始坐标
        $x = ( $limit_width - $pic_width ) / 2;
        $y = ( $limit_height - $pic_height) / 2;
        if( $x == 0 && $y == 0 ) {
            return $file_url;
        }

        $new_im = imagecreatetruecolor($limit_width, $limit_height);
        //设置图像的背景颜色，白色
        imagecolorallocate($new_im, 255, 255, 255);

        //合并补白
        imagecopy($new_im, $im,$x, $y, 0, 0, $pic_width, $pic_height);
    } else {
        //计算缩略图的宽和高
        if( ($limit_width && $pic_width > $limit_width) || ($limit_height && $pic_height > $limit_height) ) {
            //需要缩放宽度
            if( $limit_width && $pic_width > $limit_width ) {
                $width_ratio = $limit_width/$pic_width;
                $resize_width_tag = true;
            }
            //需要缩放高度
            if( $limit_height && $pic_height>$limit_height ) {
                $height_ratio = $limit_height/$pic_height;
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
                $new_im = imagecreatetruecolor($new_width,$new_height);
                imagecopyresized($new_im,$im,0,0,0,0,$new_width,$new_height,$pic_width,$pic_height);
            }
            //宽度补白或者高度补白
            if( $new_width < $limit_width || $new_height < $limit_height ) {
                $temp_img = imagecreatetruecolor($limit_width, $limit_height);
                //设置图像的背景颜色，白色
                imagecolorallocate($temp_img, 255, 255, 255);
                //计算起始坐标
                $x = ( $limit_width - $new_width ) / 2;
                $y = ( $limit_height - $new_height) / 2;
                //合并补白
                imagecopy($temp_img, $new_im, $x, $y, 0, 0, $new_width, $new_height);
                imagedestroy($new_im);
                $new_im = $temp_img;

            }
        } else {
            $new_im = $im;
        }
    }
    //创建文件夹
    if ($dir_name !== '') {
        $save_path .= $dir_name . "/";
        $save_url .= $dir_name . "/";
        if (!file_exists($save_path)) {
            mkdir($save_path);
        }

        $save_path .= $_SESSION['business_account'] .'/';
        $save_url .= $_SESSION['business_account'] . '/';
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
    imagedestroy($im);
    imagedestroy($new_im);
    if( !$result ) {
        alert('图片缩放发生错误，请联系管理员'.$file_path);
    }
//    @unlink($filename);
    return $save_url . $temp[$temp_length - 1];

}


function create_thumb($filename, $type, $max_width = 100, $max_height = 75) {

    $php_path = dirname(__FILE__) . '/';
    $php_url = dirname($_SERVER['PHP_SELF']) . '/';

    //文件保存目录路径
    $save_path = $php_path . '../../upload/';
    //文件保存目录URL
    $save_url = $php_url . '../../upload/';

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
            $new_im = imagecreatetruecolor($new_width,$new_height);
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

        $save_path .= $_SESSION['business_account'] .'/';
        $save_url .= $_SESSION['business_account'] . '/';
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
        alert('生成缩略图发生错误, 请联系管理员');
    }
    return $save_url . $temp[$temp_length - 1];
}

