<?php

namespace AloneFrame\base\expand;

class Acme{
    /**
     * acme转换各种环境
     * @param string $path acme 证书位置
     * @param string $save 保存路径
     * @return string|array,success=成功,array=失败信息
     */
    public static function exec(string $path, string $save): string|array {
        $path = rtrim($path, '/');
        $config['list'] = static::getDirFile($path);
        $config['save'] = rtrim($save, '/');
        foreach ($config['list'] as $k => $v) {
            $key = strtolower($k);
            $format = pathinfo($key, PATHINFO_EXTENSION);
            if ($key == 'fullchain.cer') {
                $config['full'] = $v;//完整的证书链，可更改后缀为pem。文件里一般有两段证书(也会有三张)，一张是你的域名证书，另一张是所依赖的证书链(可能会有两张证书链)
            } elseif ($key == 'ca.cer') {
                $config['chain'] = $v;//依赖的证书链，里面内容同时存在于fullchain.crt文件中。
            } elseif ($format == 'cer') {
                $config['domain'] = $v;//域名证书，里面内容同时存在于fullchain.crt文件中。
            } elseif ($format == 'key') {
                $config['private'] = $v;//证书私钥，可更改后缀为key。如果使用的是自己上传的CSR文件，将不包含该文件。
            } elseif ($format == 'conf') {
                $config['conf'] = $v;//配置
            }
        }
        $pass = substr(str_shuffle('abcdefghijklmnopqrstuvwxyz1234567890'), 0, 6);
        $save = $config['save'];
        $all = $save . '/All';
        $apache = $save . '/Apache';
        $bt = $save . '/Bt';
        $iis = $save . '/IIS';
        $nginx = $save . '/Nginx';
        $tomcat = $save . '/Tomcat';
        $pfxFile = $all . '/fullchain.pfx';
        $passFile = $all . '/password.txt';
        $data['all_fullchain'] = static::copyFile($config['full'], $all . '/fullchain.pem');     //完整的证书链
        $data['all_domain'] = static::copyFile($config['domain'], $all . '/domain.crt');         //域名证书
        $data['all_chain'] = static::copyFile($config['chain'], $all . '/chain.crt');            //依赖的证书链
        $data['all_private'] = static::copyFile($config['private'], $all . '/private.key');      //证书私钥
        $data['all_public'] = static::getPublic($config['private'], $all . '/public.pem');       //证书公钥
        $data['all_pfx'] = static::getPfx($config['full'], $config['private'], $pfxFile, $pass); //PFX类型证书
        $data['all_password'] = static::saveFile($passFile, $pass);                              //PFX密码
        $time = static::getTime($config['full']);
        $arr = array_slice(explode("DNS:", (@file_get_contents(($config['conf'] ?? '')))), 1);
        $domain = [];
        foreach ($arr as $v) {
            $domain[] = trim(trim($v), ',');
        }
        $data['help'] = static::saveFile($all . '/help.txt',
            static::helpTxt([
                'chain.crt'     => '依赖的证书链，里面内容同时存在于fullchain.pem文件中',
                'domain.crt'    => '域名证书，里面内容同时存在于fullchain.pem文件中',
                'fullchain.pem' => '完整的证书链，文件里一般有两段证书(也会有三张)，一张是你的域名证书，另一张是所依赖的证书链(可能会有两张证书链)',
                'fullchain.pfx' => 'PFX类型证书，一般IIS和Tomcat使用',
                'password.txt'  => 'PFX证书密码',
                'private.key'   => '证书私钥',
                'public.pem'    => '证书公钥',
                '证书密码'      => $pass,
                '证书域名'      => join(",", $domain),
                '证书时间'      => $time['top'] . " - " . $time['end']
            ], true));
        //apache
        $data['apache_domain'] = static::copyFile($config['domain'], $apache . '/domain.crt');   //域名证书
        $data['apache_private'] = static::copyFile($config['private'], $apache . '/private.key');//证书私钥
        $data['apache_chain'] = static::copyFile($config['chain'], $apache . '/chain.crt');      //依赖的证书链
        //bt
        $data['bt_fullchain'] = static::copyFile($config['full'], $bt . '/fullchain.pem');       //完整的证书链
        $data['bt_private'] = static::copyFile($config['private'], $bt . '/private.key');        //证书私钥
        //iis
        $data['iis_public'] = static::copyFile($pfxFile, $iis . '/fullchain.pfx');               //PFX类型证书iis
        $data['iis_password'] = static::copyFile($passFile, $iis . '/password.txt');             //PFX密码
        //nginx
        $data['nginx_fullchain'] = static::copyFile($config['full'], $nginx . '/fullchain.pem'); //完整的证书链
        $data['nginx_private'] = static::copyFile($config['private'], $nginx . '/private.key');  //证书私钥
        //tomcat
        $data['tomcat_public'] = static::copyFile($pfxFile, $tomcat . '/fullchain.pfx');         //PFX类型证书iis
        $data['tomcat_password'] = static::copyFile($passFile, $tomcat . '/password.txt');       //PFX密码
        $error = array_filter($data, function($v) {
            return $v == 0 || empty($v);
        });
        return ($error ?: 'success');
    }

    /**
     * acme使用dns申请命令
     * @param string $domain 域名列表
     * @param string $option 选项
     * @return string
     */
    public static function acmeDnsApply(string $domain, string $option = "--dns dns_gd"): string {
        return "acme.sh --issue {$domain} --force --debug 2" . (!empty($option) ? (" " . $option) : "");
    }

    /**
     * acme使用dns更新命令
     * @param string $domain 域名列表
     * @param string $option 选项
     * @return string
     */
    public static function acmeDnsUpdate(string $domain, string $option = "--dns dns_gd"): string {
        return "acme.sh --renew {$domain} --force --debug 2" . (!empty($option) ? (" " . $option) : "");
    }

    /**
     * acme申请域名命令
     * @param string $domain  //域名列表
     * @param string $path    //网站目录 绝对路径
     * @param string $sslFile //证书保存路径
     * @return string
     */
    public static function acmeApply(string $domain, string $path, string $sslFile): string {
        return "acme.sh --issue {$domain} -w {$path} --config-home {$sslFile} --force --debug 2";
    }

    /**
     * acme更新域名命令
     * @param string $domain //域名列表
     * @return string
     */
    public static function acmeUpdate(string $domain): string {
        return "acme.sh --renew {$domain} --force --debug 2";
    }

    /**
     * acme更新全部域名命令
     * @return string
     */
    public static function acmeUpdateAll(): string {
        return "acme.sh --renew-all --force --debug 2";
    }


    /**
     * 通过完整的证书链 获取 PFX类型证书，一般IIS和Tomcat使用
     * @param string $full    完整的证书链
     * @param string $private 证书私钥
     * @param string $pfx     保存路径和文件名
     * @param string $pass    密码
     * @return bool|int
     */
    public static function getPfx(string $full, string $private, string $pfx, string $pass = ''): bool|int {
        $res = openssl_pkcs12_export((@file_get_contents($full)), $data, (@file_get_contents($private)), $pass);
        return !empty($res) ? static::saveFile($pfx, $data) : 0;
    }

    /**
     * 通过证书私钥获取证书公钥
     * @param string $private 完整的证书链
     * @param string $public  保存路径和文件名
     * @return bool|int
     */
    public static function getPublic(string $private, string $public): bool|int {
        $res = openssl_pkey_get_details(openssl_pkey_get_private((@file_get_contents($private))));
        return static::saveFile($public, ($res['key'] ?? ''));
    }

    /**
     * 通过完整的证书链 获取 证书开始到结束时间
     * @param string $full 完整的证书链
     * @param bool   $type 是否返回全部信息
     * @return array
     */
    public static function getTime(string $full, bool $type = false): array {
        $resource = openssl_x509_read((@file_get_contents($full)));
        $res = openssl_x509_parse($resource);
        $data['top_time'] = $res['validFrom_time_t'] ?? 0;
        $data['end_time'] = $res['validTo_time_t'] ?? 0;
        $data['top'] = date('Y-m-d H:i:s', $data['top_time']);
        $data['end'] = date('Y-m-d H:i:s', $data['end_time']);
        if (!empty($type)) {
            $data['data'] = $res;
        }
        return $data;
    }

    /**
     * @param string $file
     * @param string $data
     * @return bool|int
     */
    public static function saveFile(string $file, string $data): bool|int {
        return @file_put_contents(static::mkDir($file), $data);
    }

    /**
     * 创造文件夹
     * @param string $filePath 文件名
     * @return string
     */
    public static function mkDir(string $filePath): string {
        $path = dirname($filePath);
        if (empty(is_dir($path))) {
            mkdir($path, 0777, true);
        }
        return $filePath;
    }

    /**
     * 获取目录下全部文件列表
     * @param       $path
     * @param array $result
     * @return mixed
     */
    public static function getDirFile($path, array $result = []): mixed {
        $files = scandir($path);
        foreach ($files as $file) {
            if ($file != '.' && $file != '..') {
                if (is_dir($path . '/' . $file)) {
                    $result = static::getDirFile($path . '/' . $file, $result);
                } else {
                    $result[$file] = $path . '/' . $file;
                }
            }
        }
        return $result;
    }

    /**
     * 复制文件
     * @param string $filePath    文件
     * @param string $newFilePath 复制到的位置
     * @return bool|int
     */
    public static function copyFile(string $filePath, string $newFilePath): bool|int {
        if (is_readable($filePath)) {
            static::mkDir($newFilePath);
            if (($handle1 = fopen($filePath, 'r')) && ($handle2 = fopen($newFilePath, 'w'))) {
                $type = stream_copy_to_stream($handle1, $handle2);
                fclose($handle1);
                fclose($handle2);
            }
        }
        clearstatcache();
        return ($type ?? false);
    }

    /**
     * @param string|array $data
     * @param string|bool  $info
     * @param bool         $type
     * @return mixed
     */
    private static function helpTxt(string|array $data, string|bool $info = '', bool $type = false): mixed {
        $way = function($name, $content) {
            return $name . "\r\n" . $content . "\r\n------------------------------------------------------------------\r\n\r\n";
        };
        $content = '';
        if (is_array($data)) {
            $i = 0;
            $type = is_bool($info) ? $info : $type;
            foreach ($data as $k => $v) {
                ++$i;
                $content .= $way((!empty($type) ? ($i . '.' . $k) : $k), $v);
            }
        } elseif (is_string($info)) {
            $content = $way($data, $info);
        }
        return $content;
    }
}