<?php

namespace AloneFrame\base\expand;

use Redis;
use Predis\Client;
use AloneFrame\base\Frame;
use AloneFrame\base\helper\RedisHelper;

class AloneRedis {
    //配置
    public array $config = [];
    //选择数据库
    protected int $db = 0;
    //RedisClient对像
    protected RedisHelper $client;
    //key前缀
    protected string $keyPrefix = '';
    //是否使用新连接
    protected bool $update = false;
    //redis连接
    public Redis|Client $redis;
    //是否 \Redis
    public bool $isRedis = true;

    /**
     * 设置 左侧队列
     * @param string|int $key
     * @param mixed      $val
     * @return bool|int
     */
    public function queueSet(string|int $key, mixed $val): bool|int {
        $keys = $this->link()->getKey($key);
        return $this->redis->lpush($keys, $this->setValue($val));
    }

    /**
     * 获取 右侧队列
     * @param string|int $key
     * @return bool|string|int
     */
    public function queueGet(string|int $key): bool|string|int {
        $keys = $this->link()->getKey($key);
        return $this->redis->rpop($keys);
    }

    /**
     * 处理 左入右出 队列
     * @param string|int $key
     * @param callable   $callable 处理包
     * @param int        $int      获取数量
     * @param int        $j
     * @return int
     */
    public function queueGets(string|int $key, callable $callable, int $int = 1, int $j = 0): int {
        if (!empty($this->exists($key))) {
            for ($i = 1; $i <= $int; $i++) {
                if (!empty($queue = $this->queueGet($key))) {
                    ++$j;
                    $callable($this, $queue);
                }
            }
        }
        return $j;
    }

    /**
     * 自增 可增大数
     * @param string|int $key
     * @param int        $value
     * @return int
     */
    public function incrBy(string|int $key, int $value = 1): int {
        $keys = $this->link()->getKey($key);
        return $this->redis->incrBy($keys, $value);
    }

    /**
     * 自减 可增减数
     * @param string|int $key
     * @param int        $value
     * @return int
     */
    public function decrBy(string|int $key, int $value = 1): int {
        $keys = $this->link()->getKey($key);
        return $this->redis->decrBy($keys, $value);
    }

    /**
     * 设置 有序列表
     * @param string|int $key
     * @param mixed      $val
     * @param int        $time
     * @return mixed
     */
    public function zAdd(string|int $key, mixed $val, int $time = 0): mixed {
        $keys = $this->link()->getKey($key);
        return $this->redis->zadd($keys, ($time ?: time()), $this->setValue($val));
    }

    /**
     * 获取 有序列表
     * @param string|int $key
     * @param int        $time
     * @return array
     */
    public function zGet(string|int $key, int $time = 0): array {
        $keys = $this->link()->getKey($key);
        return $this->redis->zrangebyscore($keys, '-inf', ($time ?: time()), ['WITHSCORES' => true]);
    }

    /**
     * 处理 有序列表
     * @param string|int $key
     * @param callable   $callable
     * @param int        $time
     * @param int        $j
     * @return int
     */
    public function zGets(string|int $key, callable $callable, int $time = 0, int $j = 0): int {
        if (!empty($array = $this->zGet($key, $time))) {
            foreach ($array as $k => $v) {
                if ($this->zDel($key, $k) > 0) {
                    ++$j;
                    $callable($this, $k, $v);
                }
            }
        }
        return $j;
    }

    /**
     * 删除指定 有序列表
     * @param string|int $key
     * @param mixed      $val
     * @return mixed
     */
    public function zDel(string|int $key, mixed $val = null): mixed {
        $keys = $this->link()->getKey($key);
        return ($val ? $this->redis->zrem($keys, $val) : $this->redis->del($keys));
    }

    /**
     * 设置 缓存
     * @param int|string $key
     * @param int|string $name
     * @param mixed      $val
     * @param int        $time
     * @return mixed
     */
    public function hSet(int|string $key, int|string $name, mixed $val, int $time = 0): mixed {
        $keys = $this->link()->getKey($key);
        $exists = true;
        if ($time > 0) {
            $exists = $this->exists($key);
        }
        $res = $this->redis->hmset($keys, [$name => $this->setValue($val)]);
        if (empty($exists)) {
            $this->expire($key, $time);
        }
        return $res;
    }

    /**
     * 获取 缓存
     * @param int|string $key
     * @param int|string $name
     * @param mixed      $def
     * @return mixed
     */
    public function hGet(int|string $key, int|string $name, mixed $def = ''): mixed {
        $keys = $this->link()->getKey($key);
        return $this->getValue($this->redis->hget($keys, $name), $def);
    }

    /**
     * 删除 缓存
     * @param int|string $key
     * @param int|string $name
     * @return Redis|int|bool
     */
    public function hDel(int|string $key, int|string $name = ''): Redis|int|bool {
        $keys = $this->link()->getKey($key);
        return $name ? $this->redis->hdel($keys, $name) : $this->redis->del($keys);
    }

    /**
     * 设置
     * @param string|int $key
     * @param mixed      $val
     * @param int        $time
     * @return mixed
     */
    public function set(string|int $key, mixed $val, int $time = 0): mixed {
        $keys = $this->link()->getKey($key);
        $res = $this->redis->set($keys, $this->setValue($val));
        if ($time > 0) {
            $this->expire($key, $time);
        }
        return $res;
    }

    /**
     * 设置排他锁
     * @param string|int $key
     * @param int        $time
     * @param mixed      $val
     * @return mixed
     */
    public function setNx(string|int $key, int $time, mixed $val = 1): mixed {
        $keys = $this->link()->getKey($key);
        return (($this->isRedis) ? $this->redis->set($keys, $val, ['nx', 'ex' => $time]) : $this->redis->set($keys, $val, 'ex', $time, 'nx'));
    }

    /**
     * 获取
     * @param string|int $key
     * @param mixed      $def
     * @return mixed
     */
    public function get(string|int $key, mixed $def = ''): mixed {
        $keys = $this->link()->getKey($key);
        return $this->getValue($this->redis->get($keys), $def);
    }

    /**
     * 删除
     * @param string|int $key
     * @return Redis|int|bool
     */
    public function del(string|int $key): Redis|int|bool {
        $keys = $this->link()->getKey($key);
        return $this->redis->del($keys);
    }

    /**
     * 设置有效时间
     * @param string|int $key
     * @param int        $time
     * @return mixed
     */
    public function expire(string|int $key, int $time): mixed {
        $keys = $this->link()->getKey($key);
        return $this->redis->expire($keys, $time);
    }

    /**
     * key是否存在
     * @param string|int $key
     * @return mixed
     */
    public function exists(string|int $key): mixed {
        $keys = $this->link()->getKey($key);
        return $this->redis->exists($keys);
    }

    /**
     * 删除key下面全部key
     * @param string|int $key
     * @param bool       $prefix 是否加前缀
     * @param int        $count
     * @return int
     */
    public function delete(string|int $key, bool $prefix = true, int $count = 0): int {
        $this->link();
        $list = $this->redis->keys(($prefix ? $this->getKey($key) : $key) . ':*');
        if (!empty($list)) {
            foreach ($list as $item) {
                ++$count;
                $this->redis->del($item);
            }
        }
        return $count;
    }

    /**
     * @param array $config
     * @return static
     */
    public function link(array $config = []): static {
        $this->redis = (empty($this->link) || !empty($this->update)) ? call_user_func_array([$this->client, ($this->isRedis ? 'redisLink' : 'predisLink')], [$config]) : $this->link;
        $this->redis->select($this->db ?: 0);
        return $this;
    }

    /**
     * 选择链接
     * @param string|int $name
     * @return $this
     */
    public function connection(string|int $name = 'default'): static {
        $this->client->connection($name);
        return $this;
    }

    /**
     * 设置key前缀,多级使用 : 号
     * @param string $prefix
     * @return $this
     */
    public function prefix(string $prefix): static {
        $this->keyPrefix = $prefix;
        return $this;
    }

    /**
     * 选择数据库
     * @param int $db
     * @return $this
     */
    public function select(int $db = 0): static {
        $this->db = $db;
        return $this;
    }

    /**
     * 是否更新连接
     * @param bool $update
     * @return $this
     */
    public function update(bool $update): static {
        $this->update = $update;
        return $this;
    }

    /**
     * redis 排他锁 执行
     * @param string|int    $key      唯一标识
     * @param callable      $callable 执行包
     * @param callable|bool $closure  超时的时候处理,false=不处理,true=运行执行包,callable($callable)=自定执行包
     * @param int           $timeout  有效时间,执行的最长等待时间 秒
     * @param int           $wait     间隔等待时间 微秒
     * @return mixed
     */
    public function lock(string|int $key, callable $callable, callable|bool $closure = false, int $timeout = 5, int $wait = 100): mixed {
        $keys = $this->link()->getKey($key);
        $startTime = time();
        while (true) {
            if ($this->setNx($key, $timeout)) {
                break;
            } else {
                usleep($wait * 10000);
                if ((time() - $startTime) >= $timeout) {
                    return (!empty($closure) ? (is_callable($closure) ? $closure($callable) : $callable()) : $closure);
                }
                usleep($wait * 10000);
            }
        }
        $res = $callable();
        if ($this->exists($key)) {
            $this->redis->del($keys);
        }
        return $res;
    }

    /**
     * 设置 右侧队列
     * @param string|int $key
     * @param mixed      $val
     * @return bool|int
     */
    public function queueRightSet(string|int $key, mixed $val): bool|int {
        $keys = $this->link()->getKey($key);
        return $this->redis->rpush($keys, $this->setValue($val));
    }

    /**
     * 获取 左侧队列
     * @param string|int $key
     * @return bool|string|int
     */
    public function queueRightGet(string|int $key): bool|string|int {
        $keys = $this->link()->getKey($key);
        return $this->redis->lpop($keys);
    }

    /**
     * 处理 右入左出 队列
     * @param string|int $key
     * @param callable   $callable 处理包
     * @param int        $int      获取数量
     * @param int        $j
     * @return int
     */
    public function queueRightGets(string|int $key, callable $callable, int $int = 1, int $j = 0): int {
        if (!empty($this->exists($key))) {
            for ($i = 1; $i <= $int; $i++) {
                if (!empty($queue = $this->queueRightGet($key))) {
                    ++$j;
                    $callable($this, $queue);
                }
            }
        }
        return $j;
    }

    /**
     * 获取key
     * @param string|int $key
     * @return string|int
     */
    public function getKey(string|int $key): string|int {
        $prefix = !empty($prefix = ($this->config['prefix'] ?? '')) ? ($prefix . ":") : "";
        return ($prefix . (!empty($this->keyPrefix) ? ($this->keyPrefix . ":" . $key) : $key));
    }

    /**
     * 保存数据
     * @param mixed $val
     * @return mixed
     */
    protected function setValue(mixed $val): mixed {
        $val = is_callable($val) ? $val() : $val;
        return (is_array($val) ? Frame::json($val) : $val);
    }

    /**
     * 获取数据
     * @param mixed $val
     * @param mixed $def
     * @return mixed
     */
    protected function getValue(mixed $val, mixed $def = ''): mixed {
        return (Frame::isJson($val) ?: $val) ?? $def;
    }
}