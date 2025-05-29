<?php

namespace AloneFrame\base\worker;

use Workerman\Worker;
use Workerman\Protocols\Http\Request;
use Workerman\Protocols\Http\Response;
use Workerman\Connection\TcpConnection;
use Workerman\Connection\AsyncTcpConnection;

class ProxySocket{
    public Request            $req;
    public TcpConnection      $tcp;
    public AsyncTcpConnection $async;
    public string             $schema;     //目标协议
    public string             $domain;     //目标域名
    public string             $path;       //目标路径
    public string             $host;       //当前域名
    public string             $port;       //当前域名端口
    public array              $server = [];//$_SERVER
    //webSite方法代理网站默认配置
    public array $web_config = [
        'encoding' => true,//发送数据给目标时是否删除禁止编码,true=禁止
        'context'  => [],//AsyncTcpConnection context设置
        'on_async' => [],//AsyncTcpConnection 设置,on方法和headers等,可以重写方法
        'on_tcp'   => [],//TcpConnection 设置,on方法等,可以重写方法
        'callable' => [
            'remote' => 'to remote:callable($buffer,AsyncTcpConnection,$this)',//发送给目标网站的数据,可以自定修改
            'client' => 'to client:callable($buffer,TcpConnection,$this)'//发送给客户端的数据,可以自定修改
        ]
    ];
    //websocket方法代理网站默认配置
    public array $socket_config = [
        'custom_head' => [],//自定义头部信息设置,优选级1
        'header'      => false,//是否发送客户端的头部信息,true=全发送，array=选择头部名字,全小写,优选级3
        'origin'      => false,//是否发送域名,true=真实来源,array=自定(带http),多个会随机,优选级2
        'browser'     => false,//是否发送浏览器信息,true=真实来源,array=自定,多个会随机,优选级2
        'ip'          => false,//伪装ip,true使用客户端ip,array=自定ip,多个会随机,优选级2
        //伪装ip列表,['头部名字'=>'ip(为空使用生成的ip)']
        'ip_name'     => [
            'X-Real-IP',
            'X-Forwarded-For',
            'Client-Ip',
            'Remote-Addr',
            'Proxy-Client-Ip',
            'Wl-Proxy-Client-IP',
            'Cf-Connecting-Ip',
            'Cdn-Src-Ip'
        ],
        'send'        => '',//连接第一次发送内容,为空不发送
        'encoding'    => true,//发送数据给目标时是否删除禁止编码,true=禁止
        'context'     => [],//AsyncTcpConnection context设置
        'on_async'    => [],//AsyncTcpConnection 设置,on方法和headers等,可以重写方法
        'on_tcp'      => [],//TcpConnection 设置,on方法等,可以重写方法
        'callable'    => [
            'remote' => 'to remote:callable($body,AsyncTcpConnection,$this)',//发送给目标网站的数据,可以自定修改
            'client' => 'to client:callable($body,TcpConnection,$this)'//发送给客户端的数据,可以自定修改
        ]
    ];

    /**
     * websocket 代理客户端使用例
     * @param string $remote 目标 ws开头
     * @param int    $port   访问端口
     * @param int    $count  进程数量
     * @param string $name   进程名称
     * @param string $host   启动IP
     */
    public static function websocketProxy(string $remote, int $port, int $count = 20, string $name = 'websocket_client', string $host = '0.0.0.0') {
        $worker = new Worker('websocket://' . $host . ':' . $port);
        $worker->count = $count;
        $worker->name = $name;
        $worker->onConnect = function(TcpConnection $tcp) use ($remote) {
            static::websocket($tcp, $remote, [
                'header'   => ['User-Agent', 'Origin', 'Accept-Language'],
                'ip'       => true,
                'ip_name'  => [
                    'X-Real-IP',
                    'X-Forwarded-For'
                ],
                'send'     => date("Y-m-d H:i:s"),
                'encoding' => true,
                'callable' => [
                    'remote' => function($body, AsyncTcpConnection $con, self $self) {
                        //发送给目标的信息,可以在这里面处理
                        $con->send($body);
                    },
                    'client' => function($body, TcpConnection $con, self $self) {
                        //发送给客户端的信息,可以在这里面处理
                        $con->send($body);
                    }
                ]
            ]);
        };
    }

    /**
     * @param int    $port
     * @param int    $count
     * @param string $name
     * @param string $host
     */
    public static function websocket_server(int $port, int $count = 20, string $name = 'websocket_server', string $host = '0.0.0.0') {
        $worker = new Worker('websocket://' . $host . ':' . $port);
        $worker->count = $count;
        $worker->name = $name;
        $worker->onConnect = function(TcpConnection $con) {
            $con->onWebSocketConnect = function(TcpConnection $con, $buffer) {
                //print_r(['getRemoteIp' => $con->getRemoteIp(), 'header' => $buffer, 'SERVER' => $_SERVER]);
                $con->onMessage = function(TcpConnection $con, $data) {
                    $con->send('收到信息 ' . $data . "," . date('Y-m-d H:i:s'));
                };
                $con->onError = function(TcpConnection $con, $code, $msg) {
                    $con->send("Code：$code,Msg:$msg");
                    $con->close();
                };
                $con->onClose = function(TcpConnection $con) {
                    $con->close();
                };
            };
        };
    }

    /**
     * 代理网站客户端使用例
     * @param string $routing 设置网站,/set_path?url=网站(带http),url为不为域名时删除当前设置的网站
     * @param int    $port    访问端口
     * @param int    $count   进程数量
     * @param string $name    进程名称
     * @param string $host    启动IP
     */
    public static function webSiteProxy(string $routing, int $port, int $count = 20, string $name = 'http_client', string $host = '0.0.0.0') {
        $worker = new Worker('http://' . $host . ':' . $port);
        $worker->count = $count;
        $worker->name = $name;
        $worker->onMessage = function(TcpConnection $tcp, Request $req) use ($routing) {
            $session = $req->session();
            $website = $session->get('website');
            if (trim($req->path(), '/') == trim($routing, '/')) {
                $url = trim($req->get('url'));
                if (!empty($url)) {
                    $url = str_starts_with($url, 'http') ? $url : ('http://' . $url);
                    $web = static::getWeb($url);
                    $path = $web['path'] ?: "";
                    $websites = $web['web'] ?: "";
                }
                if ($website != ($websites ?? "")) {
                    if (!empty($websites)) {
                        $response = new Response(302, ['Location' => ($path ?? "/")]);
                    } else {
                        $response = static::errWebSite();
                    }
                    $session->set('website', $websites ?? "");
                    $tcp->send($response);
                    return;
                }
            }
            if (empty($website)) {
                $tcp->send(static::errWebSite());
                return;
            }
            $website = str_starts_with($website, 'http') ? $website : ('http://' . $website);
            static::webSite($tcp, $req, $website, $req->uri(), [
                'encoding' => true,
                'callable' => [
                    'remote' => function($buffer, AsyncTcpConnection $con, self $self) {
                        //发送给目标的信息,可以在这里面处理
                        $con->send($buffer);
                    },
                    'client' => function($buffer, TcpConnection $con, self $self) use ($routing) {
                        //发送给客户端的信息,可以在这里面处理
                        $location = static::getHtmlLocation($buffer);
                        if (empty($location)) {
                            $code = substr(trim(trim($buffer, "\r\n")), 9, 3);
                            if (($code == 301 || $code == 302)) {
                                $header = explode("\r\n\r\n", $buffer);
                                if (count($header) > 0) {
                                    $array = static::headToArr($header[0]);
                                    $location = ($array['location']['val'] ?? '');
                                }
                            }
                        }
                        if (!empty($location)) {
                            $web = static::getWeb($location);
                            $website = $web['web'];
                            if ($self->req->get('website') != $website) {
                                $path = $web['path'];
                                $self->req->session()->set('website', $website);
                                if (!empty($website)) {
                                    $con->send(new Response(302, ['Location' => $path]));
                                    return;
                                }
                                $con->send(static::errWebSite());
                                return;
                            }
                        }
                        $con->send($buffer, true);
                    }
                ]
            ]);
        };
    }

    /**
     * websocket代理客户端
     * @param TcpConnection $tcp
     * @param string        $remote 目标
     * @param array         $socket_config
     * @return static
     */
    public static function websocket(TcpConnection $tcp, string $remote, array $socket_config = []): static {
        $static = new static($tcp);
        $static->socket_config = array_merge($static->socket_config, $socket_config);
        $tcp->onWebSocketConnect = function(TcpConnection $con, $buffer) use ($static, $remote) {
            $static->tcp = $con;
            $static->setWebsocket(
                $remote,
                $static->socket_config['context']
            );
            $static->connectWebsocket(
                $static->socket_config['on_async'],
                $static->socket_config['on_tcp'],
                $static->socket_config['encoding'],
                $static->socket_config['callable']
            );
        };
        return $static;
    }

    /**
     * 网站代理客户端
     * @param TcpConnection $tcp
     * @param Request       $req
     * @param string        $website    目标域名，http开头
     * @param string        $path       目标路径
     * @param array         $web_config http设置
     * @return static
     */
    public static function webSite(TcpConnection $tcp, Request $req, string $website, string $path, array $web_config = []): static {
        $static = new static($tcp, $req);
        $static->web_config = array_merge($static->web_config, $web_config);
        $static->setWebSite(
            $website,
            $path,
            $static->web_config['context']
        );
        $static->connectWebSite(
            $static->web_config['on_async'],
            $static->web_config['on_tcp'],
            $static->web_config['encoding'],
            $static->web_config['callable']
        );
        return $static;
    }

    /**
     *
     */
    public static function start() {
        Worker::runAll();
    }

    /**
     * @param array $OnAsync  可选On方法,headers信息设置
     * @param array $OnTcp    可选On方法
     * @param bool  $encoding 发送数据给目标时是否删除禁止编码,true=禁止
     * @param array $callable ['remote'=>发送给目标callable($buffer,$tcp,$this), 'client'=>发送给客户callable($buffer,$tcp,$this)]
     * @return $this
     */
    protected function connectWebsocket(array $OnAsync = [], array $OnTcp = [], bool $encoding = true, array $callable = []): static {
        $headers = [];
        $this->server = $_SERVER;
        //伪装ip
        if (!empty($ip = $this->socket_config['ip']) && !empty($ip_name = $this->socket_config['ip_name'])) {
            $address = [];
            $adder = $this->tcp->getRemoteIp();
            if (is_array($ip)) {
                $key = count($ip) > 1 ? rand(0, (count($ip) - 1)) : key($ip);
                $adder = $ip[$key] ?? $adder;
            }
            foreach ($ip_name as $k => $v) {
                if (is_string($k)) {
                    $address[$k] = ((!empty($v) && is_string($v)) ? $v : $adder);
                } else {
                    $address[$v] = $adder;
                }
            }
            $head = static::editWwbSocketHead($address);
            foreach ($head as $val) {
                $headers[$val['key']] = $val['val'];
            }
        }
        //选择头部信息
        if (!empty($header = $this->socket_config['header'])) {
            $head = static::editWwbSocketHead($_SERVER);
            if (is_array($header)) {
                foreach ($header as $v) {
                    if (!empty($val = ($head[strtolower($v)] ?? []))) {
                        $headers[$val['key']] = $val['val'];
                    }
                }
            } else {
                foreach ($head as $val) {
                    if (!empty($val['http'])) {
                        $headers[$val['key']] = $val['val'];
                    }
                }
            }
        }
        //设置来源
        if (!empty($origin = $this->socket_config['origin'])) {
            $http_origin = $_SERVER['HTTP_ORIGIN'] ?? '';
            if (is_array($origin)) {
                $key = count($origin) > 1 ? rand(0, (count($origin) - 1)) : key($origin);
                $http_origin = $origin[$key] ?? $http_origin;
            }
            if (!empty($http_origin)) {
                $headers['Origin'] = $http_origin;
            }
        }
        //设置浏览器
        if (!empty($browser = $this->socket_config['browser'])) {
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            if (is_array($browser)) {
                $key = count($browser) > 1 ? rand(0, (count($browser) - 1)) : key($browser);
                $user_agent = $browser[$key] ?? $user_agent;
            }
            if (!empty($user_agent)) {
                $headers['User-Agent'] = $user_agent;
            }
        }
        $delete = ['Sec-Websocket-Key', 'Sec-Websocket-Version'];
        if ($encoding === true) {
            $delete[] = 'Accept-Encoding';
        }
        foreach ($delete as $k) {
            if (isset($headers[$k])) {
                unset($headers[$k]);
            }
        }
        $async = [
            'headers'       => array_merge($headers, $this->socket_config['custom_head']),
            'onConnect'     => function($con) {
                if (!empty($send = $this->socket_config['send'])) {
                    $this->async->send($send);
                }
            },
            'onMessage'     => function($con, $body) use ($callable) {
                //发送给客户端
                if (!empty($client = ($callable['client'] ?? '')) && is_callable($client)) {
                    $client($body, $this->tcp, $this);
                } else {
                    $this->tcp->send($body);
                }
            },
            'onBufferFull'  => function() {
                $this->tcp->pauseRecv();
            },
            'onBufferDrain' => function() {
                $this->tcp->resumeRecv();
            },
            'onError'       => function($con, $code, $msg) {
                print_r(['type' => 'tcp_error', 'code' => $code, 'msg' => $msg]);
                $this->tcp->close();
            },
            'onClose'       => function() {
                $this->tcp->close();
            }
        ];
        $tcp = [
            'onMessage'     => function($con, $body) use ($callable) {
                //发送给目标
                if (!empty($remote = ($callable['remote'] ?? '')) && is_callable($remote)) {
                    $remote($body, $this->async, $this);
                } else {
                    $this->async->send($body);
                }
            },
            'onBufferFull'  => function() {
                $this->async->pauseRecv();
            },
            'onBufferDrain' => function() {
                $this->async->resumeRecv();
            },
            'onError'       => function($con, $code, $msg) {
                print_r(['type' => 'tcp_error', 'code' => $code, 'msg' => $msg]);
                $this->async->close();
            },
            'onClose'       => function() {
                $this->async->close();
            }
        ];
        $onArray = [
            'async' => array_merge($async, $OnAsync),
            'tcp'   => array_merge($tcp, $OnTcp),
        ];
        foreach ($onArray as $key => $val) {
            foreach ($val as $k => $v) {
                if (!empty($v)) {
                    $this->$key->$k = $v;
                }
            }
        }
        $this->async->connect();
        return $this;
    }

    /**
     * @param string $remote
     * @param array  $context
     * @return $this
     */
    protected function setWebsocket(string $remote, array $context = []): static {
        $webArr = explode("://", $remote);
        $web = static::getWeb($remote);
        $this->schema = $web['schema'];//目标协议
        $this->domain = $web['domain'];//目标域名
        $this->path = $web['path'];    //目标路径
        $this->async = new AsyncTcpConnection($this->schema . '://' . trim($this->domain, '/' . $this->path), array_merge([
            'ssl' => [
                'verify_peer' => false,
            ]
        ], $context));
        $this->async->transport = $this->schema == 'ws' ? 'tcp' : 'ssl';
        return $this;
    }

    /**
     * @param array $OnAsync  可选On方法,headers信息设置
     * @param array $OnTcp    可选On方法
     * @param bool  $encoding 发送数据给目标时是否删除禁止编码,true=禁止
     * @param array $callable ['remote'=>发送给目标callable($buffer,$tcp,$this), 'client'=>发送给客户callable($buffer,$tcp,$this)]
     * @return $this
     */
    protected function connectWebSite(array $OnAsync = [], array $OnTcp = [], bool $encoding = true, array $callable = []): static {
        $this->server = $_SERVER;
        $async = [
            'onConnect'     => function($con) use ($encoding, $callable) {
                //当连接建立成功时，发送http请求数据
                $buffer = $this->req->rawBuffer();
                if ($encoding == true) {
                    $buffer = $this->editWebSiteHead($buffer, ['Accept-Encoding' => null]);
                }
                $buffer = str_replace($this->host . (!empty($this->port) ? (":" . $this->port) : ""), $this->domain, $buffer);
                if (!empty($remote = ($callable['remote'] ?? '')) && is_callable($remote)) {
                    $remote($buffer, $this->async, $this);
                } else {
                    $this->async->send($buffer);
                }
            },
            'onMessage'     => function($con, $buffer) use ($callable) {
                //目标网站的数据发送给客户端
                if (!empty($client = ($callable['client'] ?? '')) && is_callable($client)) {
                    $client($buffer, $this->tcp, $this);
                } else {
                    $this->tcp->send($buffer, true);
                }
            },
            'onBufferFull'  => function($con) {
                $this->tcp->pauseRecv();
            },
            'onBufferDrain' => function($con) {
                $this->tcp->resumeRecv();
            },
            'onError'       => function($con, $code, $msg) {
                print_r(['type' => 'async_error', 'code' => $code, 'msg' => $msg]);
                $this->tcp->send(static::errWebSite());
            },
            'onClose'       => function($con) {
                $this->tcp->close();
            }
        ];
        $tcp = [
            'onBufferFull'  => function($con) {
                $this->async->pauseRecv();
            },
            'onBufferDrain' => function($con) {
                $this->async->resumeRecv();
            },
            'onError'       => function($con, $code, $msg) {
                print_r(['type' => 'tcp_error', 'code' => $code, 'msg' => $msg]);
                $this->async->close();
            },
            'onClose'       => function($con) {
                $this->async->close();
            }
        ];
        $onArray = [
            'async' => array_merge($async, $OnAsync),
            'tcp'   => array_merge($tcp, $OnTcp),
        ];
        foreach ($onArray as $key => $val) {
            foreach ($val as $k => $v) {
                if (!empty($v)) {
                    $this->$key->$k = $v;
                }
            }
        }
        $this->async->connect();
        return $this;
    }

    /**
     * 连接 网站 设置
     * @param string $webSite 目标域名
     * @param string $path    目标路径
     * @param array  $context AsyncTcpConnection 设置
     * @return $this
     */
    protected function setWebSite(string $webSite, string $path, array $context = []): static {
        $site = explode(':', $this->req->host());
        $this->host = trim(($site[0] ?? ''));//当前域名
        $this->port = trim(($site[1] ?? ''));//域名端口
        $webArr = explode('://', str_starts_with($webSite, 'http') ? $webSite : ('http://' . $webSite), 2);
        $this->schema = trim(($webArr[0] ?? ''));           //目标协议
        $this->domain = trim(trim(($webArr[1] ?? '')), '/');//目标域名
        $this->path = ltrim(trim($path), '/');              //目标路径
        $this->async = new AsyncTcpConnection('tcp://' . $this->domain . '/' . $this->path, array_merge([
            'ssl' => [
                'verify_peer' => false,
            ]
        ], $context));
        $this->async->transport = $this->schema == 'https' ? 'ssl' : 'tcp';
        return $this;
    }

    /**
     * 修改 websocket 头部信息
     * @param array $header 头部内容
     * @param array $array
     * @return array
     */
    protected static function editWwbSocketHead(array $header, array $array = []): array {
        foreach ($header as $k => $v) {
            if (!empty($k) && !empty($v)) {
                $key = str_replace("_", "-", $k);
                $key = strtolower($key);
                $http = false;
                if (str_starts_with($key, 'http-')) {
                    $http = true;
                    $key = substr($key, strlen('http-'));
                }
                $arr = explode('-', $key);
                $keys = '';
                foreach ($arr as $x) {
                    $keys .= '-' . ucfirst($x);
                }
                $array[$key] = ['name' => $k, 'http' => $http, 'key' => trim($keys, '-'), 'val' => $v];

            }
        }
        return $array;
    }

    /**
     * 修改网站头部信息
     * @param string $header 头部内容
     * @param array  $edit   要修改的头部
     * @param bool   $type
     * @return string
     */
    public static function editWebSiteHead(string $header, array $edit = [], bool $type = false): string {
        if (!empty($edit)) {
            $arr = explode("\r\n", trim($header, "\r\n"));
            foreach ($arr as $key => $val) {
                $val = trim(strtolower($val));
                foreach ($edit as $k => $v) {
                    $k = trim(strtolower($k));
                    if (str_starts_with($val, $k)) {
                        $type = true;
                        if ($v !== null) {
                            $arr[$key] = $v;
                        } else {
                            unset($arr[$key]);
                        }
                    }
                }
            }
            if ($type === true) {
                $header = trim(join("\r\n", array_values($arr)), "\r\n") . "\r\n\r\n";
            }
        }
        return $header;
    }

    /**
     * @param string $header
     * @param array  $array
     * @return array
     */
    public static function headToArr(string $header, array $array = []): array {
        if (!empty($header)) {
            $arr = explode("\r\n", trim($header, "\r\n"));
            if (!empty($arr)) {
                foreach ($arr as $v) {
                    $position = strpos($v, ":");
                    if (!empty($position)) {
                        $key = trim(substr($v, 0, $position));
                        $val = trim(substr($v, $position + 1));
                        $array[(strtolower(str_replace("_", "-", $key)))] = ['key' => $key, 'val' => $val, 'head' => $v];
                    }
                }
            }
        }
        return $array;
    }

    /**
     *
     */
    protected static function errWebSite($boby = '') {
        $html = '<html><head><title>404 Not Found</title></head><body><center><h1>404 Not Found</h1></center><hr>' . $boby . '</body></html>';
        $res = new Response(404, [], $html);
        return $res;
    }

    /**
     * @param TcpConnection $tcp
     * @param Request|null  $req
     */
    public function __construct(TcpConnection $tcp, Request|null $req = null) {
        $this->tcp = $tcp;
        if (!empty($req)) {
            $this->req = $req;
        }
    }

    /**
     * @param $html
     * @return mixed
     */
    public static function getHtmlLocation($html): mixed {
        preg_match('/content=[\'"][0-9]*;?url=([^\'"]+)[\'"]/i', $html, $matches);
        return $matches[1] ?? '';
    }


    /**
     * @param $uri
     * @return array
     */
    public static function getWeb($uri): array {
        $webArr = explode('://', $uri);
        if (!empty($schema = ($webArr[0] ?? '')) && !empty($uri = ($webArr[1] ?? ''))) {
            $routing = explode('/', trim($uri));
            $domain = trim($routing[key($routing)]);
            $path = "/" . (count($routing) > 1 ? join('/', array_slice($routing, 1)) : "");
            $website = $schema . "://" . $domain;
        }
        return ['schema' => $schema ?? '', 'domain' => $domain ?? '', 'path' => $path ?? '', 'web' => $website ?? ''];
    }
}