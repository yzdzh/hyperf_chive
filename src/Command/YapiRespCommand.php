<?php
/**
 * Class YapiRespCommand
 * 作者: su
 * 时间: 2021/4/26 9:51
 * 备注:
 */

namespace Chive\Command;

use Chive\Dao\CommonDao;
use Chive\Exception\BusinessException;
use Chive\Helper\ErrorHelper;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\DbConnection\Db;
use Symfony\Component\Console\Input\InputOption;

/**
 * @Command
 */
class YapiRespCommand extends HyperfCommand
{
	/** @var string 文件生成地址 */
	protected $path = 'runtime/yapiResp.txt';

	public function __construct()
	{
		parent::__construct('chive:yapiResp');
	}

	public function configure()
	{
		parent::configure();
		$this->setDescription('根据数据库表生成Yapi接口文档返回格式');
	}

	public function handle()
	{
		$this->line('开始生成文件...', 'info');
		$dbName = env('DB_DATABASE');
		if (empty($dbName)) {
			throw new BusinessException(ErrorHelper::FAIL_CODE, '未配置数据库信息');
		}

		$path = $this->input->getOption('path');
		$this->decodeTable($dbName);
		$this->line('生成文件完成，路径【' . $path . '】', 'info');
	}

	protected function getArguments()
	{
		$this->addOption('path', 'p', InputOption::VALUE_OPTIONAL, '文件生成路径，默认【' . $this->path . '】.', $this->path);
	}

	/**
	 * 读取所有表信息，转换成Yapi格式
	 * @param $list
	 */
	public function decodeTable($dbName)
	{
		$list    = CommonDao::getAllTableName($dbName);
		$content = '';
		/** @var \stdClass $obj */
		foreach ($list as $obj) {
			$tableName    = $obj->table_name;
			$tableComment = $obj->table_comment;

			$list2 = CommonDao::getTableColumn($dbName, $tableName);

			$properties = [];
			/** @var \stdClass $data */
			foreach ($list2 as $key => $data) {
				$dKey        = $data->column_name;
				$type        = 'string';
				$description = $data->column_comment;
				switch ($data->data_type) {
					case 'int':
					case 'tinyint':
					case 'bigint':
					case 'smallint':
					case 'decimal':
						$type = 'number';
						break;
				}
				// key以_at结尾，默认为时间格式
				if (substr($dKey, -3) == '_at') {
					$type        = 'string';
					$description .= '[格式：Y/m/d H:i:s]';
				}
				$properties[$dKey] = [
					'type'        => $type,
					'description' => $description,
				];

				// key以x_id结尾，默认加上x_name字段
				if (substr($dKey, -3) == '_id') {
					$_name              = substr($dKey, 0, strlen($dKey) - 3) . '_name';
					$properties[$_name] = [
						'type'        => 'string',
						'description' => $description . '[对应名称]',
					];
				}
			}

			$toFormatArr  = [
				'$schema'    => 'http://json-schema.org/draft-04/schema#',
				'type'       => 'object',
				'properties' => [
					'code'  => ['type' => 'number'],
					'msg'   => ['type' => 'string'],
					'total' => ['type' => 'number'],
					'data'  => [
						'type'  => 'array',
						'items' => [
							'type'       => 'object',
							'properties' => $properties,
							'required'   => [],
						]
					],
				]
			];
			$toFormatArr2 = [
				'$schema'    => 'http://json-schema.org/draft-04/schema#',
				'type'       => 'object',
				'properties' => [
					'code'  => ['type' => 'number'],
					'msg'   => ['type' => 'string'],
					'total' => ['type' => 'number'],
					'data'  => [
						'type'       => 'object',
						'properties' => $properties,
						'required'   => [],
					],
				]
			];

			$content .= "【" . $tableName . "】" . "【" . $tableComment . "】二维数组\n" . json_encode($toFormatArr, JSON_UNESCAPED_UNICODE) . "\n";
			$content .= "【" . $tableName . "】" . "【" . $tableComment . "】一维数组\n" . json_encode($toFormatArr2, JSON_UNESCAPED_UNICODE) . "\n\n";

		}

		file_put_contents($this->path, $content);
	}

}