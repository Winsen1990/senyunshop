<?php
/**
 * Created by PhpStorm.
 * User: apple
 * Date: 2017/8/29
 * Time: 上午11:20
 */
session_start();

foreach($_SESSION as $key=>$value) {
    unset($_SESSION[$key]);
}

header('Location: index.php');