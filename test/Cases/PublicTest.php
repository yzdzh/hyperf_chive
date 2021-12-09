<?php


namespace HyperfTest\Cases;


use Chive\Helper\PrintHelper;
use Hyperf\DbConnection\Db;
use Hyperf\Utils\ApplicationContext;
use PHPUnit\Framework\TestCase;

/**
 * 测试公共模块，redis,数据库等
 * Class PublicTest
 * @package HyperfTest\Cases\Consumer
 */
class PublicTest extends TestCase
{

    /**
     * 测试redis连通
     */
    public function testRedis()
    {
        $key = 'unit_testing';
        $val = 'ceshi_' . time();
        $redis = ApplicationContext::getContainer()->get(\Redis::class);
        $redis->del($key);
        $this->assertEmpty($redis->get($key));
        $redis->set($key, $val, 60);
        $this->assertEquals($val, $redis->get($key), 'redis连接失败！');
        PrintHelper::p('连接redis【'.env('REDIS_HOST').':'.env('REDIS_DB').'】：success', true);
    }

    /**
     * 测试数据库连接
     */
    public function testMysql()
    {
        /** @var array $res */
        $res = Db::select("show tables");
        $this->assertNotEmpty($res);
        PrintHelper::p('连接mysql【'.env('DB_HOST').':'.env('DB_DATABASE').'】：success', true);
    }

}