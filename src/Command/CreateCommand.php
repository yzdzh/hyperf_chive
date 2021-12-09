<?php
/**
 * Class CreateCommand
 * 作者: su
 * 时间: 2021/5/31 18:20
 * 备注: 自动生成controller、service、dao文件
 * 版本：v2.0，按新格式生成
 */

namespace Chive\Command;

use Chive\Dao\CommonDao;
use Chive\Exception\BusinessException;
use Chive\Helper\ErrorHelper;
use Chive\Chive\SwaggerRespService;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Symfony\Component\Console\Input\InputOption;

/**
 * @Command
 */
class CreateCommand extends HyperfCommand
{
	public static $daoDir        = 'app/Dao';
	public static $requestDir    = 'app/Request';
	public static $serviceDir    = 'app/Service';
	public static $controllerDir = 'app/Controller';
	public static $routesPath    = 'config/routes.php';

	private static $help  = <<<EOF

  帮助信息:
  Usage: /path/to/php bin/hyperf.php chive:create [options] -- [args...]

  -c            [必填]创建文件类名
  -a            [必填]作者名
  -r            [必填]备注remark
  -m			[必填]数据表名
  -d            [可选]controller文件生成目录
  
  例：
  php bin/hyperf.php chive:create -c Test -r 类作用注释 -a 作者名 -m test 


EOF;
	private static $error = <<<EOF

  【必填项】
  -c            [必填]创建文件类名
  -a            [必填]作者名
  -r            [必填]备注remark
  -m 			[必填]数据表名

EOF;

	public function __construct()
	{
		parent::__construct('chive:create');
	}

	public function configure()
	{
		parent::configure();
		$this->setDescription('自动生成控制器、Service层、Dao层文件' . self::$help);
	}

	public function handle()
	{
		$opt = $this->input->getOptions();

		// 验证字段
		if (!isset($opt["controller"]) || !isset($opt["author"]) || !isset($opt["remark"]) || !isset($opt["model"])) {
			$this->line(self::$error, 'info');
			return false;
		}

		$author    = $opt['author'];
		$className = ucfirst($opt['controller']);
		$mark      = $opt['remark'];
		$date      = date('Y-m-d');
		$dir       = '';
		// controller变更生成目录
		$controllerDir = self::$controllerDir;
		if (isset($opt['dir'])) {
			$dir           = ucfirst($opt['dir']);
			$controllerDir = $controllerDir . '/' . $dir;
		}
		$model  = $opt['model'];
		$dbName = env('DB_DATABASE');
		if (empty($dbName)) {
			throw new BusinessException(ErrorHelper::FAIL_CODE, '未配置数据库信息');
		}

		list($anno, $definitionName) = self::createClassAnno($dbName, $model, $mark);
		if (empty($anno)) {
			$this->line('未找到数据表' . $model, 'info');
			return false;
		}
		self::createDao(self::$daoDir, $className, $author, $mark, $date);
		self::createController($controllerDir, $className, $author, $mark, $date, $anno, $definitionName, $dir);
		self::createService(self::$serviceDir, $className, $author, $mark, $date);
	}

	protected function getArguments()
	{
		$this->addOption('author', 'a', InputOption::VALUE_OPTIONAL, '[必填]作者名');
		$this->addOption('controller', 'c', InputOption::VALUE_OPTIONAL, '[必填]创建文件类名');
		$this->addOption('remark', 'r', InputOption::VALUE_OPTIONAL, '[必填]备注mark');
		$this->addOption('model', 'm', InputOption::VALUE_OPTIONAL, '[必填]数据表名');
		$this->addOption('dir', 'd', InputOption::VALUE_OPTIONAL, '[可选]controller文件生成目录');
	}


	public static function createDao($dir, $className, $author, $mark, $date)
	{
		$content = '<?php
/**
 * Class ' . $className . 'Dao
 * 作者: ' . $author . '
 * 时间: ' . $date . '
 * 备注: ' . $mark . '
 */

namespace App\Dao;

use App\Model\\' . $className . ';
use Chive\Model\Casts\TimeCasts;
use Chive\Dao\AbstractDao;

class ' . $className . 'Dao  extends AbstractDao
{
    // model类名
    protected $modelClass = ' . $className . '::class;

    // 转格式字段
    protected $withCasts = [
        \'created_at\' => TimeCasts::class,
        \'updated_at\' => TimeCasts::class,
    ];

    // 主键key
    protected $primaryKey = \'id\';
    

}
';
		self::mkdirs($dir);
		$fileName = $dir . '/' . $className . 'Dao.php';
		self::writeFile($fileName, $content);
	}


	/**
	 * 生成类注释
	 */
	public static function createClassAnno($dbName, $tableName, $mark)
	{
		$tableNames   = CommonDao::getAllTableName($dbName);
		$tableComment = '';
		$isFind       = false;
		/** @var \stdClass $obj */
		foreach ($tableNames as $obj) {
			if ($obj->table_name == $tableName) {
				$isFind       = true;
				$tableComment = $obj->table_comment;
				break;
			}
		}
		if (!$isFind) {
			return false;
		}
		$tableAnno = SwaggerRespService::decodeTable($dbName, $tableName, $tableComment);
		// 加入classRoute
		$tableAnnoArr = explode(PHP_EOL, $tableAnno);

		$str = '';
		$definitionName = '';
		foreach ($tableAnnoArr as $row) {
			if(trim($row) == '') {
				continue;
			}
			$str .= $row . PHP_EOL;
			if (strpos($row, '/**') !== false) {
				$str .= ' * @ClassRoute(tag="' . $mark . '", desc="' . $mark . '")' . PHP_EOL;
			}
			if(strpos($row, '@ApiDefinition(name="') !== false) {
				preg_match_all('/@ApiDefinition\(name="[a-zA-Z]*"/', $str, $matches);
				$tmpStr = $matches[0][0];
				$definitionName = substr($tmpStr, 21, strlen($tmpStr)-22);
			}
		}
		return [$str, $definitionName];
	}

	public static function createController($dir, $className, $author, $mark, $date, $anno, $definitionName, $lowerDir = '')
	{
		$content = '<?php
/**
 * Class ' . $className . 'Controller
 * 作者: ' . $author . '
 * 时间: ' . $date . '
 * 备注: ' . $mark . '
 */

namespace App\Controller';
		if (!empty($lowerDir)) {
			$content .= '\\' . $lowerDir;
		}
		$content .= ';
';
		if (!empty($lowerDir)) {
			$content .= '
use App\Controller\AbstractController;';
		}
		$content .= '
use App\Service\\' . $className . 'Service;
use Chive\Annotation\Form\FormData;
use Chive\Annotation\Route\ClassRoute;
use Chive\Annotation\Form\ApiResponse;
use Chive\Annotation\Form\ApiDefinitions;
use Chive\Annotation\Form\ApiDefinition;
use Chive\Annotation\Route\MethodRoute;
use Hyperf\Di\Annotation\Inject;
use Chive\Controller\AbstractController;

'.$anno.'class ' . $className . 'Controller extends AbstractController
{
    /**
     * @Inject()
     * @var ' . $className . 'Service
     */
    private $' . lcfirst($className) . 'Service;

    /**
	 * 获取列表
	 * @MethodRoute(tag="获取列表")
	 * @FormData(param="page_size|页大小", rule="required|integer", default="10")
	 * @FormData(param="page|页数", rule="required|integer", default="1")
	 * @ApiResponse(template="success", data={{"$ref":"'.$definitionName.'"}})
	 */
    public function getList()
    {
        $params = $this->request->all();
        $list   = $this->'.lcfirst($className).'Service->getList($params);
        return $this->success($list);
    }
    
    /**
     * 插入数据
	 * @MethodRoute(tag="插入数据")
	 * @ApiResponse(template="success")
     */
    public function add()
    {
        $params = $this->request->all();
        $this->'.lcfirst($className).'Service->add($params);
        return $this->success();
    }

    /**
	 * 获取单条详情
	 * @MethodRoute(tag="获取单条详情")
	 * @FormData(param="id|表ID", rule="required|integer", default="1")
	 * @ApiResponse(template="success", data={"$ref":"'.$definitionName.'"})
	 */
    public function getOne()
    {
        $params = $this->request->all();
        $list   = $this->'.lcfirst($className).'Service->getOne($params);
        return $this->success($list);
    }


}';

		self::mkdirs($dir);
		$fileName = $dir . '/' . $className . 'Controller.php';
		self::writeFile($fileName, $content);
	}

	public static function createService($dir, $className, $author, $mark, $date)
	{
		$content = '<?php
/**
 * Class ' . $className . 'Service
 * 作者: ' . $author . '
 * 时间: ' . $date . '
 * 备注: ' . $mark . '
 */

namespace App\Service;


use App\Dao\\' . $className . 'Dao;
use Hyperf\Di\Annotation\Inject;
use Chive\Service\AbstractService;

class ' . $className . 'Service extends AbstractService
{
    /**
     * @Inject()
     * @var ' . $className . 'Dao
     */
    protected $dao;
    
    
}';
		self::mkdirs($dir);
		$fileName = $dir . '/' . $className . 'Service.php';
		self::writeFile($fileName, $content);
	}

	/**
	 * 创建文件夹
	 * @param     $dir
	 * @param int $mode
	 * @return bool
	 */
	public static function mkdirs($dir, $mode = 0777)
	{
		if (is_dir($dir) || @mkdir($dir, $mode)) return TRUE;
		if (!self::mkdirs(dirname($dir), $mode)) return FALSE;
		return @mkdir($dir, $mode);
	}

	/**
	 * 写文件
	 * @param      $fileName
	 * @param      $content
	 */
	public static function writeFile($fileName, $content)
	{
		if (file_exists($fileName) == true) {
			echo "【{$fileName}】文件已存在，请手动删除" . PHP_EOL;
			return;
		}
		$res = file_put_contents($fileName, $content);
		if ($res) {
			echo "写入【{$fileName}】完成" . PHP_EOL;
		} else {
			echo "写入【{$fileName}】失败！！！" . PHP_EOL;
		}
	}
}