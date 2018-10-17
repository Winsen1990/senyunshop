<?php

/**
 * Created by PhpStorm.
 * User: Winsen
 * Date: 2017/12/8
 * Time: 上午10:24
 */
interface WxResponse
{
    public function run();

    public function send();
}