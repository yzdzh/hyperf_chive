<?php
/**
 * Class TestCommand
 * 作者: su
 * 时间: 2021/11/3 17:55
 * 备注: 生成单元测试代码
 */

namespace Chive\Command;


use Chive\Annotation\Form\ApiResponse;
use Chive\Annotation\Form\Param;
use Chive\Annotation\Route\ClassRoute;
use Chive\Annotation\Route\MethodRoute;
use Chive\Exception\BusinessException;
use Chive\Helper\AnnotationHelper;
use Chive\Helper\DirHelper;
use Chive\Helper\ErrorHelper;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Di\ReflectionManager;
use Hyperf\Utils\ApplicationContext;
use stdClass;
use Symfony\Component\Console\Input\InputOption;
use Hyperf\Command\Annotation\Command;

/**
 * 生成简单单元测试代码
 * @Command
 */
class TestCommand extends HyperfCommand
{
    public $server = 'http';

    public $config;

    public function __construct()
    {
        $container = ApplicationContext::getContainer();
        $this->config = $container->get(ConfigInterface::class);
        parent::__construct('chive:test');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('生成简单单元测试代码');
    }

    public function handle()
    {
        $opt = $this->input->getOptions();

        // 验证字段
        if (!isset($opt["controller"])) {
            $this->line("[必填]文件路径，controller目录下，例:front/AController", 'error');
            return false;
        }
        if (!isset($opt["author"])) {
            $this->line("[必填]作者名必填", 'error');
            return false;
        }

        $this->do($opt);
    }

    protected function getArguments()
    {
        $this->addOption('controller', 'c', InputOption::VALUE_OPTIONAL, '[必填]文件路径，controller目录下，例:front/AController');
        $this->addOption('author', 'a', InputOption::VALUE_OPTIONAL, '[必填]作者名');
    }

    public function do($params)
    {
        $pathArr = explode("/", $params['controller']);
        $author = $params['author'];

        $controllerName = '';                                 // 文件名
        $namespace = '';                                      // 命名空间
        $savePath = BASE_PATH . '/test/Cases/Controller/';    // 保存文件地址
        // 不等于1，带路径
        if (count($pathArr) != 1) {
            $cArr = $pathArr;
            $className = "App\\Controller\\" . implode("\\", $cArr);
            $controllerName = $cArr[count($cArr) - 1];
            unset($cArr[count($cArr) - 1]);
            $savePath .= implode("/", $cArr);

            foreach ($cArr as &$item) {
                $item = ucfirst($item);
            }
            $namespace = "\\" . implode("\\", $cArr);
            DirHelper::mkdirs($className);
        } else {
            $className = "App\\Controller\\" . implode("\\", $pathArr);
            $controllerName = $pathArr[count($pathArr) - 1];
            $namespace = '';
        }

        // 判断类存在
        if (!class_exists($className)) {
            throw new BusinessException(ErrorHelper::FAIL_CODE, $className . '类不存在！');
        }

        // 反射类
        $reflectClass = ReflectionManager::reflectClass($className);
        $methods = $reflectClass->getMethods();

        // 配置路由
        $route = '';                                          // 类路由地址
        $routeTag = '';                                       // 路由备注
        $classAnno = AnnotationHelper::classAnnotations($className);
        foreach ($classAnno as $anno) {
            /** @var ClassRoute $anno */
            if ($anno instanceof ClassRoute) {
                $routeTag = $anno->tag;
                if ($anno->prefix == '') {
                    $ccArr = $pathArr;
                    foreach ($ccArr as $k => $item) {
                        if (strpos($item, "Controller") !== false) {
                            $i = strpos($item, "Controller");
                            $ccArr[$k] = lcfirst(substr($item, 0, $i));
                        } else {
                            $ccArr[$k] = lcfirst($item);
                        }
                    }
                    $route = implode("/", $ccArr);
                } else {
                    $route = $anno->prefix;
                }
                break;
            }
        }
        $route = '/' . $route;

        $methodArr = [];
        /**
         * @var StdClass $method
         * [[1] => ReflectionMethod Object
         * (
         * [name] => getList
         * [class] => App\Controller\Background\ProductCategoryController
         * )]
         */
        foreach ($methods as $method) {
            $methodAnnotations = AnnotationHelper::methodMetadata($method->class, $method->name);

            $methodRoute = null;        // 方法路由
            $params = [];               // 入参注解
            foreach ($methodAnnotations as $annotation) {
                if ($annotation instanceof MethodRoute && $annotation->server == $this->server) {
                    $methodRoute = $annotation;
                } else if ($annotation instanceof Param) {
                    $params[] = $annotation;
                } else if ($annotation instanceof ApiResponse) {
                    $responses[] = $annotation;
                }
            }

            // 方法没有类路由，不生成测试代码
            if (empty($methodRoute)) {
                continue;
            }

            $methodArr[$method->name] = [
                'methodRoute' => $methodRoute,
                'params' => $params,
            ];
        }

        //        print_r($methodArr);

        $testName = $controllerName . "Test";
        if (strpos($controllerName, "Controller") !== false) {
            $i = strpos($controllerName, "Controller");
            $testName = substr($controllerName, 0, $i) . "Test";
        }
        $this->saveFile($savePath, $testName, $namespace, $route, $routeTag, $methodArr, $author);
    }

    /**
     * 输出测试代码文件
     * @param string $path 文件路径
     * @param string $fileName 保存文件名
     * @param string $namespce 命名空间
     * @param string $route 路由名
     * @param string $routeTag 路由备注
     * @param array  $methods
     * @param string $author 作者
     */
    public function saveFile($path, $fileName, $namespce, $route, $routeTag, $methods, $author = '')
    {
        DirHelper::mkdirs($path);

        $str = '<?php
/**
 * Class ' . $fileName . '
 * 作者: ' . $author . '
 * 时间: ' . date('Y-m-d H:i') . '
 * 备注: ' . $routeTag . ' 单元测试
 */

namespace HyperfTest\Cases\Controller' . $namespce . ';

use Chive\Helper\PrintHelper;
use HyperfTest\Cases\CommonTest;
use HyperfTest\Cases\Header\MyHeader;
use HyperfTest\Cases\Header\HeaderFactory;

/**
 * ' . $routeTag . ' 单元测试 
 */
class ' . $fileName . ' extends CommonTest
{
    public function __construct($name = null, array $data = [], $dataName = "")
    {
        parent::__construct($name, $data, $dataName);
        $this->header = HeaderFactory::create(MyHeader::class);
    }
';
        /** @var MethodRoute $method */
        foreach ($methods as $methodName => $method) {
            $methodRoute = $method['methodRoute'];
            $str .= '
    /**
     * ' . $methodRoute->tag . '
     */
    public function test' . ucfirst($methodName) . '()
    {
        PrintHelper::p("' . $routeTag . '->' . $methodRoute->tag . '：");
        $data = [' . PHP_EOL;
            /** @var Param $param */
            foreach ($method['params'] as $param) {
                if ($param->required == true) {
                    if (is_numeric($param->default)) {
                        $str .= self::space(4, 3) . '"' . $param->key . '"' . ' => ' . $param->default . ',' . PHP_EOL;
                    } else {
                        $str .= self::space(4, 3) . '"' . $param->key . '"' . ' => "' . $param->default . '"' . ',' . PHP_EOL;
                    }
                }
            }
            $str .= self::space(4, 2) . '];
        $ret = $this->post("' . $route . '/' . $methodName . '", $data, $this->header->getHeader());
        $this->assertFormat($ret);
        $this->assertSuccess($ret);
        PrintHelper::p("success", true);
    }
            ';
        }
        $str .= '
}';
        file_put_contents($path . "/" . $fileName . '.php', $str);
        $this->line("文件已生成，请查看：" . $path . "/" . $fileName . '.php');
    }

    /**
     * 输出空格字符串
     * @param int $num 输出空格个数，4个等于\t
     * @param int $mult 倍数
     * @return string
     */
    public static function space($num = 4, $mult = 1)
    {
        $str = '';
        for ($i = 0; $i < $num * $mult; $i++) {
            $str .= ' ';
        }
        return $str;
    }
}