<?php

namespace AloneFrame\base;

use Closure;
use AloneFrame\base\helper\CurlHelper;

class CurlClient{
    //请求请求
    protected array $setCurl = [];
    //代理列表,true=默认代理,string=key,false=关闭
    protected array $setProxy = [];

    /**
     * 请求url
     * @param string|array      $data
     * @param string            $mode
     * @param string|array|null $query
     * @return static
     */
    public static function url(string|array $data, string $mode = 'get', string|array|null $query = null): static {
        $static = new static();
        $static->setCurl['url'] = $data;
        $static->setCurl['mode'] = $mode;
        $static->setCurl['query'] = $query;
        return $static;
    }

    /**
     * get请求
     * @param string|array      $url
     * @param string|array|null $query
     * @return static
     */
    public static function get(string|array $url, string|array|null $query = null): static {
        return self::url($url, 'get', $query);
    }

    /**
     * post请求
     * @param string|array     $url
     * @param mixed            $body
     * @param string|bool|null $format
     * @return static
     */
    public static function post(string|array $url, mixed $body, string|bool|null $format = null): static {
        return self::url($url, 'post')->body($body, $format);
    }

    /**
     * put请求
     * @param string|array     $url
     * @param mixed            $body
     * @param string|bool|null $format
     * @return static
     */
    public static function put(string|array $url, mixed $body, string|bool|null $format = null): static {
        return self::url($url, 'put')->body($body, $format);
    }

    /**
     * patch请求
     * @param string|array     $url
     * @param mixed            $body
     * @param string|bool|null $format
     * @return static
     */
    public static function patch(string|array $url, mixed $body, string|bool|null $format = null): static {
        return self::url($url, 'patch')->body($body, $format);
    }

    /**
     * delete请求
     * @param string|array     $url
     * @param mixed            $body
     * @param string|bool|null $format
     * @return static
     */
    public static function delete(string|array $url, mixed $body, string|bool|null $format = null): static {
        return self::url($url, 'delete')->body($body, $format);
    }

    /**
     * head请求
     * @param string|array      $url
     * @param string|array|null $query
     * @return static
     */
    public static function head(string|array $url, string|array|null $query = null): static {
        return self::url($url, 'head', $query);
    }

    /**
     * connect请求
     * @param string|array      $url
     * @param string|array|null $query
     * @return static
     */
    public static function connect(string|array $url, string|array|null $query = null): static {
        return self::url($url, 'connect', $query);
    }

    /**
     * options请求
     * @param string|array      $url
     * @param string|array|null $query
     * @return static
     */
    public static function options(string|array $url, string|array|null $query = null): static {
        return self::url($url, 'options', $query);
    }

    /**
     * 请求路径
     * @param string            $data
     * @param string|array|null $query
     * @return static
     */
    public function path(string $data = '', string|array|null $query = null): static {
        $this->setCurl['path'] = $data;
        $this->setCurl['query'] = $query ?? ($this->setCurl['query'] ?? '');
        return $this;
    }

    /**
     * 请求url参数
     * @param string|array $data
     * @return static
     */
    public function query(string|array $data = ''): static {
        $this->setCurl['query'] = $data;
        return $this;
    }

    /**
     * 请求模式(get,[post,put,patch,delete]支持请求体,head,connect,options)
     * @param string $data
     * @return static
     */
    public function mode(string $data = 'get'): static {
        $this->setCurl['mode'] = $data;
        return $this;
    }

    /**
     * 设置头部信息
     * @param array $data
     * @return static
     */
    public function header(array $data = []): static {
        $this->setCurl['header'] = $data;
        return $this;
    }

    /**
     * 是否ajax提交
     * @param bool $data
     * @return static
     */
    public function ajax(bool $data = true): static {
        $this->setCurl['ajax'] = $data;
        return $this;
    }

    /**
     * 是否json
     * @param bool $data
     * @return $this
     */
    public function json(bool $data = true): static {
        $this->setCurl['format'] = $data ? 'json' : false;
        return $this;
    }

    /**
     * 请求体格式(false=原样或者http_build_query, json, string=form-data的multi)
     * @param string|bool $data
     * @return $this
     */
    public function format(string|bool $data = false): static {
        $this->setCurl['format'] = $data;
        return $this;
    }

    /**
     * 请求体
     * @param string           $data
     * @param string|bool|null $format
     * @return static
     */
    public function body(mixed $data = [], string|bool|null $format = null): static {
        $this->setCurl['body'] = $data;
        $this->setCurl['format'] = $format ?? ($this->setCurl['format'] ?? false);
        return $this;
    }

    /**
     * 上传文件(body要设置array)
     * @param array $data
     * @return static
     */
    public function file(array $data = []): static {
        $this->setCurl['file'] = $data;
        return $this;
    }

    /**
     * 设置cookie
     * @param string|array $data
     * @return static
     */
    public function cookie(string|array $data = []): static {
        $this->setCurl['cookie'] = $data;
        return $this;
    }

    /**
     * 设置来路,true=使用默认当前域名
     * @param string|bool $data
     * @return static
     */
    public function origin(string|bool $data = true): static {
        $this->setCurl['origin'] = $data;
        return $this;
    }

    /**
     * 设置浏览器信息,true=使用默认浏览器
     * @param string|bool $data
     * @return static
     */
    public function browser(string|bool $data = true): static {
        $this->setCurl['browser'] = $data;
        return $this;
    }

    /**
     * 设置基本认证信息
     * @param string $data
     * @return static
     */
    public function auth(string $data = ''): static {
        $this->setCurl['auth'] = $data;
        return $this;
    }

    /**
     * 设置解码名称
     * @param string $data
     * @return static
     */
    public function encoding(string $data = ''): static {
        $this->setCurl['encoding'] = $data;
        return $this;
    }

    /**
     * @param string|int $connect 连接时间
     * @param string|int $timeout 超时时间
     * @return static
     */
    public function time(string|int $connect = 10, string|int $timeout = 10): static {
        $this->setCurl['connect'] = (int) $connect;
        $this->setCurl['timeout'] = (int) $timeout;
        return $this;
    }

    /**
     * 设置代理ip
     * @param array                $data
     * @param string|int|bool|null $key true=默认代理,string=key,false=关闭
     * @return static
     */
    public function proxy(array $data = [], string|int|bool|null $key = null): static {
        $this->setProxy['config'] = $data;
        $this->setProxy['key'] = $key ?? ($this->setProxy['key'] ?? false);
        return $this;
    }

    /**
     * true=默认代理,string=key,false=关闭
     * @param string|int|bool $data
     * @return $this
     */
    public function proxyKey(string|int|bool $data = false): static {
        $this->setProxy['key'] = $data;
        return $this;
    }

    /**
     * 设置伪装ip
     * @param string $data
     * @param array  $key
     * @return $this
     */
    public function reqIp(string $data = '', array $key = ['CLIENT-IP', 'X-FORWARDED-FOR', 'CDN_SRC_IP', 'CF_CONNECTING_IP']): static {
        $this->setCurl['req_ip'] = $data;
        $this->setCurl['req_ip_name'] = $key;
        return $this;
    }

    /**
     * 是否检查证书,默认不检查
     * @param bool $data
     * @return static
     */
    public function sslPeer(bool $data = false): static {
        $this->setCurl['ssl_peer'] = $data;
        return $this;
    }

    /**
     * 是否检查证书公用名,默认不检查
     * @param bool $data
     * @return static
     */
    public function sslHost(bool $data = false): static {
        $this->setCurl['ssl_host'] = $data;
        return $this;
    }

    /**
     * 是否自动跳转,默认不跳转
     * @param bool $data
     * @return static
     */
    public function follow(bool $data = false): static {
        $this->setCurl['follow'] = $data;
        return $this;
    }

    /**
     * 自定义Curl设置
     * @param array $data
     * @return static
     */
    public function curl(array $data = []): static {
        $this->setCurl['curl'] = $data;
        return $this;
    }

    /**
     * 发送请求
     * @param int          $retry    重试次数
     * @param Closure|null $next     function (CurlReq $curl),返回false重试
     * @param int          $interval 等待时间
     * @return CurlHelper
     */
    public function exec(int $retry = 0, Closure|null $next = null, int $interval = 200): CurlHelper {
        $this->handle();
        $next = !empty($next)
            ? $next
            : function(CurlHelper $curl) {
                return (!empty($curl->getBody()) || $curl->getCode() == 200);
            };
        foreach ($this->setCurl['url'] as $item) {
            for ($i = 0; $i <= $retry; $i++) {
                $curl = CurlHelper::send(array_merge($this->setCurl, ['url' => $item]));
                if ($next($curl) !== false) {
                    return $curl;
                }
                usleep($interval * 1000);
            }
        }
        return ($curl ?? new CurlHelper());
    }

    /**
     * 并发请求执行
     * @param int $hits 并发次数
     * @return CurlHelper
     */
    public function concurrent(int $hits = 0): CurlHelper {
        $this->handle();
        $url = $this->setCurl['url'][key($this->setCurl['url'])];
        for ($i = 0; $i <= $hits; $i++) {
            $array[] = array_merge($this->setCurl, ['url' => $url]);
        }
        return CurlHelper::send($array ?? []);
    }

    /**
     * 获取处理后配置,配合send方法执行
     * @return array
     */
    public function getConfig(): array {
        return $this->handle()->setCurl;
    }

    /**
     * 可通过getConfig传入参数
     * @param array $config
     * @return CurlHelper
     */
    public static function send(array $config = []): CurlHelper {
        return CurlHelper::send($config);
    }

    /**
     * 处理
     * @param array $config
     * @return $this
     */
    protected function handle(array $config = []): static {
        $proxy = $this->setProxy['config'] ?? [];
        if (isset($proxy['ip'])) {
            $this->setCurl['proxy'] = $proxy;
        } else {
            $default = ($this->setProxy['default'] ?? 'default');
            $key = ($this->setProxy['key'] ?? $default) ?: 'default';
            $key = $key === true ? $default : $key;
            $this->setCurl['proxy'] = ($this->setProxy['config'][$key] ?? []) ?: [];
        }
        $this->setCurl = array_merge($this->setCurl, $config);
        $url = $this->setCurl['url'] ?? [];
        $this->setCurl['url'] = is_array($url) ? $url : [$url];
        return $this;
    }
}