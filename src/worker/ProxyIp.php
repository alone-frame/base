<?php

namespace AloneFrame\base\worker;

use Workerman\Timer;
use Workerman\Worker;
use Workerman\Protocols\Http\Request;
use Workerman\Protocols\Http\Response;
use Workerman\Connection\TcpConnection;
use Workerman\Connection\AsyncUdpConnection;
use Workerman\Connection\AsyncTcpConnection;

ini_set("memory_limit", "10240M");
define('STAGE_INIT', 0);
define('STAGE_AUTH', 1);
define('STAGE_ADDR', 2);
define('STAGE_UDP_ASSOC', 3);
define('STAGE_DNS', 4);
define('STAGE_CONNECTING', 5);
define('STAGE_STREAM', 6);
define('STAGE_DESTROYED', -1);

define('CMD_CONNECT', 1);
define('CMD_BIND', 2);
define('CMD_UDP_ASSOCIATE', 3);

define('ERR_GENERAL', 1);
define('ERR_NOT_ALLOW', 2);
define('ERR_NETWORK', 3);
define('ERR_HOST', 4);
define('ERR_REFUSE', 5);
define('ERR_TTL_EXPIRED', 6);
define('ERR_UNKNOW_COMMAND', 7);
define('ERR_UNKNOW_ADDR_TYPE', 8);
define('ERR_UNKNOW', 9);

define('ADDRTYPE_IPV4', 1);
define('ADDRTYPE_IPV6', 4);
define('ADDRTYPE_HOST', 3);

define('METHOD_NO_AUTH', 0);
define('METHOD_GSSAPI', 1);
define('METHOD_USER_PASS', 2);

class ProxyIp{
    protected array $config = [];

    /**
     * @param string $file 白名单文件
     * @param string $host 内网IP
     */
    public static function set(string $file = __DIR__ . '/ip.cache', string $host = '0.0.0.0'): static {
        return new static($file, $host);
    }

    /**
     * 开启socks5代理,复用时端口不能重复
     * @param int $port      socks5代理端口
     * @param int $udp_proxy udp内部使用端口
     * @param int $count     socks5代理进程
     * @param int $udp_count udp 子进程数量
     * @return $this
     */
    public function socks5(int $port, int $udp_proxy = 29444, int $count = 20, int $udp_count = 20): static {
        return $this->socks5_proxy($port, $udp_proxy, $count, $udp_count);
    }


    /**
     * 开启http代理,复用时端口不能重复
     * @param int $port  http代理端口
     * @param int $count http代理进程
     * @return $this
     */
    public function http(int $port, int $count = 20): static {
        return $this->http_proxy($port, $count);
    }

    /**
     * 开启http服务使用白名单,复用时端口不能重复
     * @param int        $port  http访问端口
     * @param string|int $token 操作白名单链接(支持带参数)
     * @param int        $count http进程
     * @return $this
     */
    public function server(int $port, string|int $token, int $count = 10): static {
        return $this->http_serve($token, $port, $count);
    }

    /**
     * 启动
     */
    public function start() {
        Worker::runAll();
    }

    public function __construct($file = __DIR__ . '/ip.cache', $host = '0.0.0.0') {
        global $argv;
        $this->config = [
            'host'      => $host,
            'ip_file'   => $file,
            "udp_ports" => 0,
            "auth"      => [METHOD_NO_AUTH => true],
        ];
        if (($argv[1] ?? '') == 'start' && !empty($ip_file = $this->config['ip_file'])) {
            if (empty(is_file($ip_file))) {
                self::createFilePath($ip_file);
                @file_put_contents($ip_file, "#白名单列表,IP段可使用*号或者[0-255]\r\n*");
            }
        }
    }

    protected function http_proxy($port, $count = 20): static {
        $worker = new Worker('tcp://' . $this->config['host'] . ':' . $port);
        $worker->count = $count;
        $worker->name = 'http-proxy-' . $port;
        $worker->onMessage = function($connection, $buffer) {
            if (empty($this->getAnyIp($connection->getRemoteIp()))) {
                $connection->close();
                return;
            }
            [$method, $address, $http_version] = explode(' ', $buffer);
            $url_data = parse_url($address);
            $address = !isset($url_data['port']) ? "{$url_data['host']}:80" : "{$url_data['host']}:{$url_data['port']}";
            $remote_connection = new AsyncTcpConnection("tcp://$address");
            if ($method !== 'CONNECT') {
                $remote_connection->send($buffer);
            } else {
                $connection->send("HTTP/1.1 200 Connection Established\r\n\r\n");
            }
            $remote_connection->pipe($connection);
            $connection->pipe($remote_connection);
            $remote_connection->connect();
        };
        return $this;
    }

    protected function http_serve($token, $port, $count = 10): static {
        $worker = new Worker('http://' . $this->config['host'] . ':' . $port);
        $worker->count = $count;
        $worker->name = 'http-serve-' . $port;
        $token = ('/' . ltrim(trim($token), '/'));
        $worker->onMessage = function(TcpConnection $con, Request $req) use ($token) {
            $uri = trim($req->uri());
            if (str_starts_with(trim($uri, '/'), trim($token, '/'))) {
                $body = @file_get_contents($this->config['ip_file']) ?: '';
                $body = str_replace("\r\n", PHP_EOL, $body);
                $array = explode(PHP_EOL, trim($body, PHP_EOL));
                switch ($req->get('act')) {
                    case 'list':
                        $bool = false;
                        if (!empty($ip = $req->get('del'))) {
                            foreach ($array as $k => $v) {
                                if (($ip == $v) || (is_numeric($ip) && $ip > 0 && $k = $ip)) {
                                    $bool = true;
                                    unset($array[$k]);
                                    break;
                                }
                            }
                        } elseif (!empty($ip = $req->get('add')) && count(explode('.', $ip)) > 3) {
                            $bool = true;
                            foreach ($array as $v) {
                                if ($ip == $v) {
                                    $bool = false;
                                    break;
                                }
                            }
                            if ($bool === true) {
                                $array[] = $ip;
                            }
                        } elseif ($req->get('delete') == 'all') {
                            $bool = true;
                            $array = ["#白名单列表,IP段可使用*号或者[0-255]\r\n"];
                        }
                        if ($bool === true) {
                            $array = array_values($array);
                            $body = trim(join("\r\n", $array), "\r\n");
                            @file_put_contents($this->config['ip_file'], $body);
                        }
                        $ip = static::getRealIp($con, $req);
                        $content = "<pre>" . print_r($array, true) . "</pre>";
                        $content .= "<div>current ip:<a href='https://api.ip.sb/geoip/" . $ip . "' target='_blank'>" . $ip . "</a></div>";
                        $content .= "<div>get ip web</div>";
                        $content .= "<div><a href='/ip' target='_blank'>server/ip</a></div>";
                        $content .= "<div><a href='https://ipinfo.io/ip' target='_blank'>ipinfo.io/ip</a></div>";
                        $content .= "<div><a href='https://api.ip.sb/ip' target='_blank'>ip.sb/ip</a></div>";
                        $content .= "<div><a href='https://api.ip.sb/geoip' target='_blank'>ip.sb/geoip</a></div>";
                        $content .= "<div><a href='http://cip.cc/' target='_blank'>cip.cc</a></div>";
                        $con->send($content);
                        return;
                    default:
                        if (!empty($ip = trim($con->getRemoteIp()))) {
                            $bool = true;
                            foreach ($array as $v) {
                                if ($ip == $v) {
                                    $bool = false;
                                    break;
                                }
                            }
                            if ($bool === true) {
                                $array[] = $ip;
                                $body = trim(join("\r\n", $array), "\r\n");
                                $put = @file_put_contents($this->config['ip_file'], $body);
                                $con->send((($put > 0) ? 'success' : 'fail') . '--->' . $ip);
                            } else {
                                $con->send('existed--->' . $ip);
                            }
                            return;
                        }
                        break;
                }
            } else {
                switch (trim(strtolower(trim($req->path())), '/')) {
                    case 'ip':
                        $con->send(new Response(200, ['Content-Type' => 'text/plain'], static::getRealIp($con, $req, empty($req->get('type')))));
                        return;
                    case 'robots.txt':
                        $con->send(new Response(200, ['Content-Type' => 'text/plain'], "User-agent: *\r\nDisallow: /"));
                        return;
                    case 'favicon.ico':
                        $con->send(new Response(204));
                        return;
                }
            }
            $html = '<html><head><title>404 Not Found</title></head><body><center><h1>404 Not Found</h1></center><hr></body></html>';
            $con->send(new Response(404, [], $html));
        };
        return $this;
    }

    protected function socks5_proxy($port, $udp_proxy, $count = 20, $udp_count = 20): static {
        $this->config['udp_port'] = $udp_proxy;
        $this->config['udp_count'] = $udp_count;
        $worker = new Worker('tcp://' . $this->config['host'] . ':' . $port);
        $worker->count = $count;
        $worker->name = 'socks5-proxy-' . $port;
        $worker->onConnect = function($connection) {
            $connection->stage = STAGE_INIT;
            $connection->auth_type = null;
        };
        $worker->onMessage = function($connection, $buffer) {
            if (empty($this->getAnyIp($connection->getRemoteIp()))) {
                $connection->close();
                return;
            }
            self::logger(LOG_DEBUG, "recv:" . bin2hex($buffer));
            switch ($connection->stage) {
                case STAGE_INIT:
                    $request = [];
                    $offset = 0;
                    if (strlen($buffer) < 2) {
                        self::logger(LOG_ERR, "init socks5 failed. buffer too short.");
                        $connection->send("\x05\xff");
                        $connection->stage = STAGE_DESTROYED;
                        $connection->close();
                        return;
                    }
                    $request['ver'] = ord($buffer[$offset]);
                    $offset += 1;
                    $request['method_count'] = ord($buffer[$offset]);
                    $offset += 1;
                    if (strlen($buffer) < 2 + $request['method_count']) {
                        self::logger(LOG_ERR, "init authentic failed. buffer too short.");
                        $connection->send("\x05\xff");
                        $connection->stage = STAGE_DESTROYED;
                        $connection->close();
                        return;
                    }
                    $request['methods'] = [];
                    for ($i = 1; $i <= $request['method_count']; $i++) {
                        $request['methods'][] = ord($buffer[$offset]);
                        $offset++;
                    }
                    foreach ($this->config['auth'] as $k => $v) {
                        if (in_array($k, $request['methods'])) {
                            self::logger(LOG_INFO, "auth client via method $k");
                            self::logger(LOG_DEBUG, "send:" . bin2hex("\x05" . chr($k)));
                            $connection->send("\x05" . chr($k));
                            if ($k == 0) {
                                $connection->stage = STAGE_ADDR;
                            } else {
                                $connection->stage = STAGE_AUTH;
                            }
                            $connection->auth_type = $k;
                            return;
                        }
                    }
                    if ($connection->stage != STAGE_AUTH) {
                        self::logger(LOG_ERR, "client has no matched auth methods");
                        self::logger(LOG_DEBUG, "send:" . bin2hex("\x05\xff"));
                        $connection->send("\x05\xff");
                        $connection->stage = STAGE_DESTROYED;
                        $connection->close();
                    }
                    return;
                case STAGE_AUTH:
                    $request = [];
                    $offset = 0;
                    if (strlen($buffer) < 5) {
                        self::logger(LOG_ERR, "auth failed. buffer too short.");
                        $connection->send("\x01\x01");
                        $connection->stage = STAGE_DESTROYED;
                        $connection->close();
                        return;
                    }
                    switch ($connection->auth_type) {
                        case METHOD_USER_PASS:
                            $request['sub_ver'] = ord($buffer[$offset]);
                            $offset += 1;
                            $request['user_len'] = ord($buffer[$offset]);
                            $offset += 1;
                            if (strlen($buffer) < 2 + $request['user_len'] + 2) {
                                self::logger(LOG_ERR, "auth username failed. buffer too short.");
                                $connection->send("\x01\x01");
                                $connection->stage = STAGE_DESTROYED;
                                $connection->close();
                                return;
                            }
                            $request['user'] = substr($buffer, $offset, $request['user_len']);
                            $offset += $request['user_len'];
                            $request['pass_len'] = ord($buffer[$offset]);
                            $offset += 1;
                            if (strlen($buffer) < 2 + $request['user_len'] + 1 + $request['pass_len']) {
                                self::logger(LOG_ERR, "auth password failed. buffer too short.");
                                $connection->send("\x01\x01");
                                $connection->stage = STAGE_DESTROYED;
                                $connection->close();
                                return;
                            }
                            $request['pass'] = substr($buffer, $offset, $request['pass_len']);
                            $offset += $request['pass_len'];
                            if (($this->config["auth"][METHOD_USER_PASS])($request)) {
                                self::logger(LOG_INFO, "auth ok");
                                $connection->send("\x01\x00");
                                $connection->stage = STAGE_ADDR;
                            } else {
                                self::logger(LOG_INFO, "auth failed");
                                $connection->send("\x01\x01");
                                $connection->stage = STAGE_DESTROYED;
                                $connection->close();
                            }
                            break;
                        default:
                            self::logger(LOG_ERR, "unsupport auth type");
                            $connection->send("\x01\x01");
                            $connection->stage = STAGE_DESTROYED;
                            $connection->close();
                            break;
                    }
                    return;
                case STAGE_ADDR:
                    $request = [];
                    $offset = 0;
                    if (strlen($buffer) < 4) {
                        self::logger(LOG_ERR, "connect init failed. buffer too short.");
                        $connection->stage = STAGE_DESTROYED;
                        $response = [];
                        $response['ver'] = 5;
                        $response['rep'] = ERR_GENERAL;
                        $response['rsv'] = 0;
                        $response['addr_type'] = ADDRTYPE_IPV4;
                        $response['bind_addr'] = '0.0.0.0';
                        $response['bind_port'] = 0;
                        $connection->close(self::packResponse($response));
                        return;
                    }
                    $request['ver'] = ord($buffer[$offset]);
                    $offset += 1;
                    $request['command'] = ord($buffer[$offset]);
                    $offset += 1;
                    $request['rsv'] = ord($buffer[$offset]);
                    $offset += 1;
                    $request['addr_type'] = ord($buffer[$offset]);
                    $offset += 1;
                    switch ($request['addr_type']) {
                        case ADDRTYPE_IPV4:
                            if (strlen($buffer) < 4 + 4) {
                                self::logger(LOG_ERR, "connect init failed.[ADDRTYPE_IPV4] buffer too short.");
                                $connection->stage = STAGE_DESTROYED;
                                $response = [];
                                $response['ver'] = 5;
                                $response['rep'] = ERR_GENERAL;
                                $response['rsv'] = 0;
                                $response['addr_type'] = ADDRTYPE_IPV4;
                                $response['bind_addr'] = '0.0.0.0';
                                $response['bind_port'] = 0;
                                $connection->close(self::packResponse($response));
                                return;
                            }
                            $tmp = substr($buffer, $offset, 4);
                            $ip = 0;
                            for ($i = 0; $i < 4; $i++) {
                                $ip += ord($tmp[$i]) * pow(256, 3 - $i);
                            }
                            $request['dest_addr'] = long2ip($ip);
                            $offset += 4;
                            break;
                        case ADDRTYPE_HOST:
                            $request['host_len'] = ord($buffer[$offset]);
                            $offset += 1;
                            if (strlen($buffer) < 4 + 1 + $request['host_len']) {
                                self::logger(LOG_ERR, "connect init failed.[ADDRTYPE_HOST] buffer too short.");
                                $connection->stage = STAGE_DESTROYED;
                                $response = [];
                                $response['ver'] = 5;
                                $response['rep'] = ERR_GENERAL;
                                $response['rsv'] = 0;
                                $response['addr_type'] = ADDRTYPE_IPV4;
                                $response['bind_addr'] = '0.0.0.0';
                                $response['bind_port'] = 0;
                                $connection->close(self::packResponse($response));
                                return;
                            }
                            $request['dest_addr'] = substr($buffer, $offset, $request['host_len']);
                            $offset += $request['host_len'];
                            break;
                        case ADDRTYPE_IPV6:
                        default:
                            self::logger(LOG_ERR, "unsupport ipv6. [ADDRTYPE_IPV6].");
                            $connection->stage = STAGE_DESTROYED;
                            $response = [];
                            $response['ver'] = 5;
                            $response['rep'] = ERR_UNKNOW_ADDR_TYPE;
                            $response['rsv'] = 0;
                            $response['addr_type'] = ADDRTYPE_IPV4;
                            $response['bind_addr'] = '0.0.0.0';
                            $response['bind_port'] = 0;
                            $connection->close(self::packResponse($response));
                            return;
                    }
                    if (strlen($buffer) < $offset + 2) {
                        self::logger(LOG_ERR, "connect init failed.[port] buffer too short.");
                        $connection->stage = STAGE_DESTROYED;
                        $response = [];
                        $response['ver'] = 5;
                        $response['rep'] = ERR_GENERAL;
                        $response['rsv'] = 0;
                        $response['addr_type'] = ADDRTYPE_IPV4;
                        $response['bind_addr'] = '0.0.0.0';
                        $response['bind_port'] = 0;
                        $connection->close(self::packResponse($response));
                        return;
                    }
                    $portData = unpack("n", substr($buffer, $offset, 2));
                    $request['dest_port'] = $portData[1];
                    $offset += 2;
                    switch ($request['command']) {
                        case CMD_CONNECT:
                            self::logger(LOG_DEBUG, 'tcp://' . $request['dest_addr'] . ':' . $request['dest_port']);
                            if ($request['addr_type'] == ADDRTYPE_HOST) {
                                if (!filter_var($request['dest_addr'], FILTER_VALIDATE_IP)) {
                                    self::logger(LOG_DEBUG, 'resolve DNS ' . $request['dest_addr']);
                                    $connection->stage = STAGE_DNS;
                                    $addr = @dns_get_record((isset($request['dest_addr']) ? $request['dest_addr'] : ''), DNS_A);
                                    $addr = $addr ? array_pop($addr) : null;
                                    self::logger(LOG_DEBUG, 'DNS resolved ' . $request['dest_addr'] . ' => ' . (isset($addr['ip']) ? $addr['ip'] : ''));
                                } else {
                                    $addr['ip'] = $request['dest_addr'];
                                }
                            } else {
                                $addr['ip'] = $request['dest_addr'];
                            }
                            if ($addr) {
                                $connection->stage = STAGE_CONNECTING;
                                $remote_connection = new AsyncTcpConnection('tcp://' . $addr['ip'] . ':' . $request['dest_port']);
                                $remote_connection->onConnect = function($remote_connection) use ($connection, $request) {
                                    $connection->state = STAGE_STREAM;
                                    $response = [];
                                    $response['ver'] = 5;
                                    $response['rep'] = 0;
                                    $response['rsv'] = 0;
                                    $response['addr_type'] = $request['addr_type'];
                                    $response['bind_addr'] = '0.0.0.0';
                                    $response['bind_port'] = 18512;
                                    $connection->send(self::packResponse($response));
                                    $connection->pipe($remote_connection);
                                    $remote_connection->pipe($connection);
                                    self::logger(LOG_DEBUG, 'tcp://' . $request['dest_addr'] . ':' . $request['dest_port'] . ' [OK]');
                                };
                                $remote_connection->connect();
                            } else {
                                self::logger(LOG_DEBUG, 'DNS resolve failed.');
                                $connection->stage = STAGE_DESTROYED;
                                $response = [];
                                $response['ver'] = 5;
                                $response['rep'] = ERR_HOST;
                                $response['rsv'] = 0;
                                $response['addr_type'] = ADDRTYPE_IPV4;
                                $response['bind_addr'] = '0.0.0.0';
                                $response['bind_port'] = 0;
                                $connection->close(self::packResponse($response));
                            }
                            break;
                        case CMD_UDP_ASSOCIATE:
                            $connection->stage = STAGE_UDP_ASSOC;
                            if ($this->config['udp_ports'] == 0) {
                                $connection->udpWorker = new Worker('udp://' . $this->config['host'] . ':0');
                                $connection->udpWorker->incId = 0;
                                $connection->udpWorker->onMessage = function($udp_connection, $data) use ($connection) {
                                    self::udpWorkerOnMessage($udp_connection, $data, $connection->udpWorker);
                                };
                                $connection->udpWorker->listen();
                                $listenInfo = stream_socket_get_name($connection->udpWorker->getMainSocket(), false);
                                [$bind_addr, $bind_port] = explode(":", $listenInfo);
                            } else {
                                $bind_port = $this->config['udp_ports'];
                            }
                            $bind_addr = $this->config['host'];
                            $response['ver'] = 5;
                            $response['rep'] = 0;
                            $response['rsv'] = 0;
                            $response['addr_type'] = ADDRTYPE_IPV4;
                            $response['bind_addr'] = $bind_addr;
                            $response['bind_port'] = $bind_port;
                            self::logger(LOG_DEBUG, 'send:' . bin2hex(self::packResponse($response)));
                            $connection->send(self::packResponse($response));
                            break;
                        default:
                            self::logger(LOG_ERR, "connect init failed. unknow command.");
                            $connection->stage = STAGE_DESTROYED;
                            $response = [];
                            $response['ver'] = 5;
                            $response['rep'] = ERR_UNKNOW_COMMAND;
                            $response['rsv'] = 0;
                            $response['addr_type'] = ADDRTYPE_IPV4;
                            $response['bind_addr'] = '0.0.0.0';
                            $response['bind_port'] = 0;
                            $connection->close(self::packResponse($response));
                            return;
                    }
            }
        };
        $worker->onClose = function($connection) {
            self::logger(LOG_INFO, "client closed.");
        };
        $udpWorker = new Worker('udp://' . $this->config['host'] . ':' . $this->config['udp_port']);
        $udpWorker->count = $this->config['udp_count'];
        $udpWorker->incId = 0;
        $udpWorker->onWorkerStart = function($worker) {
            $worker->udpConnections = [];
            Timer::add(1, function() use ($worker) {
                foreach ($worker->udpConnections as $id => $remote_connection) {
                    if ($remote_connection->deadTime < time()) {
                        $remote_connection->close();
                        $remote_connection->udp_connection->close();
                        unset($worker->udpConnections[$id]);
                    }
                }
            });
        };
        $udpWorker->onMessage = 'self::udpWorkerOnMessage';
        return $this;
    }

    protected static function packResponse($response): string {
        $data = "";
        $data .= chr($response['ver']);
        $data .= chr($response['rep']);
        $data .= chr($response['rsv']);
        $data .= chr($response['addr_type']);
        switch ($response['addr_type']) {
            case ADDRTYPE_IPV4:
                $tmp = explode('.', $response['bind_addr']);
                foreach ($tmp as $block) {
                    $data .= chr($block);
                }
                break;
            case ADDRTYPE_HOST:
                $host_len = strlen($response['bind_addr']);
                $data .= chr($host_len);
                $data .= $response['bind_addr'];
                break;
        }
        $data .= pack("n", $response['bind_port']);
        return $data;
    }

    protected static function udpWorkerOnMessage($udp_connection, $data, &$worker) {
        self::logger(LOG_DEBUG, 'send:' . bin2hex($data));
        $request = [];
        $offset = 0;
        $request['rsv'] = substr($data, $offset, 2);
        $offset += 2;
        $request['frag'] = ord($data[$offset]);
        $offset += 1;
        $request['addr_type'] = ord($data[$offset]);
        $offset += 1;
        switch ($request['addr_type']) {
            case ADDRTYPE_IPV4:
                $tmp = substr($data, $offset, 4);
                $ip = 0;
                for ($i = 0; $i < 4; $i++) {
                    $ip += ord($tmp[$i]) * pow(256, 3 - $i);
                }
                $request['dest_addr'] = long2ip($ip);
                $offset += 4;
                break;
            case ADDRTYPE_HOST:
                $request['host_len'] = ord($data[$offset]);
                $offset += 1;
                $request['dest_addr'] = substr($data, $offset, $request['host_len']);
                $offset += $request['host_len'];
                break;
            case ADDRTYPE_IPV6:
                if (strlen($data) < 22) {
                    echo "buffer too short\n";
                    $error = true;
                    break;
                }
                echo "todo ipv6\n";
                $error = true;
            default:
                echo "unsupported addrtype {$request['addr_type']}\n";
                $error = true;
        }
        $portData = unpack("n", substr($data, $offset, 2));
        $request['dest_port'] = $portData[1];
        $offset += 2;
        if ($request['addr_type'] == ADDRTYPE_HOST) {
            self::logger(LOG_DEBUG, '解析DNS');
            $addr = dns_get_record($request['dest_addr'], DNS_A);
            $addr = $addr ? array_pop($addr) : null;
            self::logger(LOG_DEBUG, 'DNS 解析完成' . $addr['ip']);
        } else {
            $addr['ip'] = $request['dest_addr'];
        }
        $remote_connection = new AsyncUdpConnection('udp://' . $addr['ip'] . ':' . $request['dest_port']);
        $remote_connection->id = $worker->incId++;
        $remote_connection->udp_connection = $udp_connection;
        $remote_connection->onConnect = function($remote_connection) use ($data, $offset) {
            $remote_connection->send(substr($data, $offset));
        };
        $remote_connection->onMessage = function($remote_connection, $recv) use ($data, $offset, $udp_connection, $worker) {
            $udp_connection->close(substr($data, 0, $offset) . $recv);
            $remote_connection->close();
            unset($worker->udpConnections[$remote_connection->id]);
        };
        $remote_connection->deadTime = time() + 3;
        $remote_connection->connect();
        $worker->udpConnections[$remote_connection->id] = $remote_connection;
    }

    protected static function logger($level, $str) {
        if (LOG_DEBUG >= $level) {
            echo "";
        }
    }

    protected function getAnyIp($ip): bool {
        if (!empty(in_array($ip, ['0.0.0.0', '127.0.0.1']))) {
            return true;
        }
        $body = @file_get_contents($this->config['ip_file']) ?: '';
        if (!empty($body)) {
            $body = str_replace("\r\n", PHP_EOL, $body);
            $array = explode(PHP_EOL, trim($body, PHP_EOL));
            return self::isIp($ip, $array);
        }
        return false;
    }

    protected static function isIp($ip, $array): bool {
        if (!empty($array) && filter_var($ip, FILTER_VALIDATE_IP) !== false) {
            foreach ($array as $v) {
                if (!empty($v) && ($ip == $v || $v == '*')) {
                    return true;
                }
            }
            $arrIp = explode('.', $ip);
            foreach ($array as $v) {
                if (str_contains($v, ".")) {
                    $ifIp = $arrIp;
                    $arr = explode('.', $v);
                    if (str_contains($v, "*") || (str_contains($v, "[") && str_contains($v, "]"))) {
                        foreach ($arr as $key => $val) {
                            if (isset($ifIp[$key])) {
                                if ($val == '*') {
                                    $ifIp[$key] = $val;
                                } elseif (str_starts_with($val, '[') && str_ends_with($val, ']')) {
                                    $ipVal = $ifIp[$key];
                                    $arrA = explode('[', $val);
                                    $arrB = explode(']', ($arrA[1] ?? ''));
                                    $arrC = explode('-', ($arrB[0] ?? ''));
                                    $min = $arrC[0] ?? 0;
                                    $max = $arrC[1] ?? 255;
                                    if ($ipVal >= $min && $ipVal <= $max) {
                                        $ifIp[$key] = $val;
                                    }
                                }
                            }
                        }
                    }
                    if (join('.', $ifIp) == $v) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    protected static function createFilePath($filePath) {
        $path = dirname($filePath);
        if (empty(is_dir($path))) {
            mkdir($path, 0777, true);
        }
        return $filePath;
    }

    protected static function getRealIp(TcpConnection $con, Request $req, bool $safeMode = true): string {
        $remoteIp = $con->getRemoteIp();
        if ($safeMode && !static::isIntranetIp($remoteIp)) {
            return $remoteIp;
        }
        $ip = $req->header('x-real-ip', $req->header('x-forwarded-for',
            $req->header('client-ip', $req->header('x-client-ip',
                $req->header('via', $remoteIp)))));
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : $remoteIp;
    }

    protected static function isIntranetIp(string $ip): bool {
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return false;
        }
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return true;
        }
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return false;
        }
        $reservedIps = [
            1681915904 => 1686110207,
            3221225472 => 3221225727,
            3221225984 => 3221226239,
            3227017984 => 3227018239,
            3323068416 => 3323199487,
            3325256704 => 3325256959,
            3405803776 => 3405804031,
            3758096384 => 4026531839,
        ];
        $ipLong = ip2long($ip);
        foreach ($reservedIps as $ipStart => $ipEnd) {
            if (($ipLong >= $ipStart) && ($ipLong <= $ipEnd)) {
                return true;
            }
        }
        return false;
    }
}