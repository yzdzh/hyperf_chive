<?php
/**
 * Class ${NAME}
 * 作者: su
 * 时间: 2021/4/19 14:40
 * 备注: chive配置
 */

return [
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
	// 生成路由忽略方法，填方法名
	'route_ignore'                => [],


	/** ------------------------- 自动生成资源目录 ------------------------- */
	// 在启动前自动创建资源目录，如public/file目录
	'create_resource_path_enable' => env('RESOURCE_PATH_ENABLE', true),
	// 需要生成的目录，一行一个目录
	'resource_paths'              => [
		'public/',
		'public/images',
		'public/image',
		'public/excel',
		'public/file',
	],
	// 资源目录权限
	'resource_path_auth'          => 0644,

	/** ------------------------- 自动生成swagger.json ------------------------- */
	// 生成swagger文件
	'create_swagger_enable'       => env('SWAGGER_ENABLE', false),
	// swagger 的基础配置
	'swagger'                     => [
		'swagger' => '2.0',
		'info'    => [
			'description' => 'hyperf swagger api desc',
			'version'     => '1.0.0',
			'title'       => 'HYPERF API DOC',
		],
		'host'    => '0.0.0.0:9501',
		'schemes' => ['http'],
	],
	// 输出模板
	'templates'                   => [
		// 默认返回格式
		'success' => [
			\Chive\Helper\ErrorHelper::RET_CODE  => \Chive\Helper\ErrorHelper::SUCCESS_CODE,
			\Chive\Helper\ErrorHelper::RET_MSG   => \Chive\Helper\ErrorHelper::STR_SUCCESS,
			\Chive\Helper\ErrorHelper::RET_DATA  => '{template}',
			\Chive\Helper\ErrorHelper::RET_TOTAL => 1,
		],
	],
	'swagger_output_path'         => 'runtime/swagger/',
	// 启动swagger UI
	'start_swagger_enable'        => env('START_SWAGGER_ENABLE', false),
	// swagger页面端口
	'start_swagger_port'          => env('START_SWAGGER_PORT', 9999),
	// 默认按http服务生成文件
	'swagger_server'              => 'http',

	/** ------------------------- 将生成的swagger上传到yapi文档中 ------------------------- */
	'yapi'                        => [
		'domain_name' => env('YAPI_DOMAIN_NAME'), //如:https://api.baidu.com
		'token'       => env('YAPI_TOKEN'),// 项目token
		'project_id'  => env('YAPI_PROJECT_ID'),// 项目id
		'mode'        => env('YAPI_MODE', 'merge'), //数据同步方式 normal"(普通模式) , "good"(智能合并), "merge"(完全覆盖) 三种模式
	],

];