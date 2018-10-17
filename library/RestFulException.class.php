<?php

/**
 * Created by PhpStorm.
 * User: Winsen
 * Date: 2017/12/4
 * Time: 下午6:47
 */
class RestFulException extends Exception {
    public function __construct($message = "", $code = 0, Throwable $previous = null) {
        echo '{"error":'.$code.', "message":"'.$message.'"}';
        exit;
    }
}