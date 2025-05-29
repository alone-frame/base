<?php

namespace AloneFrame\base\helper;

use Redis;
use Predis\Client;
use AloneFrame\base\Frame;

class RedisHelper{
    public string|int $name   = 'default';
    public array      $conf   = [];
    public array      $config = [
        'default' => 'default',
        'config'  => [
            'default' => [
                //tcp tls ssl
                'scheme'         => 'tcp',
                //服务器的主机名或 IP 地址
                'host'           => '127.0.0.1',
                //服务器的端口号，默认是 6379
                'port'           => 6379,
                //服务器redis密码
                'password'       => null,
                //选择数据库
                'database'       => 0,
                //连接超时时间，以秒为单位。默认值为 0.0，表示无限制。
                'timeout'        => 3,
                //用于持久连接的标识符。如果提供此参数，连接将被视为持久连接
                'persistent'     => false,
                //如果连接失败，重试的间隔时间（以毫秒为单位）。默认值为 0，表示不重试
                'retry_interval' => 0,
                //读取超时时间，以秒为单位。默认值为 0，表示无限制
                'read_timeout'   => 0,
                //选项
                'options'        => []
            ]
        ]
    ];

    /**
     * @param array $config
     * @return Redis
     */
    public static function redis(array $config = []): Redis {
        return (new static())->redisLink($config);
    }

    /**
     * @param array $config
     * @return Client
     */
    public static function predis(array $config = []): Client {
        return (new static())->predisLink($config);
    }

    /**
     * @param array $config
     */
    public function __construct(array $config = []) {
        $this->config = Frame::arrMerge($this->config, $config);
    }

    /**
     * 选择连接
     * @param string|int $name
     * @return $this
     */
    public function connection(string|int $name = 'default'): static {
        $this->name = $name;
        return $this;
    }

    /**
     * 原生redis链接
     * @param array $config
     * @return Redis
     */
    public function redisLink(array $config = []): Redis {
        $this->handle($config);
        $redis = new Redis();
        call_user_func_array([$redis, $this->getConf('redis', 'connect')], [
            (!empty($scheme = $this->getConf('scheme', '')) ? ($scheme . "://") : "") . $this->getConf('host', '127.0.0.1'),
            $this->getConf('port', 6379),
            $this->getConf('timeout', 5),
            $this->getConf('persistent') ?: null,
            $this->getConf('retry_interval', 0),
            $this->getConf('read_timeout', 0),
            $this->getConf('options', [])
        ]);
        if (!empty($password = $this->getConf('password'))) {
            $redis->auth($password);
        }
        $redis->select($this->getConf('database', 0));
        return $redis;
    }

    /**
     * 使用predis链接
     * https://github.com/predis/predis
     * @param array $config
     * @return Client
     */
    public function predisLink(array $config = []): Client {
        $this->handle($config);
        return new Client(Frame::arrMerge([
            'scheme'       => $this->getConf('scheme', 'tcp'),
            'host'         => $this->getConf('host', '127.0.0.1'),
            'port'         => $this->getConf('port', 6379),
            'timeout'      => $this->getConf('timeout', 5),
            'read_timeout' => $this->getConf('read_timeout', 0),
            'options'      => $this->getConf('options', []),
            'parameters'   => [
                'password'       => $this->getConf('password'),
                'database'       => $this->getConf('database', 0),
                'retry_interval' => $this->getConf('retry_interval', 0),
                'persistent'     => $this->getConf('persistent', false)
            ]
        ], $this->getConf('predis', [])));
    }

    /**
     * 处理配置
     * @param array $config
     * @return void
     */
    private function handle(array $config = []): void {
        $this->conf = Frame::arrMerge(($this->config['config'][(($this->config['default'] ?? 'default') ?: 'default')] ?? ($this->config['config']['default'] ?? [])) ?: [], $config);
    }

    /**
     * 获取配置
     * @param string|int|null $key
     * @param mixed           $default
     * @return mixed
     */
    private function getConf(string|int|null $key, mixed $default = null): mixed {
        return Frame::getArr($this->conf, $key, $default);
    }
}