<?php

namespace AloneFrame\base\plugin\safe;

trait Openssl {

    //调试私钥
    public static string $private = '-----BEGIN PRIVATE KEY-----
MIICeAIBADANBgkqhkiG9w0BAQEFAASCAmIwggJeAgEAAoGBAOVvpzd7f4p3cg3k
tQNyUnPDmJA9Sl0WOto9zk5W2a/hxvfWbi+Epv3AOzg3u67owS6dCIXh1ConQlnJ
krkqFDaIvJWZjlzC67VAglO2GF0aQ9eT6nFXxPUPFEOvzhlePTKsv0zhDHzC6p9V
iEQZukoH7D3XprVjxu8tZMKySviXAgMBAAECgYEAmy7M1CsU1ev6WclSYZVmjWRD
gXPjI1kQz2I0cyotLNgyApmnhz5a0JC/vUN9D03gtA2EoUFghm5tY1uCgPkkF4Kk
6EFHr84FxzMUzQgEPTNBEru2n2fAeJP902c+0b9HAuU735RESHqlGzxL5uYRu3pk
1UcKRj2E4CbdA2xgCdkCQQD5SbgEqkD/wdLAgYW6L+sZbmfs/9huKmKUjLsoqqUg
xcr9gjE/0htP6fAicHkuZnFqXvNohTrg+AxU4ZVZb46LAkEA650Zwj0dxJ8TOI6w
yocnbiP5V1wws5giE+x+gGowg1e7hE0MeYYf6vIYxN8MsaEHg/6dZX6Udd14dg+C
MHBrpQJBAI/4FC+ViA5tCOMmqm2Z6QP58Ek+hOcy0VYLZLeavd0MfiwkeX7rP9zK
NWYeYM38WfndtmOhthxhBPYshc1uEPsCQHXJfQYgvY/9IoPEudcVx/2E2HL28JXn
+SlSsk8KRyRyKJlUV2ctSSmQTBenllX6taIkGJWTuS5PQJhs2l3S5c0CQQCkZoHB
WiZY6WA2LbnzD7vKbxg6/cq/Q/bg94k5e44lyJMZ8DGoKuzBYOCtyBbaEiL9/2OT
3JeKtTg4ha0rAcFO
-----END PRIVATE KEY-----';
    //调试公钥
    public static string $public = '-----BEGIN PUBLIC KEY-----
MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDlb6c3e3+Kd3IN5LUDclJzw5iQ
PUpdFjraPc5OVtmv4cb31m4vhKb9wDs4N7uu6MEunQiF4dQqJ0JZyZK5KhQ2iLyV
mY5cwuu1QIJTthhdGkPXk+pxV8T1DxRDr84ZXj0yrL9M4Qx8wuqfVYhEGbpKB+w9
16a1Y8bvLWTCskr4lwIDAQAB
-----END PUBLIC KEY-----';

    /**
     * 方法列表
     * @return array
     */
    public static function show(): array {
        return openssl_get_cipher_methods();
    }

    /**
     * 通用加密
     * @param string $data
     * @param string $type //可通过show查看方式
     * @param string $key
     * @param string $iv
     * @return string
     */
    public static function opensslEncrypt(string $data, string $type, string $key, string $iv = ''): string {
        return openssl_encrypt($data, $type, $key, OPENSSL_RAW_DATA, $iv);
    }

    /**
     * 通用解密
     * @param string $data
     * @param string $type //可通过show查看方式
     * @param string $key
     * @param string $iv
     * @return string
     */
    public static function opensslDecrypt(string $data, string $type, string $key, string $iv = ''): string {
        return openssl_decrypt($data, $type, $key, OPENSSL_RAW_DATA, $iv);
    }

    /**
     * RSA私钥加密
     * @param string $data 内容
     * @param string $key  私钥
     * @param bool   $is   是否格式化私钥、默认不格式化
     * @param string $cert 公钥类型
     * @return string
     */
    public static function privateEncrypt(string $data, string $key, bool $is = false, string $cert = 'PRIVATE'): string {
        $key = !empty($is) ? "-----BEGIN {$cert} KEY-----\n" . chunk_split($key, 64) . "-----END {$cert} KEY-----" : $key;
        openssl_private_encrypt($data, $encrypted, openssl_pkey_get_private($key));
        return base64_encode($encrypted);
    }

    /**
     * RSA私钥解密
     * @param string $data 内容
     * @param string $key  私钥
     * @param bool   $is   是否格式化私钥、默认不格式化
     * @param string $cert 公钥类型
     * @return string
     */
    public static function privateDecrypt(string $data, string $key, bool $is = false, string $cert = 'PRIVATE'): string {
        $key = !empty($is) ? "-----BEGIN {$cert} KEY-----\n" . chunk_split($key, 64) . "-----END {$cert} KEY-----" : $key;
        openssl_private_decrypt(base64_decode($data), $content, openssl_pkey_get_private($key));
        return $content;
    }

    /**
     * RSA公钥加密
     * @param string $data 内容
     * @param string $key  公钥
     * @param bool   $is   是否格式化公钥、默认不格式化
     * @param string $cert 公钥类型
     * @return string
     */
    public static function publicEncrypt(string $data, string $key, bool $is = false, string $cert = 'PUBLIC'): string {
        $key = !empty($is) ? "-----BEGIN {$cert} KEY-----\n" . \chunk_split($key, 64) . "-----END {$cert} KEY-----" : $key;
        \openssl_public_encrypt($data, $encrypted, \openssl_pkey_get_public($key));
        return \base64_encode($encrypted);
    }

    /**
     * RSA公钥解密
     * @param string $data 内容
     * @param string $key  公钥
     * @param bool   $is   是否格式化公钥、默认不格式化
     * @param string $cert 公钥类型
     * @return string
     */
    public static function publicDecrypt(string $data, string $key, bool $is = false, string $cert = 'PUBLIC'): string {
        $key = !empty($is) ? "-----BEGIN {$cert} KEY-----\n" . chunk_split($key, 64) . "-----END {$cert} KEY-----" : $key;
        openssl_public_decrypt(base64_decode($data), $content, openssl_pkey_get_public($key));
        return $content;
    }

    /**
     * 通过pfx密钥文件读取公钥和私钥
     * @param string $pfxData 证书内容
     * @param string $pfxPass 证书密码
     * @return array
     */
    public static function getPfxKey(string $pfxData, string $pfxPass): array {
        openssl_pkcs12_read($pfxData, $cate, $pfxPass);
        $data['private'] = $cate['pkey'];
        $data['public'] = $cate['cert'];
        return $data;
    }

    /**
     * pfx密钥文件私钥加密
     * @param string $str 加密的数据
     * @param string $key 私钥
     * @return string
     */
    public static function pfxEncrypt(string $str, string $key): string {
        openssl_sign($str, $data, $key);
        return base64_encode($data);
    }

    /**
     * pfx密钥文件公钥验证加密数据
     * @param string $str  未加密的数据
     * @param string $data 加密的数据
     * @param string $key  公钥
     * @return int 验证成功返回1
     */
    public static function pfxVerify(string $str, string $data, string $key): int {
        return openssl_verify($str, base64_decode($data), $key);
    }

    /**
     * 生成公私密钥
     * @param int $bits //字节数  512 1024 2048  4096 等
     * @return array
     */
    public static function generateKey(int $bits = 1024): array {
        $config ['private_key_bits'] = $bits;
        $config ['config'] = __DIR__ . '/../../../file/openssl.cnf';
        $res = openssl_pkey_new($config);
        openssl_pkey_export($res, $private, null, $config);
        $public = openssl_pkey_get_details($res);
        $data['private'] = $private;
        $data['public'] = $public["key"];
        return $data;
    }

    /**
     * 生成证书
     * @param int    $bits  //字节数  512 1024 2048  4096 等
     * @param string $pass  //证书密码
     * @param int    $valid //有效时长 天数
     * @param array  $conf  //配置
     * @return array
     */
    public static function generateCertificate(int $bits = 1024, string $pass = '123456', int $valid = 6570, array $conf = []): array {
        $deploy['countryName'] = $conf ['a'] ?? 'CH';                  //所在国家名称
        $deploy['stateOrProvinceName'] = $conf ['b'] ?? 'State';       //所在省份名称
        $deploy['localityName'] = $conf ['c'] ?? 'Somewhere';          //所在城市名称
        $deploy['organizationName'] = $conf ['d'] ?? 'MySelf';         //注册人姓名
        $deploy['organizationalUnitName'] = $conf ['e'] ?? 'Whatever'; //组织名称
        $deploy['commonName'] = $conf ['f'] ?? 'mySelf';               //公共名称
        $deploy['emailAddress'] = $conf ['g'] ?? 'domain@domain.com';  //邮箱
        $config ['private_key_bits'] = $bits;
        $config ['config'] = __DIR__ . '/../../../file/openssl.cnf';
        $res = openssl_pkey_new($config);
        $csr = openssl_csr_sign(openssl_csr_new($deploy, $res, $config), null, $res, $valid, $config);
        openssl_x509_export($csr, $cer);
        openssl_pkcs12_export($csr, $pfx, $res, $pass);
        $data ['cer'] = $cer; // 生成证书
        $data ['pfx'] = $pfx; // 密钥文件
        return $data;
    }
}