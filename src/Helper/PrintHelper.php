<?php
/**
 * Class PHelper
 * 作者: su
 * 时间: 2021/11/2 14:40
 * 备注:
 */

namespace Chive\Helper;

/**
 * 打印助手类
 */
class PrintHelper
{
    // 日志打印开关
    static $showLog = true;

    public static function p($str, $enter = false)
    {
        self::$showLog && fwrite(STDOUT, $str . ($enter ? "\n" : ''));
    }

    /**
     * 输出红色提示信息
     * @param      $str
     * @param bool $enter
     * @return string
     */
    public static function pRed($str, $enter = false)
    {
        $str = "\033[31m" . '错误提示：' . $str . "\033[0m";
        self::p($str, $enter);
    }
}