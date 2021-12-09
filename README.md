# chive
hyperf辅助工具

    1.根据规则生成路由文件（区别于@AutoController）
    2.根据注解生成Swagger api文档
    3.通过入参注解进行参数校验
    4.通过创建命令自动创建项目架构文件


----
## ==安装==
```
composer require chive/hyperf
```
引用了hyperf框架的组件
```
# 验证器组件
composer require hyperf/validation

1.使用过程中会输出日志，最好将example/config/logger.php配置复制到项目config/autoload/logger.php中。
2.使用过程中会抛出错误，建议将错误处理文件配置"\Chive\Exception\Handler\BusinessExceptionHandler::class"，加入config/autoload/exceptions.php配置文件中。
```


### 使用

#### 发布配置文件
发布配置文件config/autoload/chive.php

```bash
php bin/hyperf.php vendor:publish chive/hyperf

# hyperf/validation 的依赖发布
php bin/hyperf.php vendor:publish hyperf/translation
```

当启动程序时，chive执行流程：
```
-->程序启动
-->chive监听BootApplication::class服务启动事件 {
    -->(默认开)执行make(RoutesService::class)->main()，生成路由文件config/route.php
    -->(默认开)执行make(CreatePathService::class)->main()，创建资源目录
    -->(默认关)执行make(SwaggerService::class)->main()，生成总的swagger.json文件
    -->(默认关)执行make(SwaggerBranchService::class)->main()，按controller文件生成分的swagger，方便分模块开发修改Yapi文档
}
-->chive完成启动前执行，继续hyerf后面执行
```


----
## ==使用例子==
#### 1.创建数据表demo
```
CREATE TABLE `demo` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `type` int(255) unsigned NOT NULL DEFAULT '0' COMMENT 'demo_type类型，1安德鲁，2德玛西亚',
  `content` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '文本内容',
  `created_at` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updated_at` int(10) unsigned NOT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='例示表';
```
#### 2.配置数据库配置后，创建Model
```
php bin/hyperf.php gen:model --with-comments demo
```
创建model后，将"class Demo extends Model{}"继承的Model中添加，改变model默认存储时间的格式为时间戳。
```
protected $dateFormat = 'U';
```
或者：把Model改为继承Chive\Model\Model;类，例：
```
<?php

declare (strict_types=1);
namespace App\Model;

use Chive\Model\Model;  // ★修改这里，继承chive中的Model类
/**
 * @property int $id 
 * @property int $type demo_type类型，1安德鲁，2德玛西亚
 * @property string $content 文本内容
 * @property \Carbon\Carbon $created_at 创建时间
 * @property \Carbon\Carbon $updated_at 更新时间
 */
class Demo extends Model
{
    // xxxx
}
```
#### 3.自动创建控制层、Service层、Dao层
```
php bin/hyperf.php chive:create -c demo -a authorName -r Demo例示类 -m demo
```
完成基础文件的创建。
命令解析：
```
  -c    [必填]创建文件类名
  -a    [必填]作者名
  -r    [必填]备注remark
  -m    [必填]数据表名
  -d    [可选]controller文件生成目录
```

运行程序 php bin/hyperf.php start，自动调用生成route.php配置，即可访问程序。


----
## ==验证规则==
#### 实现原理：
验证类“Chive\Request\VerificationRequest”，通过继承“Hyperf\HttpServer\Request”，改写all()方法。在Controller调用$request->all()获取所有参数时，解析对应的类方法注解，对入参已经校验。



#### 调用方法：
验证通过方法注解去验证
```
/**
 * 获取列表
 * @MethodRoute(tag="获取列表")
 * @FormData(param="page_size|页大小", rule="required|integer|cb_isNum", default="10")
 * @FormData(param="page|页数", rule="required|integer", default="1")
 * @ApiResponse(template="success", data={{"$ref":"demoResp"}})
 */
public function getList()
{
    $params = $this->request->all();
    $list   = $this->demoService->getList($params);
    return $this->success($list);
}
```
@FormData作为入参注解，各个参数解析如下：

###### param：
```
如下两个方法解析为一致，分别代表：key参数名|name参数对应注释
@FormData(param="page_size|页大小") == @FormData(key="page_size", name="页大小")
```

###### rule：

规则列表参见 hyperf/validation 文档

###### 特殊规则：

cb_funcName：遇到“cb_”开头时，会自动到app/Request目录下查找跟Controller命名一样的Request文件，调用指定funcName()的函数。

例如：DemoController类，运行时会自动找到app\Request\DemoRequest.php文件，调用类文件中funcName()方法。判断验证规则正确和错误。

验证方法执行时，会创建匿名类继承Rule()类，然后在passes()方法中调用funcName()方法，完成入参验证。

```
<?php
namespace App\Request;

class DemoRequest
{
	/**
	 * 自定义的校验方法 rule 中 cb_isNum 方式调用
	 * @param string $attribute 变量名
	 * @param mixed $value 传入值
	 * @return bool|string
	 */
	public function isNum($attribute, $value)
	{
		if(is_numeric($value)) {
			return true;
		}
		// 错误返回报错信息
		return $attribute.' 必须为数值';
	}
}
```

----
## ==其他辅助命令==

#### 1. 根据数据表生成@ApiDefinitions()类注解

用于修改了数据表时需要更新@ApiDefinitions()注解，生成之后手动复制到指定地方。默认生成路径：runtime/swaggerResp/
```
php bin/hyperf.php chive:swaggerResp


生成结果：
runtime/swaggerResp/demo.txt
/**
 * @ApiDefinitions({
 * 	@ApiDefinition(name="demoResp", properties={
 * 		"id|表id": 1,
 * 		"type|demo_type类型，1安德鲁，2德玛西亚": 1,
 * 		"content|文本内容": "",
 * 		"created_at|创建时间[格式:Y/m/d H:i:s]": "2021/06/03 10:59:39",
 * 		"updated_at|更新时间[格式:Y/m/d H:i:s]": "2021/06/03 10:59:39",
 * 	})
 * })
 */ 
```

#### 2.生成路由文件

跟chive启动自动执行路由生成是同一个方法，执行命令：
```
php bin/hyperf.php chive:route
```

需要在Controller中添加类注解@ClassRoute()和方法注解@MethodRoute()。
具体填写参数看@ClassRoute()和@MethodRoute()代码中的备注信息。

路由生成配置
```
/** ------------------------- 路由模块配置 ------------------------- */
// 自动生成路由开关，true开，false关
'route_auto_create_enable'    => env('ROUTE_ENABLE', true),
// 路由生成文件地址，默认hyperf路由文件地址
'route_create_path'           => 'config/routes.php',
// 生成路由分隔符，如："/"生成/demo/index。"_"生成/demo_index路由。
'route_rule_split'            => '/',
// 默认路由方式，填"post","get","get,post"等
'route_method'                => 'post',
// 追加特殊路由规则
'route_extra'                 => "",
```
追加特殊路由规则：为需要些的特殊路由，在路由生成完之后追加的文件尾。


#### 3.生成swagger.json文件

同使用例子介绍。生成总的swagger.json和根据controller生成分的swagger文件。

默认生成目录：runtime/swagger/
```
php bin/hyperf.php chive:swagger
```
然后把swagger.json文件拖入Yapi文档中，生成api接口文档。


#### 4.根据数据表直接生成Yapi出参文档

(不推荐使用，v1.0功能)

根据数据表，生成对应的Yapi文档出参json格式，手动复制到Yapi文档中，即可完成api文档。

默认生成目录：runtime/yapiResp.txt
```
php bin/hyperf.php chive:yapiResp
```

#### 5.根据入参规则生成Yapi入参文档

(放弃使用，v1.0功能，在v2.0中放弃使用)

根据app\Request\目录中定义的入参验证规则生成Yapi入参json格式，手动复制到Yapi文档中，即可完成api文档。

默认生成目录：runtime/request/
```
php bin/hyperf.php chive:request
```

#### 6.启动 swagger UI 服务器

(功能开发中，暂不开放使用)

启动swagger UI服务，引入生成的swagger.json文件进行代码调试。
```
php bin/hyperf.php chive:ui
```

#### 7.自动部署api接口到远程服务器
需要配置config/autoload/chive.php配置文件的“yapi”配置：
```
domain_name: api文档域名，例https://api.baidu.com。
token: 具体Yapi文档中某个项目下的，【设置->token配置】中的token值，每个项目不一样。
project_id：项目ID，如网址https://api.baidu.com/project/150/setting，中的150即为项目ID。或者在具体Yapi文档中某个项目下的，【设置->项目配置】可看到项目ID。
```

支持平台：  
1）yapi  
2）postman（暂未实现）

功能：自动调用“3.生成swagger.json文件”命令之后，将swagger.json上传到api接口文档中。只支持根据控制器单个上传，不批量更新所有。
执行命令
```
bin/hyperf.php chive:yapi -c 控制器名[必填] -f 方法名[非必填]
```


★配置通过phpstorm快捷命令上传★


在phpstrom编辑器配置远程连接设置，【设置->工具->远程ssh外部工具->“＋”按钮】
如下图示例：
[![RrpiUe.png](https://z3.ax1x.com/2021/07/01/RrpiUe.png)](https://imgtu.com/i/RrpiUe)  
yapi运行命令参数为：
```
bin/hyperf.php chive:yapi -c $FileNameWithoutAllExtensions$ -f $SelectedText$
```

编辑完成之后，可添加快捷执行按钮【设置->快捷键->在“其他”选项中找到刚添加的快捷工具】给新增的工具添加快捷键，然后再对应controller文件中按快捷键，即完成api文档更新。


#### 8.生成单元测试框架和简单单元测试代码
1) 生成运行单元测试所需要的文件，例如，mysql、redis的测试代码。jwt获取token的方法
2) 生成简单的单元测试代码，该方法建立在第①点的基础上。生成后的代码自行修改成具体逻辑

##### 8.1.生成单元测试框架
生成单元测试框架运行命令参数为：
```
bin/hyperf.php chive:itest
```
运行后，生成如下文件：
```
├── bootstrap.php              
├── Cases
│   ├── CommonTest.php          // 单元测试基础类，后续都要基本本类
│   ├── Constant.php            // 统一存放测试中需要用到的变量
│   ├── Controller              // 单元测试代码存放目录
│   ├── DatabaseConfig.php      // 设置数据库配置，清空数据库（每次执行单元测试是需要清空的表等）
│   ├── ExampleTest.php
│   ├── Header                  // jwt设置header文件夹
│   │   ├── AbstractHeader.php  // 抽象header，实例化header需要继承
│   │   ├── HeaderFactory.php   // header工厂，生成单例对象
│   │   └── MyHeader.php        // 实例化header，调用登录接口，将登录信息存header中
│   └── PublicTest.php          // 通用测试mysql、redis连通性
└── HttpTestCase.php
```

##### 8.2.生成简单单元测试代码
生成简单单元测试代码运行命令参数为：
```
bin/hyperf.php chive:test -c AController -a author
如果带目录
bin/hyperf.php chive:test -c Dir/AController -a author

-c  控制器名，按哪一个控制器生成代码
-a  作者名，生成单元测试是带上创建人
```
注意事项：
1) 控制器中需要应用到上面的定义规则，定义ClassRoute,MethodRoute.
2) Param(FormData,Body)等类，必填参数填入default字段，才会自动生成默认值
3) 生成之后需要手动修改一下单元测试代码，不能全自动化



----
## ==其他==
在Chive\Helper\下封装了开发中写的助手类。

如：http、redis、时间处理、上传文件、excel导出、日志、二维码识别、mongodb等等




----
## ==更新日志==
- 2021-07-1 v2.1
  - 新增自动部署api接口到远程服务器
- 2021-06-02 v2.0
  - 增加配置文件config/autoload/chive.php统一管理配置
  - 引入注解自动生成swagger文件
  - 验参方式改为通过注解定义，改写Request类入参时过滤并验证参数，让业务代码更纯粹
  - 重写自动生成默认代码方法为chive:create命令，按新的注解方式生成
  - 引入swagger UI，调试中
- 2020-11-21 v1.0
  - 封装抽象Controller、Service、Dao层
  - 读取验参配置app/Response/文件夹生成Yapi入参文档
  - 通过中间件YapiMiddleware截取返回结果生成Yapi文档
  - 通过数据库表定义生成Yapi文档
  - 通过php create.php命令生成架构默认代码
  - 根据注解自动生成路由规则文件