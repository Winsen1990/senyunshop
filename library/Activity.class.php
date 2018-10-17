<?php

/**
 * Created by PhpStorm.
 * User: Winsen
 * Date: 2017/12/8
 * Time: 下午3:08
 */
abstract class Activity
{
    protected $url;
    protected $name;
    protected $param = array();
    protected $thumb;
    protected $config = array();
    protected $has_reward;
    protected $reward_param = array();

    abstract public function validateParams(&$params);

    abstract public function validateRewardParams(&$params);
}