<?php

namespace AloneFrame\base\helper;

use CURLFile;
use AloneFrame\base\Frame;

class CurlHelper {
    //全局代理ip
    public static array $proxy = [
        'default' => 'default',
        'config'  => [
            'default' => [
                //ip
                'ip'   => '',
                //端口
                'port' => '',
                //认证信息
                'user' => '',
                //http,socks5
                'type' => '',
                //basic,ntlm
                'auth' => ''
            ]
        ]
    ];

    //请求参数例
    public static array $config = [
        //请求url
        'url'         => '',
        //请求路径
        'path'        => '',
        //请求url参数
        'query'       => [],
        //请求模式(get,[post,put,patch,delete]支持请求体,head,connect,options)
        'mode'        => 'get',
        //设置头部信息
        'header'      => [],
        //是否ajax提交
        'ajax'        => false,
        //请求体格式,false=原样或者http_build_query, json, string=form-data的multi
        'format'      => false,
        //请求体
        'body'        => [],
        //上传文件(body要设置array)
        'file'        => '',
        //设置cookie
        'cookie'      => [],
        //设置来路,true=使用默认当前域名
        'origin'      => true,
        //设置浏览器信息,true=使用默认浏览器
        'browser'     => true,
        //设置基本认证信息
        'auth'        => '',
        //设置解码名称
        'encoding'    => '',
        //连接时间,默认10
        'connect'     => 10,
        //超时时间,默认10
        'timeout'     => 10,
        //设置代理ip [ip,port,user,type,auth],true=默认代理,string=key,false=关闭,array=设置代理
        'proxy'       => [],
        //设置伪装ip
        'req_ip'      => '',
        //伪装ip的key列表
        'req_ip_name' => ['CLIENT-IP', 'X-FORWARDED-FOR', 'CDN_SRC_IP', 'CF_CONNECTING_IP'],
        //是否检查证书,默认不检查
        'ssl_peer'    => false,
        //是否检查证书公用名,默认不检查
        'ssl_host'    => false,
        //是否自动跳转,默认不跳转
        'follow'      => false,
        //自定义Curl设置
        'curl'        => []
    ];

    //响应信息
    protected array $body = [];
    //响应头部
    protected array $header = [];

    //默认浏览器信息
    public static string $browser = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/99.0.4844.74 Safari/537.36 Edg/99.0.1150.46';

    /**
     * @param array $config
     * @return static
     */
    public static function send(array $config): static {

        $config = isset($config['url']) ? [$config] : $config;

        $init = curl_multi_init();

        $request = [];

        foreach ($config as $k => $v) {
            $curl = [];

            $conf = function($key, $default = '') use ($v) {
                return ($v[$key] ?? $default) ?: $default;
            };

            //记录请求开始时间
            $time = microtime(true);

            //请求头
            $headers = ($conf('header', []) ?? []) ?: [];

            //请求路径
            $path = trim($conf('path'), '/');

            //拼接路径
            $url = trim($conf('url'), '/') . ($path ? "/$path" : "");
            $urlShow = Frame::urlShow($url, $conf('query', []));
            //设置请求URL参数
            if ($conf('query')) {
                $url = $urlShow['url'] ?? $url;
            }
            $req['url'] = $url;

            $conn = curl_init($url);

            //请求模式
            $mode = strtoupper(($conf('mode') ?? 'GET'));
            if ($mode == 'POST') {
                $curl[CURLOPT_POST] = true;
            }
            $curl[CURLOPT_CUSTOMREQUEST] = $mode;
            $req['mode'] = $mode;

            //请求体
            $body = ($conf('body', []) ?? []) ?: [];
            if (in_array($mode, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
                if (is_array($body)) {
                    //上传文件
                    $file = ($conf('file') ?? []);
                    if (!empty($file)) {
                        foreach ($file as $key => $val) {
                            if (!empty($filePath = realpath($val))) {
                                $body[$key] = new CURLFile($filePath);
                            }
                        }
                    }
                }
                if (!empty($body)) {
                    if ($conf('format') == 'json') {
                        $body = is_array($body) ? json_encode($body) : $body;
                        $headers['Content-Type'] = 'application/json';
                    } elseif (is_string($conf('format', null))) {
                        $body = is_array($body) ? static::formData($conf('format'), $body) : $body;
                        $headers['Content-Type'] = 'multipart/form-data;boundary=' . $conf('format');
                    } else {
                        $body = is_array($body) ? http_build_query($body) : $body;
                    }
                    $curl[CURLOPT_POSTFIELDS] = $body;
                    $headers['Content-Length'] = strlen($body);
                    $req['body'] = $body;
                }
            }

            //是否ajax
            if ($conf('ajax')) {
                $headers['X-Requested-With'] = 'XMLHttpRequest';
            }

            //设置解码名称
            if ($conf('encoding')) {
                $curl[CURLOPT_ENCODING] = $conf('encoding');
            }

            //设置基本认证信息
            if ($conf('auth')) {
                $curl[CURLOPT_USERPWD] = $conf('auth');
            }

            //设置cookie
            if (!empty($cookie = $conf('cookie'))) {
                $cookies = '';
                if (is_array($cookie)) {
                    foreach ($cookie as $key => $val) {
                        if (!empty($v)) {
                            $cookies .= $key . '=' . $val . ';';
                        }
                    }
                }
                $curl[CURLOPT_COOKIE] = $cookies ?: $cookie;
            }

            //自动设置浏览器信息
            if ($conf('browser')) {
                $curl[CURLOPT_USERAGENT] = $conf('browser') === true ? static::$browser : $conf('browser');
            }

            //自动跳转时设置开启头部
            $curl[CURLOPT_FOLLOWLOCATION] = ($conf('follow') ?? false) ?: false;
            if (!empty($follow)) {
                $curl[CURLOPT_AUTOREFERER] = true;
            }

            //设置代理ip
            if ($conf('proxy')) {
                $proxy = $conf('proxy', []);

                if (!is_array($proxy)) {
                    $default = (static::$proxy['default'] ?? 'default');
                    $key = (static::$proxy['key'] ?? $default) ?: 'default';
                    $key = $key === true ? $default : $key;
                    $proxy = (static::$proxy['config'][$key] ?? []) ?: [];
                }

                if (!empty($ip = ($proxy['ip'] ?? ''))) {
                    $curl[CURLOPT_PROXY] = $ip;
                    if (!empty($port = ($proxy['port'] ?? ''))) {
                        $curl[CURLOPT_PROXYPORT] = $port;
                    }
                    if (!empty($user = ($proxy['user'] ?? ''))) {
                        $curl[CURLOPT_PROXYUSERPWD] = $user;
                    }
                    if (!empty($type = ($proxy['type'] ?? ''))) {
                        $curl[CURLOPT_PROXYTYPE] = ($type == 'http' ? CURLPROXY_HTTP : ($type == 'socks5' ? CURLPROXY_SOCKS5 : $type));
                    }
                    if (!empty($auth = ($proxy['auth'] ?? ''))) {
                        $curl[CURLOPT_PROXYAUTH] = ($auth == 'basic' ? CURLAUTH_BASIC : ($auth == 'ntlm' ? CURLAUTH_NTLM : $auth));
                    }
                }
            }

            //true 将curl_exec()获取的信息以字符串返回，而不是直接输出。
            $curl[CURLOPT_RETURNTRANSFER] = true;

            //true 时将不输出 BODY 部分。同时 Mehtod 变成了 HEAD。修改为 false 时不会变成 GET
            $curl[CURLOPT_NOBODY] = false;

            //是否返回头部信息
            $curl[CURLOPT_HEADER] = true;

            //连接时间,设置为0，则无限等待
            $curl[CURLOPT_CONNECTTIMEOUT] = ($conf('connect', 10) ?? 10) ?: 10;

            //超时时间,设置为0，则无限等待
            $timeout = ($conf('timeout', 10) ?? 10) ?: 10;
            $curl[CURLOPT_TIMEOUT] = $timeout;

            //否检查证书,默认不检查
            $curl[CURLOPT_SSL_VERIFYPEER] = ($conf('ssl_peer') ?? false);

            //设置成 2，会检查公用名是否存在，并且是否与提供的主机名匹配。 0 为不检查名称。 在生产环境中，这个值应该是 2（默认值）
            $curl[CURLOPT_SSL_VERIFYHOST] = ($conf('ssl_host') ?? false);

            //设置来源
            $origin = ($urlShow['scheme'] . '://' . $urlShow['host'] . (!empty($urlShow['port']) ? ':' . $urlShow['port'] : ''));
            if ($conf('origin')) {
                $origin = $conf('origin') === true ? $origin : $conf('origin');
                $curl[CURLOPT_REFERER] = $origin;
                $headers['REFERER'] = $origin;
                $headers['ORIGIN'] = $origin;
            }

            //伪装ip
            if ($conf('req_ip')) {
                foreach ($conf('req_ip_name') as $val) {
                    $headers[$val] = $conf('req_ip');
                }
            }

            //设置请求头
            $header = [];
            if (!empty($headers)) {
                foreach ($headers as $key => $val) {
                    $header[] = is_numeric($key) ? $val : "$key: $val";
                }
                $curl[CURLOPT_HTTPHEADER] = $header;
                $req['header'] = $header;
            }

            //强制使用 HTTP/1.1
            /**
             * CURL_HTTP_VERSION_NONE
             * CURL_HTTP_VERSION_1_0
             * CURL_HTTP_VERSION_1_1
             * CURL_HTTP_VERSION_2_0
             */
            //$curl[CURLOPT_HTTP_VERSION] = CURL_HTTP_VERSION_NONE;

            //自定设置
            if (!empty($curls = $conf('curl'))) {
                foreach ($curls as $key => $val) {
                    $curl[$key] = $val;
                }
            }

            curl_setopt_array($conn, $curl);

            curl_multi_add_handle($init, $conn);

            $request[$k] = [
                'time' => $time,
                'req'  => $req,
                'curl' => $curl,
                'url'  => $urlShow,
                'conn' => $conn,
            ];

        }

        $static = new static();
        $static->body = [];
        $static->header = [];
        $forGet = function() use ($request, $static, $init) {
            foreach ($request as $k => $v) {
                $info = curl_getinfo($v['conn']);
                $code = ($info['http_code'] ?? 0);
                if (curl_errno($v['conn']) == 0 && $code >= 200 && $code < 300) {
                    $header_size = ($info['header_size'] ?? 0);
                    $response = curl_multi_getcontent($v['conn']);
                    $header = trim(trim(substr($response, 0, $header_size), "\r\n"), "\r\n");
                    $body = substr($response, $header_size);
                    $static->body[$k] = [
                        //curl
                        'curl'   => $v['curl'],
                        //请求url
                        'url'    => $v['url'],
                        //请求信息
                        'req'    => $v['req'],
                        //响应信息
                        'info'   => $info,
                        //执行时间
                        'time'   => (microtime(true) - ($v['time'] ?? 0)),
                        //响应信息
                        'res'    => $response,
                        //状态码
                        'code'   => $code,
                        //返回头部信息
                        'header' => $header,
                        //内容
                        'body'   => $body,
                        //时间
                        'date'   => date("Y-m-d H:i:s")
                    ];
                    curl_multi_remove_handle($init, $v['conn']);
                    curl_close($v['conn']);
                    unset($request[$k]);
                } else {
                    $static->body[$k] = [
                        //curl
                        'curl'  => $v['curl'],
                        //请求url
                        'url'   => $v['url'],
                        //请求信息
                        'req'   => $v['req'],
                        //响应信息
                        'info'  => $info,
                        //执行时间
                        'time'  => (microtime(true) - ($v['time'] ?? 0)),
                        //状态码
                        'code'  => $code,
                        //出错
                        'error' => curl_error($v['conn']),
                        //时间
                        'date'  => date("Y-m-d H:i:s")
                    ];
                    unset($request[$k]);
                }
            }
        };

        do {
            $exec = curl_multi_exec($init, $active);
            if ($active) {
                curl_multi_select($init, 10);
            }
        } while ($active && $exec == CURLM_OK);

        $forGet();

        curl_multi_close($init);

        return $static;
    }

    /**
     * 调试信息
     * @param array|string $field  字段列表
     * @param bool         $delete 是否删除
     * @return array
     */
    public function debug(array|string $field = [], bool $delete = false): array {
        if (!empty($field)) {
            $array = [];
            $field = is_array($field) ? $field : [$field];
            foreach ($this->body as $key => $val) {
                foreach ($val as $k => $v) {
                    if ($delete) {
                        if (!in_array($k, $field)) {
                            $array[$key][$k] = $v;
                        }
                    } elseif (in_array($k, $field)) {
                        $array[$key][$k] = $v;
                    }
                }
            }
            return $array;
        }
        return $this->body;
    }

    /**
     * 获取单个请求或者指定请求 响应信息
     * @param array|null $key 取那些key
     * @return array
     */
    public function getAllBody(array|null $key = ['req', 'code', 'header', 'body', 'error']): array {
        return Frame::arrayColumn($this->body, $key);
    }

    /**
     * 获取 单个请求 或者 指定请求 状态码
     * @param string|int|null $key 多请求时默认获取第1个
     * @return int
     */
    public function getCode(string|int|null $key = null): int {
        $keys = $key ?? key($this->body);
        return (int) ($this->body[$keys]['code'] ?? 0);
    }

    /**
     * 获取单个请求或者指定请求 响应信息
     * @param string|int|null $key 多请求时默认获取第1个
     * @return string|int
     */
    public function getBody(string|int|null $key = null): string|int {
        $keys = $key ?? key($this->body);
        return $this->body[$keys]['body'] ?? '';
    }

    /**
     * 获取单个请求或者指定请求 响应信息Array
     * @param string|int|null $key 多请求时默认获取第1个
     * @return array
     */
    public function getArray(string|int|null $key = null): array {
        return Frame::isJson($this->getBody($key)) ?: [];
    }

    /**
     * 获取单个请求或者指定请求 响应头部信息
     * @param string|int|null $key 多请求时默认获取第1个
     * @return string
     */
    public function getHeader(string|int|null $key = null): string {
        $keys = $key ?? key($this->body);
        return $this->body[$keys]['header'] ?? '';
    }

    /**
     * 获取单个请求或者指定请求 响应头部信息array
     * @param string|int|null $key 多请求时默认获取第1个
     * @return array
     */
    public function getHeaders(string|int|null $key = null): array {
        $keys = $key ?? key($this->body);
        if (!isset($this->header[$keys])) {
            $data = $this->body[$keys]['header'] ?? '';
            $header = explode("\r\n", trim($data));
            foreach ($header as $head) {
                if (str_contains($head, ':')) {
                    [$k, $v] = explode(': ', $head, 2);
                    $ks = str_replace('-', '_', strtolower(trim($k)));
                    $this->header[$keys][$ks] = Frame::isJson($v) ?: $v;
                }
            }
        }
        return $this->header[$keys] ?? [];
    }

    /**
     * 获取curl请求参数
     * @param string|int|null $key
     * @return array
     */
    public function getCurl(string|int|null $key = null): array {
        $keys = $key ?? key($this->body);
        return ($this->body[$keys]['curl'] ?? []);
    }

    /**
     * 获取请求信息
     * @param string|int|null $key
     * @return array
     */
    public function getReq(string|int|null $key = null): array {
        $keys = $key ?? key($this->body);
        return ($this->body[$keys]['req'] ?? []);
    }

    /**
     * 获取请求Url
     * @param string|int|null $key
     * @return array
     */
    public function getUrl(string|int|null $key = null): array {
        $keys = $key ?? key($this->body);
        return ($this->body[$keys]['url'] ?? []);
    }

    /**
     * 获取响应info
     * @param string|int|null $key
     * @return array
     */
    public function getInfo(string|int|null $key = null): array {
        $keys = $key ?? key($this->body);
        return ($this->body[$keys]['info'] ?? []);
    }

    /**
     * 获取响应信息
     * @param string|int|null $key
     * @return string
     */
    public function getRes(string|int|null $key = null): string {
        $keys = $key ?? key($this->body);
        return ($this->body[$keys]['res'] ?? '');
    }

    /**
     * 获取执行时间
     * @param string|int|null $key
     * @return float
     */
    public function getTime(string|int|null $key = null): float {
        $keys = $key ?? key($this->body);
        return (float) ($this->body[$keys]['time'] ?? 0);
    }

    /**
     * 获取出错信息
     * @param string|int|null $key
     * @return float
     */
    public function getError(string|int|null $key = null): float {
        $keys = $key ?? key($this->body);
        return (float) ($this->body[$keys]['error'] ?? 0);
    }

    /**
     * 输出指定信息列表
     * @param string|int|null $key
     * @param array|string    $field
     * @param bool            $delete
     * @return array
     */
    public function getList(string|int|null $key = null, array|string $field = [], bool $delete = false): array {
        $body = $this->debug($field ?: ['code', 'header', 'body', 'time'], $delete);
        return $body[($key ?? key($body))] ?? [];
    }

    /**
     * form-data请求设置
     * @param string $multi
     * @param array  $data
     * @param string $body
     * @param string $path
     * @return string
     */
    protected static function formData(string $multi, array $data, string $body = '', string $path = ''): string {
        $i = !empty($i) ? $i : 0;
        $ov = !empty($ov) ? $ov : count($data);
        foreach ($data as $k => $v) {
            if (empty($path)) {
                $name = $k;
                $i = $i + 1;
            } else {
                $name = $path . "[" . $k . "]";
            }
            if (is_array($v)) {
                $body = static::formData($multi, $v, $body, $name);
            } else {
                $body .= "--$multi\r\n";
                if (is_file($v)) {
                    $body .= "Content-Disposition:form-data;name=\"$name\";";
                    $body .= "filename=\"" . basename($v) . "\"\r\n";
                    $body .= "Content-Type: " . Frame::getMime(Frame::getFileFormat($v)) . "\r\n\r\n";
                    $body .= "" . (@file_get_contents($v)) . "\r\n";
                } else {
                    $body .= "Content-Disposition:form-data;";
                    $body .= "name=\"$name\"\r\n\r\n$v\r\n";
                }
            }
        }
        $body .= (($ov == $i) ? "--$multi--\r\n" : "");
        return $body;
    }
}