<?php
/**
 * Class CommonTest
 * 作者: su
 * 时间: 2021/10/29 16:56
 * 备注: 公共测试类
 */

namespace HyperfTest\Cases;

use Chive\Helper\ErrorHelper;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Testing\Client;
use HyperfTest\Cases\Header\AbstractHeader;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Class CommonTest.
 * @method get($uri, $data = [], $headers = [])
 * @method post($uri, $data = [], $headers = [])
 * @method json($uri, $data = [], $headers = [])
 * @method file($uri, $data = [], $headers = [])
 * @method request($method, $path, $options = [])
 */
class CommonTest extends TestCase
{
    // 定义每次跑测试清空数据库，请到 Config/DatabaseConfig::class 下配置

    /** @var bool 是否构建数据库环境 */
    static $buildMysqlEnv = false;

    /** @var LoggerInterface $logger */
    public $logger;

    /** @var Client */
    protected $client;

	/** @var AbstractHeader 抽象header信息，使用时必须实例化 */
	protected $header;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->logger = make(StdoutLoggerInterface::class);
        $this->client = make(Client::class);

        if (self::$buildMysqlEnv !== true) {
            // 构建mysql环境
            self::$buildMysqlEnv = true;
            $this->initTruncate();
        }
    }

    /**
     * 默认测试类，测试index访问
     */
    public function testIndex()
    {
        $res = $this->post('/');
        $this->assertIsArray($res);
    }

    /**
     * 初始化清空指定表
     */
    public function initTruncate()
    {
        foreach (DatabaseConfig::$initTruncateTables as $table) {
            $table::query()->truncate();
        }
        $this->assertTrue(true);
        $this->logger->info("初始化清空指定表：success");
    }

    public function __call($name, $arguments)
    {
        return $this->client->{$name}(...$arguments);
    }

    /**
     * 通用验证返回格式
     * @param $ret
     */
    public function assertFormat($ret)
    {
        $this->assertIsArray($ret, '返回信息不为数组');
        $this->assertTrue(isset($ret[ErrorHelper::RET_CODE]));
        $this->assertTrue(isset($ret[ErrorHelper::RET_DATA]));
        $this->assertTrue(isset($ret[ErrorHelper::RET_MSG]));
        $this->assertTrue(isset($ret[ErrorHelper::RET_TOTAL]));
    }

    /**
     * 验证正确下返回值
     * @param $ret
     */
    public function assertSuccess($ret)
    {
        $this->assertSame($ret[ErrorHelper::RET_CODE], ErrorHelper::SUCCESS_CODE, "返回errcode错误".json_encode($ret, JSON_UNESCAPED_UNICODE));
        $this->assertSame($ret[ErrorHelper::RET_MSG], ErrorHelper::STR_SUCCESS, "返回msg错误".json_encode($ret, JSON_UNESCAPED_UNICODE));
    }

    /**
     * 验证错误下返回值
     * @param $ret
     */
    public function assertFail($ret)
    {
        $this->assertNotSame($ret[ErrorHelper::RET_CODE], ErrorHelper::SUCCESS_CODE, "返回errcode错误".json_encode($ret, JSON_UNESCAPED_UNICODE));
        $this->assertNotSame($ret[ErrorHelper::RET_MSG], ErrorHelper::STR_SUCCESS, "返回msg错误".json_encode($ret, JSON_UNESCAPED_UNICODE));
    }

}