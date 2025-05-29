<?php

namespace AloneFrame\base\plugin\safe;

use Throwable;
use AloneFrame\base\Frame;

trait Mov {
    public static array $setMov = [
        'aes'  => [0, 1, 2, 3],
        'des'  => [4, 5],
        'des3' => [6, 7, 8, 9],
    ];

    /**
     * 解密验证arr
     * 动态加密使用phpseclib3
     * @param array $arr
     * @return array
     */
    public static function movDeArr(array $arr): array {
        return static::arrDecrypt($arr, function($data, $random) {
            return static::movDe($data, $random);
        });
    }

    /**
     * 解密验证arr
     * 动态加密使用openssl
     * @param array $arr
     * @return array
     */
    public static function movDesArr(array $arr): array {
        return static::arrDecrypt($arr, function($data, $random) {
            return static::movDes($data, $random);
        });
    }

    /**
     * 动态加密使用phpseclib3
     * @param       $data
     * @param array $mode
     * @param bool  $opt
     * @return array
     */
    public static function movEn($data, array $mode = ['aes', 'des', 'des3'], bool $opt = false): array {
        return call_user_func_array([self::class, ($mode[rand(0, (count($mode) - 1))] ?? 'des3') . 'En'], [$data, ($opt ? 's' : '')]);
    }

    /**
     * 动态加密使用openssl
     * @param                $data
     * @param array|string[] $mode
     * @param bool           $opt
     * @return array
     */
    public static function movEns($data, array $mode = ['aes', 'des', 'des3'], bool $opt = true): array {
        return self::movEn($data, $mode, $opt);
    }

    /**
     * 动态解密使用phpseclib3
     * @param       $data
     * @param mixed $random
     * @param bool  $opt
     * @return string
     */
    public static function movDe($data, mixed $random = null, bool $opt = false): string {
        if (empty($random)) {
            $random = $data['random'] ?? $random;
            $data = $data['data'] ?? $data;
        }
        $type = substr($random, 0, 1);
        if (in_array($type, self::$setMov['aes'])) {
            $method = 'aesDe';
        } elseif (in_array($type, self::$setMov['des'])) {
            $method = 'desDe';
        } else {
            $method = 'des3De';
        }
        return call_user_func_array([self::class, $method], [$data, $random, ($opt ? 's' : '')]);
    }

    /**
     * 动态解密使用openssl
     * @param       $data
     * @param mixed $random
     * @param bool  $opt
     * @return string
     */
    public static function movDes($data, mixed $random = null, bool $opt = true): string {
        return self::movDe($data, $random, $opt);
    }

    /**
     * aes动态加密
     * @param        $data
     * @param string $opt
     * @return array
     */
    public static function aesEn($data, string $opt = ''): array {
        $rand = self::rand();
        $iv = rand(1, 16);
        $md5 = md5($rand);
        $type = rand(1, 2);
        $array['random'] = self::randInt('aes') . $type . $rand . (strlen($iv) == 1 ? '0' . $iv : $iv);
        $array['data'] = call_user_func_array([self::class, 'aesEncrypt' . $opt], [(is_array($data) ? json_encode($data, JSON_UNESCAPED_UNICODE + JSON_UNESCAPED_SLASHES) : $data), substr($md5, $iv, 16), ($type == 2 ? substr(md5($md5), $iv, 16) : '')]);
        return $array;
    }

    /**
     * aes动态解密
     * @param        $data
     * @param        $random
     * @param string $opt
     * @return string
     */
    public static function aesDe($data, $random = null, string $opt = ''): string {
        if (empty($random)) {
            $random = $data['random'] ?? $random;
            $data = $data['data'] ?? $data;
        }
        $md5 = md5(substr($random, 2, strlen($random) - 4));
        $iv = substr($random, -2);
        $type = substr($random, 1, 1);
        return call_user_func_array([self::class, 'aesDecrypt' . $opt], [$data, substr($md5, $iv, 16), ($type == 2 ? substr(md5($md5), $iv, 16) : '')]);
    }

    /**
     * des动态加密
     * @param        $data
     * @param string $opt
     * @return array
     */
    public static function desEn($data, string $opt = ''): array {
        $rand = self::rand();
        $iv = rand(1, 8);
        $md5 = md5($rand);
        $type = rand(1, 2);
        $array['random'] = self::randInt('des') . $type . $rand . (strlen($iv) == 1 ? '0' . $iv : $iv);
        $array['data'] = call_user_func_array([self::class, 'desEncrypt' . $opt], [(is_array($data) ? json_encode($data, JSON_UNESCAPED_UNICODE + JSON_UNESCAPED_SLASHES) : $data), substr($md5, $iv, 8), ($type == 2 ? substr(md5($md5), $iv, 8) : '')]);
        return $array;
    }

    /**
     * des动态解密
     * @param        $data
     * @param        $random
     * @param string $opt
     * @return string
     */
    public static function desDe($data, $random = null, string $opt = ''): string {
        if (empty($random)) {
            $random = $data['random'] ?? $random;
            $data = $data['data'] ?? $data;
        }
        $md5 = md5(substr($random, 2, strlen($random) - 4));
        $iv = substr($random, -2);
        $type = substr($random, 1, 1);
        return call_user_func_array([self::class, 'desDecrypt' . $opt], [$data, substr($md5, $iv, 8), ($type == 2 ? substr(md5($md5), $iv, 8) : '')]);
    }

    /**
     * des3动态加密
     * @param        $data
     * @param string $opt
     * @return array
     */
    public static function des3En($data, string $opt = ''): array {
        $rand = self::rand();
        $iv = rand(1, 8);
        $md5 = md5($rand);
        $type = rand(1, 2);
        $array['random'] = self::randInt('des3') . $type . $rand . (strlen($iv) == 1 ? '0' . $iv : $iv);
        $array['data'] = call_user_func_array([self::class, 'des3Encrypt' . $opt], [(is_array($data) ? json_encode($data, JSON_UNESCAPED_UNICODE + JSON_UNESCAPED_SLASHES) : $data), substr($md5, $iv, 24), ($type == 2 ? substr(md5($md5), $iv, 8) : '')]);
        return $array;
    }

    /**
     * des3动态解密
     * @param        $data
     * @param        $random
     * @param string $opt
     * @return string
     */
    public static function des3De($data, $random = null, string $opt = ''): string {
        if (empty($random)) {
            $random = $data['random'] ?? $random;
            $data = $data['data'] ?? $data;
        }
        $md5 = md5(substr($random, 2, strlen($random) - 4));
        $iv = substr($random, -2);
        $type = substr($random, 1, 1);
        return call_user_func_array([self::class, 'des3Decrypt' . $opt], [$data, substr($md5, $iv, 24), ($type == 2 ? substr(md5($md5), $iv, 8) : '')]);
    }


    /**
     * 生成随机
     * @param int $length
     * @return string
     */
    public static function rand(int $length = 32): string {
        return substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890'), 0, $length);
    }

    /**
     * @param $mode
     * @return int
     */
    public static function randInt($mode): int {
        $arr = self::$setMov[$mode];
        return $arr[rand(0, (count($arr) - 1))];
    }

    /**
     * @param array    $arr
     * @param callable $callable
     * @param array    $array
     * @return mixed
     */
    protected static function arrDecrypt(array $arr, callable $callable, array $array = []): mixed {
        try {
            if (!empty($data = Frame::getArr($arr, 'data')) && !empty($random = Frame::getArr($arr, 'random'))) {
                if (!empty($json = $callable($data, $random))) {
                    if (!empty($array = Frame::isJson($json))) {
                        return $array;
                    }
                }
            }
            return $array;
        } catch (Throwable $e) {
            return $array;
        }
    }
}