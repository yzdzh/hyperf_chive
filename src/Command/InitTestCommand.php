<?php
/**
 * Class InitTestCommand
 * 作者: su
 * 时间: 2021/11/3 14:12
 * 备注:
 */

namespace Chive\Command;


use Chive\Helper\DirHelper;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Command\Annotation\Command;

/**
 * @Command
 */
class InitTestCommand extends HyperfCommand
{

    public function __construct()
    {
        parent::__construct('chive:itest');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('构建初始的测试环境，生成所需要的类和文件');
    }

    public function handle()
    {
        DirHelper::mkdirs(BASE_PATH.'/test/Cases/');
        DirHelper::mkdirs(BASE_PATH.'/test/Cases/Header/');
        DirHelper::mkdirs(BASE_PATH.'/test/Cases/Controller/');

        copy(BASE_PATH . '/vendor/chive/hyperf/test/Cases/PublicTest.php',
            BASE_PATH . '/test/Cases/PublicTest.php');
        copy(BASE_PATH . '/vendor/chive/hyperf/test/Cases/DatabaseConfig.php',
            BASE_PATH . '/test/Cases/DatabaseConfig.php');
        copy(BASE_PATH . '/vendor/chive/hyperf/test/Cases/Constant.php',
            BASE_PATH . '/test/Cases/Constant.php');
        copy(BASE_PATH . '/vendor/chive/hyperf/test/Cases/CommonTest.php',
            BASE_PATH . '/test/Cases/CommonTest.php');

        copy(BASE_PATH . '/vendor/chive/hyperf/test/Cases/Header/AbstractHeader.php',
            BASE_PATH . '/test/Cases/Header/AbstractHeader.php');
        copy(BASE_PATH . '/vendor/chive/hyperf/test/Cases/Header/HeaderFactory.php',
            BASE_PATH . '/test/Cases/Header/HeaderFactory.php');
        copy(BASE_PATH . '/vendor/chive/hyperf/test/Cases/Header/MyHeader.php',
            BASE_PATH . '/test/Cases/Header/MyHeader.php');
    }
}