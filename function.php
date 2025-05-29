<?php

use think\Validate;
use support\Request;
use support\Response;
use AloneFrame\base\Frame;
use AloneFrame\base\ThinkValidate;
use AloneFrame\base\expand\Check;
use Workerman\Protocols\Http\Chunk;

//alone根目录
function alone_path(string $path = ''): string {
    return Frame::dirPath(realpath(__DIR__ . '/../'), $path);
}

//运行根目录
function alone_root_path(string $path = ''): string {
    $path = Frame::dirPath(realpath(__DIR__ . '/../../'), $path);
    $basePath = strtolower(basename($path));
    return $basePath == 'vendor' ? dirname($path) : (in_array($basePath, ['alone', 'frame']) ? dirname($path, 2) : $path);
}

/**
 * think验证数据
 * @param array         $data  要验证的数据
 * @param array         $rules 验证规则
 * @param Validate|null $validate
 * @return array
 */
function alone_check(array $data, array $rules, Validate|null $validate = null): array {
    //支持以下写法,key和官方一样
    //https://doc.thinkphp.cn/@think-validate/rule_buildin.html
    /*
        $rules = [
            'name' => '不能为空',//这种不支持array
            'pass' => function ($val) {
                return $val == '123456' ? true : '密码错误';
            },
            'email' => [
                'require' => '邮箱不能为空',
                'email' => '邮箱格式错误',
                'max:25' => ['code' => 401, 'msg' => '名称最多不能超过25个字符'],
                'length:3,25' => '402|只能在3-25之间',
                'regex:/^[a-zA-Z]+$/' => function () {
                    return '只能包含英文字符';
                }
            ]
        ];
     */
    return ThinkValidate::alone($data, $rules, $validate);
}

if (!function_exists('ps')) {
    //pre输出
    function ps(mixed $data, bool $echo = true): string {
        $content = '<pre>' . print_r($data, true) . '</pre>';
        if (!empty($echo)) {
            echo $content;
            return '';
        }
        return $content;
    }
}

if (!function_exists('ts')) {
    //textarea
    function ts(string $data, string|int $row = 30, string|int $cols = 200, bool $echo = true): string {
        $content = '<textarea rows="' . $row . '" cols="' . $cols . '">' . $data . '</textarea>';
        if (!empty($echo)) {
            echo $content;
            return '';
        }
        return $content;
    }
}

if (!function_exists('alone_aes_encrypt')) {
    /**
     * aes加密
     * @param string|array $data
     * @param string       $key
     * @return string
     */
    function alone_aes_encrypt(string|array $data, string $key): string {
        $mode = "aes-256-gcm";
        $data = is_array($data) ? json_encode($data) : $data;
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($mode));
        $encrypted = openssl_encrypt($data, $mode, $key, OPENSSL_RAW_DATA, $iv, $tag);
        return base64_encode($iv . $tag . $encrypted);
    }
}


if (!function_exists('alone_aes_decrypt')) {
    /**
     * aes解密
     * @param string $encrypted
     * @param string $key
     * @return bool|string
     */
    function alone_aes_decrypt(string $encrypted, string $key): bool|string {
        $mode = "aes-256-gcm";
        $data = base64_decode($encrypted);
        $iv_length = openssl_cipher_iv_length($mode);
        $iv = substr($data, 0, $iv_length);
        $tag = substr($data, $iv_length, 16);
        $encryptedData = substr($data, $iv_length + 16);
        return openssl_decrypt($encryptedData, $mode, $key, OPENSSL_RAW_DATA, $iv, $tag);
    }
}


if (!function_exists('alone_aes_en')) {
    function alone_aes_en($data, $key, $iv): string {
        return base64_encode(openssl_encrypt(is_array($data) ? json_encode($data) : $data, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv));
    }
}

if (!function_exists('alone_aes_de')) {
    function alone_aes_de($data, $key, $iv): bool|string {
        return openssl_decrypt(base64_decode($data), 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
    }
}

if (!function_exists('alone_js')) {
    function alone_js(): string {
        $list = [
            '1-plugin.js',
            '2-frame.js',
            '3-safe.js'
        ];
        $str = "";
        foreach ($list as $k => $v) {
            $str .= file_get_contents(__DIR__ . '/file/js/' . $v) . "\r\n";
        }
        return $str;
    }
}


if (!function_exists('alone_js_file')) {
    function alone_js_file(): string {
        $file = __DIR__ . '/file/js/frame.js';
        if (!is_file($file)) {
            file_put_contents($file, alone_js());
        }
        return $file;
    }
}


if (!function_exists('alone_view')) {
    /**
     * 渲染视图模板
     * @param string      $template 模板文件名（不包含扩展名）
     * @param array       $vars     传递给模板的变量
     * @param string|null $path     模板文件路径
     * @param string      $suffix   模板文件后缀名
     * @return string
     */
    function alone_view(string $template, array $vars = [], string|null $path = null, string $suffix = 'html'): string {
        $templatePath = rtrim($path, '/\\') . '/' . trim($template, '/\\') . '.' . $suffix;
        if (is_file($templatePath)) {
            extract($vars);
            ob_start();
            try {
                include $templatePath;
            } catch (Throwable $e) {
                ob_end_clean();
                return "error: " . $e->getMessage();
            }
            return ob_get_clean();
        }
        return "not file: {$templatePath}";
    }
}

/**
 * 获取ip
 * @param Request|null $req
 * @return string
 */
function get_ip_adder(Request|null $req = null): string {
    $req = $req ?? request();
    $ip = $req->header('cf-connecting-ip', '');
    if (empty($ip)) {
        $ipList = $req->header('x-forwarded-for', '');
        $arr = explode(',', trim($ipList));
        $ip = trim(($arr[0] ?? ''));
        if (empty($ip)) {
            $ip = trim(($arr[1] ?? ''));
            if (empty($ip)) {
                $ip = $req->getRealIp();
            }
        }
    }
    return $ip;
}

/**
 * 发送http chunk数据
 * https://www.workerman.net/doc/workerman/http/response.html
 * @param callable $callable function (callable $callable) {$callable('发送的数据');}
 * @param string   $top      开始时数据
 * @param string   $end      结束时数据
 * @return Response
 */
function send_chunk(callable $callable, string $top = "", string $end = ""): Response {
    $connection = request()->connection;
    $connection->send((new Response(200, ['Transfer-Encoding' => 'chunked'], $top)));
    $callable(function($data) use ($connection) {
        return $connection->send(new Chunk($data));
    });
    $connection->send(new Chunk($end));
    $connection->send(new Chunk(''));
    return response($end)->withHeaders(["Content-Type" => "application/octet-stream", "Transfer-Encoding" => "chunked"]);
}

/**
 * 跨域名头部
 * @param mixed $request
 * @return array
 */
function cors_header(mixed $request = null): array {
    $request = $request ?: request();
    return [
        'Access-Control-Allow-Credentials' => 'true',
        'Access-Control-Allow-Origin'      => $request->header('origin', '*'),
        'Access-Control-Allow-Methods'     => $request->header('access-control-request-method', '*'),
        'Access-Control-Allow-Headers'     => $request->header('access-control-request-headers', '*'),
    ];
}