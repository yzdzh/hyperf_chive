<?php
/**
 * Class SwaggerRespService
 * 作者: su
 * 时间: 2021/5/31 9:33
 * 备注: 生成swaggerResp
 */

namespace Chive\Chive;


use Chive\Dao\CommonDao;

class SwaggerRespService
{
	public static function decodeTables($dbName)
	{
		$tableNames = CommonDao::getAllTableName($dbName);
		$list       = [];        // 记录各个表结构
		/** @var \stdClass $obj */
		foreach ($tableNames as $obj) {
			$tableName    = $obj->table_name;
			$tableComment = $obj->table_comment;

			$list[$tableName] = self::decodeTable($dbName, $tableName, $tableComment);
		}
		return $list;
	}

	/**
	 * 生成单个表的model注解
	 * @param string $dbName       数据库名
	 * @param string $tableName    表名
	 * @param string $tableComment 表备注
	 * @return string
	 */
	public static function decodeTable($dbName, $tableName, $tableComment = '')
	{
		// 数据表结构
		$list = CommonDao::getTableColumn($dbName, $tableName);
		// 获取一行数据，做演示数据
		/** @var \stdClass $data */
		$data = CommonDao::getLastOne($dbName, $tableName);
		if (!empty($data)) {
			$data = reset($data);
		}

		$properties = [];
		/** @var \stdClass $data */
		foreach ($list as $key => $item) {
			$dKey = $item->column_name;

			// 备注字段，过滤掉“|”字符，防止和定义的冲突
			$description = $item->column_comment;
			$description = str_replace("||", "or", $description);
			$description = str_replace("|", "or", $description);
			$description = explode(PHP_EOL, $description)[0];        // 过滤多行，只保留第一行
			$description = trim($description);
			if (substr($dKey, -3) == '_at') {
				$description .= '[格式:Y/m/d H:i:s]';
			}
			if ($dKey == 'id') {
				$description = $tableComment . '表id';
			}

			// 设置默认提示参数值
			// $value = substr($description, 0, 30);    // 默认截取30个注解作为value
			$value = "";
			if (!empty($data)) {
				$value = $data->{$dKey};
			} else {
				$intType = ['int', 'tinyint', 'bigint', 'smallint', 'decimal'];
				if (in_array($item->data_type, $intType)) {
					$value = 1;
				}
			}
			if (substr($dKey, -3) == '_at') {
				$value = date('Y/m/d H:i:s', time());
			}

			$properties[$dKey] = [
				'key'   => $dKey,
				'name'  => $description,
				'value' => $value,
			];

			// key以x_id结尾，默认加上x_name字段
			if (substr($dKey, -3) == '_id') {
				$_name              = substr($dKey, 0, strlen($dKey) - 3) . '_name';
				$properties[$_name] = [
					'key'   => $_name,
					'name'  => $description . '[对应名称]',
					'value' => $description . '名称',
				];
			}
		}

		$_tableName = str_replace("_", " ", str_replace("-", " ", $tableName));
		$_tableName = str_replace(" ", "", ucwords($_tableName));
		$str = '/**' . PHP_EOL;
		$str .= ' * ' . '@ApiDefinitions({' . PHP_EOL;
		$str .= ' * ' . "\t" . '@ApiDefinition(name="' . $_tableName . 'Resp", properties={' . PHP_EOL;
		foreach ($properties as $property) {
			$str .= ' * ' . "\t\t" . '"' . $property['key'] . '|' . $property['name'] . '": ' .
					(is_numeric($property['value']) ? $property['value'] : '"' . $property['value'] . '"') . ',' . PHP_EOL;
		}
		$str .= ' * ' . "\t" . '})' . PHP_EOL;
		$str .= ' * ' . '})' . PHP_EOL;
		$str .= ' */ ';

		return $str;
	}

}