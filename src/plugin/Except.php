<?php

namespace AloneFrame\base\plugin;

use AloneFrame\base\Frame;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

/**
 * https://github.com/PHPOffice/PhpSpreadsheet/
 * composer require phpoffice/phpspreadsheet
 */
class Except{
    /**
     * 获取文件内容
     * @param        $file
     * @param string $format //Xlsx|Xls|Xml|Ods|Slk|Gnumeric|Html|Csv
     * @return array
     * @throws Exception
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    public static function getArray($file, string $format = ''): array {
        if (empty($format)) {
            $format = Frame::getFileFormat($file);
        }
        return (IOFactory::createReader(ucfirst($format)))->setReadDataOnly(true)->load($file)->getSheet(0)->toArray();
    }

    /**
     * 数据生成表格,支持 Xls Xlsx Csv 三种
     * @param array  $row   设置字段和表头名称 格式: ['user'=>'会员帐号','time'=>'注册时间']
     * @param array  $array 表格数据,二维array 按row设置的字段设置数据  格式: [['user'=>'admin','time'=>'2023-01-01']]
     * @param string $file  保存位置带格式,绝对路径
     * @return bool
     */
    public static function save(array $row, array $array, string $file): bool {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $i = 0;
        $arr = [];
        foreach ($row as $k => $v) {
            ++$i;
            $arr[$k] = Frame::aZ($i);
            $sheet->setCellValue($arr[$k] . '1', $v);
        }
        $i = 1;
        foreach ($array as $v) {
            ++$i;
            foreach ($row as $k => $c) {
                $sheet->setCellValue($arr[$k] . $i, $v[$k]);
            }
        }
        $format = ucfirst(Frame::getFileFormat($file));
        if ($format == 'Xls') {
            $writer = new Xls($spreadsheet);
        } elseif ($format == 'Xlsx') {
            $writer = new Xlsx($spreadsheet);
        } else {
            $writer = new Csv($spreadsheet);
        }
        Frame::mkDir(dirname($file));
        $writer->save($file);
        return is_file($file);
    }
}