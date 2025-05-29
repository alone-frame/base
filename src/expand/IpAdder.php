<?php

namespace AloneFrame\base\expand;

class IpAdder{
    private static string $BasePath = __DIR__ . "/../../file/qqwry.dat";
    private int           $FirstIp;
    private int           $TotalIp;
    private int           $LastIp;
    private               $Fp;

    /**
     * 通过ip获取地址
     * @param string $ip
     * @return string
     */
    public static function getAdder(string $ip = ''): string {
        if (preg_match("/^[\d]+\.[\d]+\.[\d]+\.[\d]+$/isU", $ip)) {
            try {
                $local = (new self())->getLocal($ip);
                $area = trim(iconv("gb2312", "utf-8//IGNORE", $local['area']));
                $operators = trim(iconv("gb2312", "utf-8//IGNORE", $local['operators']));
                $area = $area == "CZ88.NET" ? "unknown" : $area;
                $operators = $operators == "CZ88.NET" ? "" : "-" . $operators;
                return str_replace("CZ88.NET", '', $area . $operators);
            } catch (\Throwable $e) {
                return 'Base Error(' . $ip . ')';
            }
        }
        return 'IP Error';
    }

    /**
     * 更新数据库
     */
    public static function update(): void {
        $copyWrite = file_get_contents("http://update.cz88.net/ip/copywrite.rar");
        $qqWry = file_get_contents("http://update.cz88.net/ip/qqwry.rar");
        $key = unpack("V6", $copyWrite)[6];
        for ($i = 0; $i < 0x200; $i++) {
            $key *= 0x805;
            $key++;
            $key = $key & 0xFF;
            $qqWry[$i] = chr(ord($qqWry[$i]) ^ $key);
        }
        $qqWry = gzuncompress($qqWry);
        $fp = fopen(self::$BasePath, "wb");
        if ($fp) {
            fwrite($fp, $qqWry);
            fclose($fp);
        }
    }

    public function __construct() {
        if (($this->Fp = @fopen(self::$BasePath, "rb")) !== false) {
            $this->FirstIp = $this->getLong();
            $this->LastIp = $this->getLong();
            $this->TotalIp = ($this->LastIp - $this->FirstIp) / 7;
        }

    }

    private function getLong() {
        $result = unpack("Vlong", fread($this->Fp, 4));
        return $result["long"];
    }

    private function getLongs() {
        $result = unpack("Vlong", fread($this->Fp, 3) . chr(0));
        return $result["long"];
    }

    private function packIp($ip): string {
        return pack("N", intval(ip2long($ip)));
    }

    private function getString($data = "") {
        $char = fread($this->Fp, 1);
        while (ord($char) > 0) {
            $data .= $char;
            $char = fread($this->Fp, 1);
        }
        return $data;
    }

    private function getArea() {
        $byte = fread($this->Fp, 1);
        switch (ord($byte)) {
            case 0:
                $operators = "";
                break;
            case 1:
            case 2:
                fseek($this->Fp, $this->getLongs());
                $operators = $this->getString();
                break;
            default:
                $operators = $this->getString($byte);
                break;
        }
        return $operators;
    }

    private function getLocal($ip): ?array {
        if (!$this->Fp) {
            return null;
        }
        $local["ip"] = $ip;
        $ip = $this->packIp($local["ip"]);
        $l = 0;
        $u = $this->TotalIp;
        $findIp = $this->LastIp;
        while ($l <= $u) {
            $i = floor(($l + $u) / 2);
            fseek($this->Fp, $this->FirstIp + $i * 7);
            $start_ip = strrev(fread($this->Fp, 4));
            if ($ip < $start_ip) {
                $u = $i - 1;
            } else {
                fseek($this->Fp, $this->getLongs());
                $end_ip = strrev(fread($this->Fp, 4));
                if ($ip > $end_ip) {
                    $l = $i + 1;
                } else {
                    $findIp = $this->FirstIp + $i * 7;
                    break;
                }
            }
        }
        fseek($this->Fp, $findIp);
        $local["start_ip"] = long2ip($this->getLong());
        $offset = $this->getLongs();
        fseek($this->Fp, $offset);
        $local["end_ip"] = long2ip($this->getLong());
        $byte = fread($this->Fp, 1);
        switch (ord($byte)) {
            case 1:
                $countryOffset = $this->getLongs();
                fseek($this->Fp, $countryOffset);
                $byte = fread($this->Fp, 1);
                switch (ord($byte)) {
                    case 2:
                        fseek($this->Fp, $this->getLongs());
                        $local["area"] = $this->getString();
                        fseek($this->Fp, $countryOffset + 4);
                        break;
                    default:
                        $local["area"] = $this->getString($byte);
                        break;
                }
                break;
            case 2:
                fseek($this->Fp, $this->getLongs());
                $local["area"] = $this->getString();
                fseek($this->Fp, $offset + 8);
                break;
            default:
                $local["area"] = $this->getString($byte);
                break;
        }
        $local["operators"] = $this->getArea();
        if ($this->Fp) {
            @fclose($this->Fp);
        }
        return $local;
    }
}
