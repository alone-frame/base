<?php

namespace AloneFrame\base;

use Closure;
use Workerman\Timer;
use Workerman\Worker;
use Workerman\Crontab\Crontab;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use AloneFrame\base\helper\CliHelper;
use Workerman\Connection\TcpConnection;
use Workerman\Connection\AsyncTcpConnection;

class WorkerHelper {
    //monitor 文件时间列表
    protected static array $monitorFileTime = [];
    //monitor 监控路径
    protected static array|string $monitorPathList = [];
    protected static int          $setCount        = 0;
    //命令
    public static array $statusList = ['start', 'restart', 'stop', 'status', 'reload', 'connections'];
    //支持方法
    public static array $onList = [
        'onWebSocketConnect',
        'onWorkerStart',
        'onWorkerReload',
        'onConnect',
        'onMessage',
        'onClose',
        'onBufferFull',
        'onBufferDrain',
        'onError'
    ];

    /**
     * 启动全部 php file start
     * 启动全部 php file start -d
     * 启动单个 php file start key
     * 启动单个 php file start key -d
     * @param array $config
     * @return void
     */
    public static function task(array $config): void {
        global $argv;
        if (!empty($a = ($argv[2] ?? ''))) {
            unset($argv[2]);
        }
        if (!empty($b = ($argv[3] ?? ''))) {
            unset($argv[3]);
        }
        $one = $argv[1] ?? '';
        $type = strtolower($one);
        $list = $config['config'] ?? [];
        if ($type == '@') {
            echo str_repeat("=", Frame::getArr($config, 'cli_len', 131)) . PHP_EOL;
            print_r($list);
            echo "启动全部: php " . $argv[0] . " start" . PHP_EOL;
            echo "启动全部: php " . $argv[0] . " start -d" . PHP_EOL;
            echo "启动单个: php " . $argv[0] . " start key" . PHP_EOL;
            echo "启动单个: php " . $argv[0] . " start key -d" . PHP_EOL;
            echo str_repeat("=", Frame::getArr($config, 'cli_len', 131)) . PHP_EOL;
            exit;
        }
        $method = Frame::getArr($config, 'method', 'aloneConfig');
        $way = function($key = null) use ($list, $method) {
            $int = 0;
            foreach ($list as $k => $v) {
                if (empty($key) || $k == $key) {
                    if (!empty($v['class_name'] ?? '')) {
                        $status = static::name($v['class_name'], $method);
                        $int = $status === true ? $int + 1 : $int;
                    }
                    if (!empty($v['class_path'] ?? '')) {
                        $status = static::path($v['class_path'], null, $method);
                        $int = $status === true ? $int + 1 : $int;
                    }
                }
            }
            return $int;
        };
        $name = 'task';
        $int = 0;
        if (!$a && !$b) {
            //启动全部 php file start
            $int = $way();
        } elseif (strtolower($a) == '-d') {
            //启动全部 php file start -d
            if ($type == 'start') {
                $argv[2] = '-d';
                $argv[3] = '';
            }
            $int = $way();
        } else {
            //启动单个 php file start key
            //启动单个 php file start key -d
            $int = $way($a);
            $name = Frame::strRep($a, ',', '_');
            if (strtolower($b) == '-d') {
                if ($type == 'start') {
                    $argv[2] = '-d';
                    $argv[3] = '';
                }
            }
        }
        if ($int === 0) {
            $argv[1] = '';
            $argv[2] = '';
            $argv[3] = '';
        }
        $show = true;
        if ($int > 0 && in_array($type, self::$statusList)) {
            $s = static::monitor(Frame::getArr($config, 'monitor_path', ''), Frame::getArr($config, 'monitor_daemonize', false));
            $int = $s === true ? $int + 1 : $int;
            $restart = Frame::getArr($config, 'restart');
            if (!empty($restart)) {
                static::set('alone.restart', function() use ($restart) {
                    static::aloneRestart($restart);
                });
            }
        } else {
            $show = false;
            $argv[1] = $one;
            $argv[2] = $a;
            $argv[3] = $b;
        }
        static::start("frame_" . $name, Frame::getArr($config, 'cli_class', ''), Frame::getArr($config, 'cli_row', 23), Frame::getArr($config, 'cli_len', 131), $int > 0);
        if (!$one || $show) {
            echo str_repeat("=", Frame::getArr($config, 'cli_len', 131)) . PHP_EOL;
            echo("查看列表: php " . $argv[0] . " @\r\n");
            echo "配置文件: " . alone_root_path('config/plugin/alone/frame/task.php') . PHP_EOL;
            echo str_repeat("=", Frame::getArr($config, 'cli_len', 131)) . PHP_EOL;
        }
    }

    /**
     * 通过配置
     * @param string|int    $name
     * @param array|Closure $config
     * @param int           $count
     * @return Worker
     */
    public static function set(string|int $name, array|Closure $config, int $count = 1): Worker {
        if (is_array($config)) {
            $worker = new Worker($config['listen'] ?? null, $config['context'] ?? []);
            foreach ($config as $key => $value) {
                if (isset($worker->$key) || in_array($key, self::$onList)) {
                    $worker->$key = $value;
                }
            }
            if (!isset($config['count'])) {
                $worker->count = $count;
            }
        } else {
            $worker = new Worker();
            $worker->count = $count;
            $worker->onWorkerStart = function(worker $worker) use ($config) {
                $config($worker);
            };
        }
        ++static::$setCount;
        $worker->name = (string) $name;
        return $worker;
    }

    /**
     * 通过目录
     * @param string|array $path
     * @param string|null  $base
     * @param string       $methodName
     * @return bool
     */
    public static function path(string|array $path, string|null $base = null, string $methodName = 'aloneConfig'): bool {
        $status = false;
        $array = static::getPathConfig($path, $base, $methodName);
        foreach ($array as $name => $value) {
            ++static::$setCount;
            $worker = static::set($name, $value);
            static::setWorkerOn($name, $worker);
            $status = true;
        }
        return $status;
    }

    /**
     * 通过类名
     * @param string|array $namespace
     * @param string       $methodName
     * @return bool
     */
    public static function name(string|array $namespace, string $methodName = 'aloneConfig'): bool {
        $status = false;
        $namespace = is_array($namespace) ? $namespace : [$namespace];
        foreach ($namespace as $value) {
            $config = static::getClassConfig($value, $methodName);
            if (!empty($config)) {
                ++static::$setCount;
                $name = key($config);
                $worker = static::set($name, $config[$name] ?? []);
                static::setWorkerOn($name, $worker);
                $status = true;
            }
        }
        return $status;
    }

    /**
     * 代理ssh
     * @param string|array $config [本地端口=>代理的ip:端口]
     * @param int          $count
     * @return void
     */
    public static function ssh(string|array $config, int $count = 10): void {
        foreach ($config as $k => $v) {
            $val = is_array($v) ? $v : ['ssh' => $v];
            static::set($val['ssh'], array_merge([
                'listen'    => 'tcp://0.0.0.0:' . $k,
                'count'     => $count,
                'onConnect' => function(TcpConnection $tcp) use ($val) {
                    $async = new AsyncTcpConnection('tcp://' . $val['ssh']);
                    $async->onMessage = function(AsyncTcpConnection $async, $buffer) use ($tcp) {
                        $tcp->send($buffer);
                    };
                    $async->onClose = function(AsyncTcpConnection $async) use ($tcp) {
                        $tcp->close();
                    };
                    $async->onError = function(AsyncTcpConnection $async) use ($tcp) {
                        $tcp->close();
                    };
                    $tcp->onMessage = function(TcpConnection $tcp, $buffer) use ($async) {
                        $async->send($buffer);
                    };
                    $tcp->onClose = function(TcpConnection $tcp) use ($async) {
                        $async->close();
                    };
                    $tcp->onError = function(TcpConnection $tcp) use ($async) {
                        $async->close();
                    };
                    $async->connect();
                }
            ], $val));
        }
        static::start('proxy_ssh');
    }

    /**
     * 设置监控文件更新
     * @param array|string $monitor 监控目录 [绝对路径=>'文件格式'] || [绝对路径]
     * @param bool         $type    守护进程是否监控文件
     * @return bool
     */
    public static function monitor(array|string $monitor, bool $type = false): bool {
        $status = false;
        static::$monitorPathList = [];
        static::setMonitor($monitor);
        if (!empty(static::$monitorPathList)) {
            $status = true;
            ++static::$setCount;
            static::set('alone.monitor', function(worker $worker) use ($type) {
                if (empty($worker::$daemonize) || !empty($type)) {
                    Timer::add(1, function() {
                        static::fileMonitor(static::$monitorPathList);
                    });
                }
            });
        }
        return $status;
    }

    /**
     * 启动Worker 带 监控文件更新
     * @param string      $name 日志名称
     * @param string|null $cli  类名,有namespace要带上
     * @param int         $row  左则位数
     * @param int         $len  总长度
     * @param bool        $status
     * @return void
     */
    public static function start(string $name, string|null $cli = null, int $row = 23, int $len = 131, bool $status = true): void {
        global $argv;
        if ($status && !empty(in_array(($argv[1] ?? ''), ['start', 'restart', 'stop', 'status', 'reload', 'connections']))) {
            static::setWorkerPath('/alone/' . trim($name, '/'));
            if (static::$setCount > 0) {
                Worker::runAll();
            }
        } elseif ($cli) {
            CliHelper::cli($cli, $row, $len);
        }
    }

    /**
     * 设置目录
     * @param string $path 相对路径
     * @return void
     */
    public static function setWorkerPath(string $path): void {
        $path = runtime_path($path);
        Frame::mkDir($path);
        Worker::$stdoutFile = Frame::dirPath($path, date('Ymd') . '.stdout');
        Worker::$logFile = Frame::dirPath($path, date('Ymd') . '.log');
        Worker::$pidFile = Frame::dirPath($path, 'worker.pid');
    }

    /**
     * 获取指定目录下的文件参数
     * @param string|array $path       绝对路径
     * @param string|null  $base       根目录位置
     * @param string       $methodName 类的方法名
     * @return array [.类名=>配置]
     */
    public static function getPathConfig(string|array $path, string|null $base = null, string $methodName = 'aloneConfig'): array {
        $list = is_array($path) ? $path : [$path];
        $base = $base ?? alone_root_path();
        foreach ($list as $item) {
            if (is_dir($item)) {
                $fileList = Frame::getDirFile($item);
                foreach ($fileList as $file) {
                    $namespace = Frame::delFileFormat(substr($file, strlen($base)));
                    $array = array_merge($array ?? [], static::getClassConfig($namespace, $methodName));
                }
            }
        }
        return $array ?? [];
    }

    /**
     * 获取类名配置
     * @param string $namespace
     * @param string $methodName
     * @return array
     */
    public static function getClassConfig(string $namespace, string $methodName = 'aloneConfig'): array {
        $className = "\\" . trim(Frame::strRep(Frame::strRep($namespace, '.', '\\'), '/', '\\'), '\\');
        $config = method_exists($className, $methodName) ? call_user_func([$className, $methodName]) : [];
        if (!empty($config)) {
            $keys = Frame::strRep(trim($className, '\\'), '\\', '.');
            return [$keys => array_merge(['handler' => $className], $config)];
        }
        return $array ?? [];
    }

    /**
     * 定时器
     * @param int|float $interval
     * @param callable  $callable
     * @return bool|int
     */
    public static function timer(int|float $interval, callable $callable): bool|int {
        return Timer::add($interval, function() use ($interval, $callable) {
            $callable();
            static::timer($interval, $callable);
        }, [], false);
    }

    /**
     * @return void
     */
    public static function restart(): void {
        posix_kill(posix_getppid(), SIGUSR1);
    }

    /**
     * 设置监控目录
     * @param string|array|null $path
     * @param array|string      $type
     * @return void
     */
    protected static function setMonitor(string|array|null $path, array|string $type = 'php'): void {
        if (is_array($path)) {
            foreach ($path as $key => $val) {
                if (is_string($key)) {
                    if (is_dir($key)) {
                        static::setMonitor($key, $val);
                    }
                } else {
                    static::setMonitor($val, $type);
                }
            }
        } elseif (!empty($path)) {
            static::$monitorPathList[$path] = (is_string($type) ? explode(',', $type) : $type);
        }
    }

    /**
     * 监控文件更新
     * @param string|array $path 监控绝对路径目录
     * @param array        $type 监控文件格式
     * @return void
     */
    protected static function fileMonitor(string|array $path, array $type = ['php']): void {
        if (is_array($path)) {
            foreach ($path as $key => $val) {
                if (is_string($key)) {
                    static::fileMonitor($key, $val);
                } else {
                    static::fileMonitor($val, $type);
                }
            }
        } else {
            static::$monitorFileTime[$path] = static::$monitorFileTime[$path] ?? time();
            $dir_iterator = new RecursiveDirectoryIterator($path);
            $iterator = new RecursiveIteratorIterator($dir_iterator);
            foreach ($iterator as $file) {
                if (in_array(pathinfo($file, PATHINFO_EXTENSION), $type)) {
                    if (static::$monitorFileTime[$path] < $file->getMTime()) {
                        echo date("Y-m-d H:i:s") . " : " . $file . " update and reload\n";
                        static::restart();
                        static::$monitorFileTime[$path] = $file->getMTime();
                        break;
                    }
                }
            }
        }
    }

    /**
     * 重启设置,为空不开启
     * int=多久重启一次
     * string=crontab
     * @param $restart
     * @return void
     */
    public static function aloneRestart($restart): void {
        if (is_string($restart)) {
            new Crontab($restart, function() {
                static::restart();
            });
        } elseif (is_numeric($restart)) {
            static::timer($restart, function() {
                static::restart();
            });
        }
    }

    /**
     * 通过类名设置方法
     * @param mixed $className
     * @param mixed $worker
     * @return void
     */
    protected static function setWorkerOn(mixed $className, mixed $worker): void {
        $className = "\\" . trim(Frame::strRep(Frame::strRep($className, '/', '\\'), '.', '\\'), '\\');
        $self = new $className();
        foreach (self::$onList as $key) {
            if (!isset($config[$key]) && method_exists($self, $key)) {
                $worker->$key = [$self, $key];
            }
        }
    }
}