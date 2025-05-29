<?php

namespace AloneFrame\base\plugin;

use AloneFrame\base\Frame;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

/**
 * https://github.com/chillerlan/php-qrcode
 * composer require chillerlan/php-qrcode
 */
class QrCodes{

    /**
     * 生成二维码
     * @param string $str
     * @param string $format
     * @param int    $version
     * @param int    $scale
     * @return mixed
     */
    public static function getBase(string $str, string $format = 'png', int $version = 5, int $scale = 5): mixed {
        $qro = new QROptions;
        $qro->version = $version;
        $qro->outputType = $format;
        $qro->addQuietzone = true;
        $qro->quietzoneSize = 1;
        $qro->scale = $scale;
        return (new QRCode($qro))->render($str);
    }

    /**
     * 生成二维码带logo
     * @param string $str
     * @param string $file
     * @param string $format
     * @param int    $version
     * @param int    $scale
     * @return mixed
     */
    public static function imgBase(string $str, string $file = '', string $format = 'png', int $version = 5, int $scale = 5): mixed {
        $base = self::getBase($str, $format, $version, $scale);
        if (!empty($file) && is_file($file) && str_contains($base, 'base64,')) {
            $base = self::qrcodeLogo($base, $file);
        }
        return $base;
    }

    /**
     * 二维码加logo
     * @param $base64
     * @param $file
     * @return string
     */
    public static function qrcodeLogo($base64, $file): string {
        $data = Frame::obCache(function() use ($base64, $file) {
            $arr = explode('base64,', $base64);
            $QR = imagecreatefromstring(base64_decode($arr[1]));
            $logo = imagecreatefromstring(file_get_contents($file));
            $QR_width = imagesx($QR);
            $logo_width = imagesx($logo);
            $logo_height = imagesy($logo);
            $logo_qr_width = round($QR_width / 5);
            $scale = $logo_width / $logo_qr_width;
            $logo_qr_height = $logo_height / $scale;
            $from_width = ($QR_width - $logo_qr_width) / 2;
            imagecopyresampled($QR, $logo, $from_width, $from_width, 0, 0, $logo_qr_width, $logo_qr_height, $logo_width, $logo_height);
            ImagePng($QR);
        });
        return 'data:image/png;base64,' . base64_encode($data);
    }
}