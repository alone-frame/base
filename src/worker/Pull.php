<?php

namespace AloneFrame\base\worker;

use AloneFrame\base\CacheFile;
use AloneFrame\base\WorkerHelper;

class Pull {
    protected static array $cachePullTime = [];

    /**
     * 拉取脚本
     * 进程名称 间隔时间 几秒前 等待时间1 等待时间2 启动时间 每次启动是否按默认开始时间拉取 定时器 进程数量
     * @param string|int $name         进程名称
     * @param int|float  $range_time   开始时间到结束时间的间隔时间秒
     * @param int|float  $before_time  正常拉取结束时间在当前时间几秒前
     * @param int|float  $start_time   启动时的开始时间
     * @param bool       $start_status 每次启动是否按启动时间开始拉取
     * @param int        $wait_time    等待时间 结束时间<间隔时间
     * @param callable   $callable     达到条件时执行包(开始时间,结束时间,状态)
     * @param int        $pull_time    正常拉取等待时间 结束时间=间隔时间 因为正常拉取时等待时间就是间隔时间,中间可此时间来停止一下,默认间隔时间-10秒
     * @param int|float  $timer        定时器 单位秒，支持小数，可以精确到0.001，即精确到毫秒级别
     * @return void
     */
    public static function set(
        string|int $name,
        int|float  $range_time,
        int|float  $before_time,
        int|float  $start_time,
        bool       $start_status,
        int        $wait_time,
        callable   $callable,
        int        $pull_time = 0,
        int|float  $timer = 1
    ): void {
        global $argv;
        if (!empty($start_status) && (($argv[1] ?? '') == 'start')) {
            static::delPullTime($name);
        }
        $start_top_time = static::getPullTime($name, (($start_time > 0 ? $start_time : time() - 3600) + $range_time));
        static::setPullTime($name, $start_top_time);
        WorkerHelper::set($name, function() use ($callable, $name, $before_time, $range_time, $wait_time, $pull_time, $timer) {
            WorkerHelper::timer($timer, function() use ($callable, $name, $before_time, $range_time, $wait_time, $pull_time) {
                $end_time = static::getPullTime($name);
                if ($end_time > 0) {
                    $now_time = time();
                    $exec_time = ($end_time + $before_time);
                    if ($now_time >= $exec_time) {
                        $top_time = ($end_time - $range_time);
                        $status = ($now_time - $end_time - $before_time <= $range_time);
                        $callable($top_time, $end_time, $status);
                        static::setPullTime($name, $end_time + $range_time);
                        if ($status) {
                            sleep(($pull_time > 0) ? $pull_time : ($range_time - 10));
                        } else {
                            sleep($wait_time);
                        }
                    }
                }
            });
        });
    }

    /**
     * 保存时间
     * @param string|int $key
     * @param int|float  $time
     * @return float|int
     */
    protected static function setPullTime(string|int $key, int|float $time): float|int {
        static::$cachePullTime[$key] = $time;
        return CacheFile::set($key, $time);
    }

    /**
     * 获取时间
     * @param string|int $key
     * @param int|float  $def
     * @return float|int|null
     */
    protected static function getPullTime(string|int $key, int|float $def = 0): float|int|null {
        return ((static::$cachePullTime[$key] ?? null) ?: CacheFile::get($key, $def)) ?: $def;
    }

    protected static function delPullTime(string|int $key): void {
        static::$cachePullTime[$key] = null;
        CacheFile::del($key);
    }
}