<?php
/**
 * 自动加载器
 * @author winsen
 * @version 1.0.0
 */
class AutoLoader
{
    private static $obj;
    private $script_path = 'library/';
    private $script_format = '%s.inc.php';
    private $class_path = 'library/';
    private $class_format = '%s.class.php';
    private $interface_path = 'library/';
    private $interface_format = '%s.interf.php';

    /*
     * 构造函数,防止用户通过new创建实例
     * @return AutoLoader AutoLoader的实例
     * @author winsen
     */
    private function __construct()
    {
    }

    /**
     * 获得实例的方法
     * @return AutoLoader AutoLoader的实例
     * @author winsen
     */
    public static function getInstance()
    {
        if(is_null(self::$obj))
        {
            $class = __CLASS__;
            self::$obj = new $class;
        }

        return self::$obj;
    }

    /**
     * 自定义引入文件的格式和路径
     * @param mixed $config 待设置的参数以及取值
     *                      $config = array(
     *                          'script_path' => 'library/',
     *                          'script_format' => '%s.inc.php'
     *                      );
     * @return void
     * @author winsen
     */
    public function setConfigs($config)
    {
        if(is_array($config))
        {
            foreach($config as $key=>$value)
            {
                if(isset($this->$key))
                {
                    $this->$key = $value;
                } else {
                    echo 'AutoLoader error: Variable '.$key.' is not exists in AutoLoader.<br/>';
                }
            }
        }
    }

    /**
     * 类加载方法
     * @param mixed $class 要加载的类名
     * @return void
     * @author winsen
     */
    public function includeClass($class)
    {
        if(is_string($class))
        {
            $file_name = sprintf($this->class_format, $class);
            if(file_exists($this->class_path.$file_name))
            {
                if(!class_exists($class))
                {
                    include($this->class_path.$file_name);
                }
            } else {
                echo 'AutoLoader error: class file:"'.$this->class_path.$file_name.'" dosen\'t exists</br/>';
            }
        }

        if(is_array($class))
        {
            foreach($class as $c)
            {
                if(is_string($c))
                {
                    $file_name = sprintf($this->class_format, $c);
                    if(file_exists($this->class_path.$file_name))
                    {
                        if(!class_exists($c))
                        {
                            include($this->class_path.$file_name);
                        }
                    } else {
                        echo 'AutoLoader error: class file:"'.$this->class_path.$file_name.'" dosen\'t exists.</br/>';
                    }
                }
            }
        }
    }

    /**
     * 脚本程序加载方法
     * @param mixed $identity 文件名
     * @return void
     * @author winsen
     */
    public function includeScript($identity)
    {
        if(is_string($identity))
        {
            $file_name = sprintf($this->script_format, $identity);

            if(file_exists($this->script_path.$file_name))
            {
                include($this->script_path.$file_name);
            } else {
                echo 'AutoLoader error: script file "'.$this->script_path.$file_name.'" doesn\'t exists.<br/>';
            }
        }

        if(is_array($identity))
        {
            foreach($identity as $script)
            {
                if(is_string($script))
                {
                    $file_name = sprintf($this->script_format, $script);

                    if(file_exists($this->script_path.$file_name))
                    {
                        include($this->script_path.$file_name);
                    } else {
                        echo 'AutoLoader error: script file "'.$this->script_path.$file_name.'" doesn\'t exists.</br/>';
                    }
                }
            }
        }
    }

    /**
     * 接口文件加载方法
     * @param mixed $identity 文件名
     * @return void
     * @author winsen
     */
    public function includeInterface($identity)
    {
        if(is_string($identity))
        {
            $file_name = sprintf($this->interface_format, $identity);

            if(file_exists($this->interface_path.$file_name))
            {
                include($this->interface_path.$file_name);
            } else {
                echo 'AutoLoader error: script file "'.$this->interface_path.$file_name.'" doesn\'t exists.<br/>';
            }
        }

        if(is_array($identity))
        {
            foreach($identity as $script)
            {
                if(is_string($script))
                {
                    $file_name = sprintf($this->interface_format, $script);

                    if(file_exists($this->interface_path.$file_name))
                    {
                        include($this->interface_path.$file_name);
                    } else {
                        echo 'AutoLoader error: script file "'.$this->interface_path.$file_name.'" doesn\'t exists.</br/>';
                    }
                }
            }
        }
    }
}
