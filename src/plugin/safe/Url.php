<?php

namespace AloneFrame\base\plugin\safe;

use AloneFrame\base\Frame;
use Throwable;

trait Url {

    /**
     * @param array $arr
     * @param array $mode
     * @return string
     */
    public static function urlEn(array $arr, array $mode = ['aes', 'des', 'des3']): string {
        return static::urlEncrypt($arr, function($safe) use ($mode) {
            return static::movEn($safe, $mode);
        });
    }

    /**
     * @param string $data
     * @return array
     */
    public static function urlDe(string $data): array {
        return static::urlDecrypt($data, function($safe) {
            return static::movDe($safe);
        });
    }

    /**
     * @param array $arr
     * @param array $mode
     * @return string
     */
    public static function urlEns(array $arr, array $mode = ['aes', 'des', 'des3']): string {
        return static::urlEncrypt($arr, function($safe) use ($mode) {
            return static::movEns($safe, $mode);
        });
    }

    /**
     * @param string $data
     * @return array
     */
    public static function urlDes(string $data): array {
        return static::urlDecrypt($data, function($safe) {
            return static::movDes($safe);
        });
    }

    /**
     * @param array    $arr
     * @param callable $callable
     * @return string
     */
    protected static function urlEncrypt(array $arr, callable $callable): string {
        $safe = $callable($arr);
        $json = json_encode($safe);
        $base = base64_encode($json);
        return str_replace(['+', '/', '='], ['-', '_', ''], $base);
    }

    /**
     * @param string   $data
     * @param callable $callable
     * @return array
     */
    protected static function urlDecrypt(string $data, callable $callable): array {
        try {
            if (!empty($data)
                && !empty($data = urldecode($data))
                && !empty($json = base64_decode(str_replace(['-', '_'], ['+', '/'], $data)))
                && !empty($safe = Frame::isJson($json))
                && !empty($string = $callable($safe))
                && !empty($arr = Frame::isJson($string))
            ) {
                return $arr;
            }
            return [];
        } catch (Throwable $e) {
            return [];
        }
    }
}