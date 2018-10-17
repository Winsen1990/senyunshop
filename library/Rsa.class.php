<?php  
/**
 * RSA加密/解密封装
 * @author winsen
 * @date 2014-10-22
 * @version 1.0.0
 */
class Rsa
{
    private $prik;
    private $pubk;

    // 使用RSA时，一般把公钥放在客户端进行解密，私钥放在服务端进行加密
    // 因此一次实例可能只使用公钥或者私钥的功能，不需要对其进行硬性初始化
    public function __construct()
    {
    }
    
    /**
     * 根据文件名获得一个公钥的资源对象
     * @param string $public_key 公钥的文件路径
     * @return return a resource link on success, FALSE on others
     */ 
    public function init_public_key($public_key)
    {
        if(!file_exists($public_key) || !fopen($public_key, 'r'))
        {
            return false;
        }
        $content = file_get_contents($public_key);
        if($content)
        {
            $this->pubk = openssl_get_publickey($content);

            return $this->pubk;
        } else {
            return false;
        }
    }

    /**
     * 根据文件名获得一个私钥的资源对象
     * @param string $private_key 私钥的文件路径
     * @return return a resource link on success, FALSE on others
     */
    public function init_private_key($private_key)
    {
        if(!file_exists($private_key) || !fopen($private_key, 'r'))
        {
            return false;
        }

        $content = file_get_contents($private_key);
        if($content)
        {
            $this->prik = openssl_get_privatekey($content);

            return $this->prik;
        } else {
            return false;
        }
    }

    /**
     * 对数据进行数字签名
     * @param string $data 待签名的数据
     * @param string $private_key 私钥的文件路径
     * @return 成功时返回签名字符串,其他情况下返回FALSE
     */
    public function sign($data, $private_key = '')
    {
        if($private_key)
        {
            if(!$this->init_private_key($private_key))
            {
                return false;
            }
        }

        $sign = '';
        openssl_sign($data, $sign, $this->prik);

        if($sign)
        {
            return $sign;
        } else {
            return false;
        }
    }

    /**
     * 使用私钥对数据进行加密
     * @param string $data 待加密的数据
     * @param string $private_key 私钥的文件路径
     * @param string $encode 对加密结果进行编码,默认:base64,可选hex、url
     * @return 成功时返回加密并且编码完成的数据,其他情况返回FALSE
     */
    public function private_key_encrypt($data, $encode = 'base64', $private_key = '')
    {
        if($private_key)
        {
            if(!$this->init_private_key($private_key))
            {
                return false;
            }
        }

        $data_length = strlen($data);

        $encrypt = '';
        //按每117个字符进行一次加密
        for($i = 0; $i < $data_length/117; $i++)
        {
            $temp = '';
            $substr = substr($data, $i * 117, 117);
            openssl_private_encrypt($substr, $temp, $this->prik);
            $encrypt .= $temp;
        }

        if($encrypt)
        {
            $encrypt = $this->encode($encrypt, $encode);
        }

        return empty($encrypt) ? false : $encrypt;
    }

    /**
     * 使用公钥对数据进行解密
     * @param string $data 待解密的数据
     * @param string $public_key 公钥的文件路径
     * @param string $decode 对待解密的数据进行反编码,默认:base64,可选hex、url
     * @return 成功时返回加密并且编码完成的数据,其他情况返回FALSE
     */
    public function public_key_decrypt($data, $encode = 'base64', $public_key = '')
    {
        if($public_key)
        {
            if(!$this->init_public_key($public_key))
            {
                return false;
            }
        }

        $data = $this->decode($data, $encode);
        $data_length = strlen($data);

        $decrypt = '';
        //按每128个字符进行一次解密
        for($i = 0; $i < $data_length/128; $i++)
        {
            $temp = '';
            $substr = substr($data, $i * 128, 128);
            if(!openssl_public_decrypt($substr, $temp, $this->pubk))
            {
                echo 'fail';
            }
            $decrypt .= $temp;
        }

        return empty($decrypt) ? false : $decrypt;
    }


    /**
     * 对数据进行编码
     * @param string $data 需要进行编码的数据
     * @param string $encode 编码类型
     * @return 返回编码后的字符串
     */
    private function encode($data, $encode)
    {
        switch($encode)
        {
        case 'base64':
                return base64_encode($data);
        case 'hex':
                return bin2hex($data);
        case 'url':
                return urlencode($data);
        default :
                return $data;
        }
    }

    /**
     * 对数据进行反编码
     * @param string $data 需要进行反编码的数据
     * @param string $decode 反编码类型
     * @return 返回反编码后的字符串
     */
    private function decode($data, $decode)
    {
        switch($decode)
        {
        case 'base64':
                return base64_decode($data);
        case 'hex':
                return hex2bin($data);
        case 'url':
                return urldecode($data);
        default :
                return $data;
        }
    }

    /**
     * 将16进制的编码变成2进制的编码
     * @param string 16进制的编码字符串
     * @return 返回对应的二进制字符串
     */
    private function hex2bin($data)
    {
        $len = strlen($data)/2;
        $re = '';
        for($i = 0; $i < $len; $i++)
        {
            $re .= chr(hexdec(substr($data, $i * 2, 1)) << 4) | chr(hexdec(substr($data, $pos+1, 1)));
        }

        return $re;
    }

    /**
     * 析构函数，用于资源回收
     */
    public function __destruct()
    {
        if($this->pubk)
        {
            openssl_free_key($this->pubk);
        }

        if($this->prik)
        {
            openssl_free_key($this->prik);
        }
    }
}
