<?php

namespace AloneFrame\base\expand;

use AloneFrame\base\Frame;

class Apple {
    //默认参数
    private array $conf = [
        //APP名称
        'title'       => 'App',

        //APP图标(绝对路径)
        'icon'        => __DIR__ . '/../../file/apple.png',

        //APP网站
        'url'         => 'https://www.apple.com.cn/',

        //是否全屏显示
        'screen'      => true,

        //是否可移除
        'move'        => true,

        //版本号
        'version'     => '1',

        //唯一ID,为空随机
        'build'       => '',

        //描述(显示在APP描述文件里面),可使用%title%获取名称
        'description' => '请点击右上角的"安装",这将会把"%title%"添加到您的主屏上',

        //标签(显示在描述文件APP名称下方),可使用%title%获取名称
        'label'       => '%title%',

        //保存目录(绝对路径),为空不保存
        'save'        => __DIR__ . '/../../file/ssl',

        //证书签名设置
        'ssl'         => [
            //域名证书路径
            'domain'  => '',
            //域名私钥路径
            'private' => ''
        ]
    ];

    //设置参数
    public array $config = [];

    /**
     * 设置
     * @param array $config
     */
    public function __construct(array $config = []) {
        $this->config = array_merge($this->conf, $config);
    }

    /**
     * 执行
     * @param string $name 保存名称
     * @param bool   $exec 是否使用exec
     * @return string
     */
    public function exec(string $name = '', bool $exec = false): string {
        $this->config['name'] = ($name ?: Frame::getUnix()) . '.mobileconfig';
        $this->config['move'] = $this->config['move'] ? 'true' : 'false';
        $this->config['screen'] = $this->config['screen'] ? 'true' : 'false';
        $this->config['build'] = $this->config['build'] ?: Frame::getToken();
        $this->config['icon'] = base64_encode(@file_get_contents($this->config['icon']));
        $xml = $this->xmlString();
        $this->config['file'] = Frame::dirPath($this->config['save'], $this->config['name']);
        Frame::mkDir(dirname($this->config['file']));
        $save = @file_put_contents($this->config['file'], $xml);
        if ($save > 0 && !empty($domain = Frame::getArr($this->config, 'ssl.domain')) && !empty($private = Frame::getArr($this->config, 'ssl.private'))) {
            $this->config['ssl_file'] = Frame::dirPath($this->config['save'], '/cert/' . $this->config['name']);
            Frame::mkDir(dirname($this->config['ssl_file']));
            if (!empty($this->sslExec($domain, $private, $exec))) {
                $res = Frame::isFile($this->config['ssl_file']) ?: Frame::isFile($this->config['file']);
                $this->config = $this->conf;
                return $res;
            }
        }
        $file = Frame::isFile($this->config['file']);
        $this->config = $this->conf;
        return $file;
    }

    /**
     * APP名称
     * @param string $data
     * @return $this
     */
    public function title(string $data): static {
        $this->config['title'] = $data;
        return $this;
    }

    /**
     * APP图标(绝对路径)
     * @param string $data
     * @return $this
     */
    public function icon(string $data): static {
        $this->config['icon'] = $data;
        return $this;
    }

    /**
     * APP网站
     * @param string $data
     * @return $this
     */
    public function url(string $data): static {
        $this->config['url'] = $data;
        return $this;
    }

    /**
     * 是否全屏显示
     * @param bool $data
     * @return $this
     */
    public function screen(bool $data): static {
        $this->config['screen'] = $data;
        return $this;
    }

    /**
     * 是否可移除
     * @param bool $data
     * @return $this
     */
    public function move(bool $data): static {
        $this->config['move'] = $data;
        return $this;
    }

    /**版本号
     * @param int|string $data
     * @return $this
     */
    public function version(int|string $data): static {
        $this->config['version'] = $data;
        return $this;
    }

    /**
     * 唯一ID
     * @param string $data
     * @return $this
     */
    public function build(string $data): static {
        $this->config['build'] = $data;
        return $this;
    }

    /**
     * 证书签名设置
     * @param string $domain  域名证书路径
     * @param string $private 域名私钥路径
     * @return $this
     */
    public function ssl(string $domain, string $private): static {
        $this->config['ssl'] = ['domain' => $domain, 'private' => $private];
        return $this;
    }

    /**
     * 设置保存路径
     * @param string $data
     * @return $this
     */
    public function save(string $data): static {
        $this->config['save'] = $data;
        return $this;
    }

    /**
     * 签名
     * @param string $domain
     * @param string $private
     * @param bool   $exec
     * @return bool
     */
    private function sslExec(string $domain, string $private, bool $exec = false): bool {
        if ($exec) {
            exec("openssl x509 -enddate -noout -in $domain", $time);
            $last_data = isset($time[0]) ? date('Y-m-d', strtotime(ltrim(strstr($time[0], '='), '='))) : 0;
        } else {
            $resource = openssl_x509_read((@file_get_contents($domain)));
            $res = openssl_x509_parse($resource);
            $last_data = $res['validTo_time_t'] ?? 0;
        }
        if (time() < $last_data) {
            exec("openssl smime -sign -in {$this->config['file']} -out {$this->config['ssl_file']} -signer {$domain}  -inkey {$private} -certfile {$domain}  -outform der -nodetach");
        }
        return is_file($this->config['ssl_file']);
    }


    /**
     * xml内容
     * @return string
     */
    private function xmlString(): string {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">' . "\n";
        $xml .= '<plist version="1.0">';
        $xml .= "
    <dict>
        <key>PayloadContent</key>
        <array>
            <dict>
                <key>FullScreen</key>
                <" . $this->config["screen"] . "/>
                <key>Icon</key>
                <data>" . $this->config["icon"] . "</data>
                <key>IsRemovable</key>
                <" . $this->config["move"] . "/>
                <key>Label</key>
                <string>" . $this->config["title"] . "</string>
                <key>PayloadDescription</key>
                <string>Adds a Web Clip.</string>
                <key>PayloadDisplayName</key>
                <string>Web Clip</string>
                <key>PayloadIdentifier</key>
                <string>com.apple.webClip.Packer." . $this->config["build"] . "</string>
                <key>PayloadOrganization</key>
                <string>" . $this->config["title"] . "</string>
                <key>PayloadType</key>
                <string>com.apple.webClip.managed</string>
                <key>PayloadUUID</key>
                <string>" . $this->config["build"] . "</string>
                <key>PayloadVersion</key>
                <integer>" . $this->config["version"] . "</integer>
                <key>Precomposed</key>
                <true/>
                <key>URL</key>
                <string>" . $this->config["url"] . "</string>
            </dict>
        </array>
        <key>PayloadDescription</key>
        <string>" . Frame::tag($this->config["description"], ['title' => $this->config["title"]]) . "</string>
        <key>PayloadDisplayName</key>
        <string>" . Frame::tag($this->config["label"], ['title' => $this->config["title"]]) . "</string>
        <key>PayloadIdentifier</key>
        <string>com.apple.webClip.Packer." . $this->config["build"] . "</string>
        <key>PayloadOrganization</key>
        <string>" . Frame::tag($this->config["label"], ['title' => $this->config["title"]]) . "</string>
        <key>PayloadRemovalDisallowed</key>
        <false/>
        <key>PayloadType</key>
        <string>Configuration</string>
        <key>PayloadUUID</key>
        <string>" . $this->config["build"] . "</string>
        <key>PayloadVersion</key>
        <integer>" . $this->config["version"] . "</integer>
    </dict>";
        $xml .= "\n</plist>";
        return $xml;
    }
}