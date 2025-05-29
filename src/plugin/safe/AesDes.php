<?php

namespace AloneFrame\base\plugin\safe;

use phpseclib3\Crypt\AES;
use phpseclib3\Crypt\DES;
use AloneFrame\base\Frame;
use phpseclib3\Crypt\TripleDES as DES3;

trait AesDes {
    public array $data = [];

    public array $list = [
        'AES'  => [
            'class'  => AES::class,
            'length' => [128, 192, 256],//不同位数key长度不一样
            'key'    => [16, 24, 32],//128=16,192=24,256=32
            'iv'     => [16]
        ],
        'DES'  => [
            'class'  => DES::class,
            'length' => false,
            'key'    => [8],
            'iv'     => [8]
        ],
        'DES3' => [
            'class'  => DES3::class,
            'length' => [192],
            'key'    => [16, 24],//一般使用24
            'iv'     => [8]
        ],
    ];

    /**
     * 验证
     * @param      $data
     * @param bool $pattern true=加密,false=解密
     * @param bool $url
     * @return mixed
     */
    protected function verify($data, bool $pattern, bool $url): mixed {
        $class = $this->list('class');
        $key = $this->data('key');
        $key_ = $this->list('key', []);
        $iv = $this->data('iv');
        $iv_ = $this->list('iv', []);
        $length = $this->data('length');
        $length_ = $this->list('length', []);
        $type = $this->data('type');
        $mode = '';
        if (empty($class)) {
            return $this->error('The encryption type is incorrect', 401);
        }
        if (!empty($key_)) {
            if (empty($key))
                return $this->error('(key)This algorithm. Only keys of sizes ' . join(',', $key_) . ' supported', 402);
            if ($type == 'AES') {
                $aes_key = array_search($length, $length_);
                if ($aes_key === false)
                    return $this->error('(length)Key of size ' . $length . ' not supported by this algorithm. Only keys of sizes ' . join(',', $length_) . ' supported', 404);
                $aes_length = ($key_[$aes_key] ?? '');
                if ($aes_length != strlen($key))
                    return $this->error('(key)Key of size ' . strlen($key) . ' not supported by this algorithm. Only keys of sizes ' . $length . '=' . $aes_length . ' supported', 405);

            } else {
                if (empty(in_array(strlen($key), $key_)))
                    return $this->error('(key)Key of size ' . strlen($key) . ' not supported by this algorithm. Only keys of sizes ' . join(',', $key_) . ' supported', 403);
            }
            $mode = 'ECB';
        }
        if (!empty($iv) && !empty($iv_)) {
            if (empty(in_array(strlen($iv), $iv_)))
                return $this->error('(iv)Received initialization vector of size ' . strlen($iv) . ', but size ' . join(',', $iv_) . ' is required', 406);
            $mode = 'CBC';
        }
        if (!empty($length_)) {
            if (empty($length))
                return $this->error('(length)This algorithm. Only keys of sizes ' . join(',', $length_) . ' supported', 407);
            if (empty(in_array($length, $length_)))
                return $this->error('(length)Key of size ' . $length . ' not supported by this algorithm. Only keys of sizes ' . join(',', $length_) . ' supported', 408);
        }
        $mode = $this->data('mode') ?: $mode;
        $opt = $this->data('opt');
        if (!empty($opt)) {
            if ($type == 'AES') {
                $cipher_algo = 'AES-' . $this->data('length') . '-' . $mode;
            } elseif ($mode == 'DES') {
                $cipher_algo = 'DES-' . $mode;
            } else {
                $cipher_algo = 'DES-EDE3' . '-' . $mode;
            }
            if (!empty($pattern)) {
                $content = openssl_encrypt($data, $cipher_algo, $key, OPENSSL_RAW_DATA, $iv);
            } else {
                $content = openssl_decrypt($data, $cipher_algo, $key, OPENSSL_RAW_DATA, $iv);
            }
        } else {
            $safe = (new $class(strtoupper($mode)));
            $safe->setKey($key);
            if (!empty($iv))
                $safe->setIV($iv);
            if ($length > 0)
                $safe->setKeyLength($length);
            if (!empty($pattern)) {
                $content = $safe->encrypt($data);
            } else {
                $content = $safe->decrypt($data);
            }
        }
        if (!empty($pattern)) {
            $content = base64_encode($content);
            $content = (!empty($url) ? urlencode($content) : $content);
        }
        return $content;
    }

    /**
     * @param string $type //加密类型aes,des,des3
     * @param int    $length
     */
    public function __construct(string $type, int $length = 0) {
        $this->data['type'] = strtoupper($type);
        $this->data['length'] = $length;
    }

    /**
     * 获取设置数据
     * @param string       $key
     * @param mixed|string $default
     * @return mixed
     */
    public function data(string $key, mixed $default = ''): mixed {
        return Frame::getArr($this->data, $key, $default);
    }

    /**
     * 获取配置
     * @param string       $key
     * @param mixed|string $default
     * @return mixed
     */
    public function list(string $key, mixed $default = ''): mixed {
        return Frame::getArr($this->list, strtoupper($this->data('type')) . '.' . $key, $default);
    }

    /**
     * 报错
     * @param string $msg
     * @param int    $code
     * @return array
     */
    public function error(string $msg, int $code = 400): array {
        $data['msg'] = $msg;
        $data['code'] = $code;
        return $data;
    }
}