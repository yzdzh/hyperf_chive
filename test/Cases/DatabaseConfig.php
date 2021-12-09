<?php
/**
 * Class DatebaseConfig
 * 作者: su
 * 时间: 2021/11/2 11:12
 * 备注:
 */

namespace HyperfTest\Cases;

/**
 * 设置数据库配置，清空数据库
 */
class DatabaseConfig
{
    /** @var array 【初始化】需要清空的数据表 */
    static $initTruncateTables = [
        // Model::Class
    ];
    /** @var array 【每个用例】需要清空的数据表 */
    static $alwaysTruncateTables = [
        // Model::Class
    ];
}