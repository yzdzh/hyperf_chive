<?php
/**
 * Class CommonDao
 * 作者: su
 * 时间: 2021/5/29 10:58
 * 备注: 通用Dao方法
 */

namespace Chive\Dao;

use Hyperf\DbConnection\Db;

class CommonDao
{
	/**
	 * 获取所有表名
	 * @param $dbName
	 * @return array[\stdClass] Array(
	 *	[0] => stdClass Object(
	 *        [table_name] => category
	 *        [table_comment] => 类目表
	 *    )
	 * )
	 */
	public static function getAllTableName($dbName)
	{
		$sql = "SELECT table_name as table_name,table_comment as table_comment FROM information_schema.TABLES WHERE table_schema = '" . $dbName . "' AND (table_type = 'base table' OR table_type = 'BASE TABLE')";
        return Db::select($sql);
	}

	/**
	 * 获取table字段信息
	 * @param $dbName
	 * @param $tableName
	 * @return array
	 */
	public static function getTableColumn($dbName, $tableName)
	{
		$sql = "SELECT `column_name` as column_name, `data_type` as data_type , `column_comment` as column_comment FROM information_schema. COLUMNS WHERE `table_schema` = '" . $dbName . "' AND `table_name` = '{$tableName}' ORDER BY ORDINAL_POSITION;";
		return Db::select($sql);
	}

	/**
	 * 获取结构表最后一行数据（主要把数据拿出来做演示用）
	 * @param $dbName
	 * @param $tableName
	 * @return array
	 */
	public static function getLastOne($dbName, $tableName)
	{
		$sql = "select * from `{$dbName}`.`{$tableName}` ORDER BY id desc LIMIT 1";
		return Db::select($sql);
	}
}