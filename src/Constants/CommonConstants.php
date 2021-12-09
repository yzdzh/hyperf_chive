<?php
/**
 * Class CommonConstants
 * 作者: su
 * 时间: 2021/7/7 16:55
 * 备注:
 */

namespace Chive\Constants;

class CommonConstants
{
	/** @var string 存grpc入参变量名 */
	const GRPC_REQUEST_KEY = 'grpc_request_key';
	/** @var string 是否grpc请求 */
	const IS_GRPC_KEY = 'is_grpc_key';
	/** @var string 模块名 */
	const GRPC_MODULE_KEY = 'grpc_module_key';
	/** @var string 控制器名 */
	const GRPC_CONTROLLER_KEY = 'grpc_controller_key';
	/** @var string 方法名 */
	const GRPC_METHOD_KEY = 'grpc_method_key';
	/** @var string 用户信息 */
	const GRPC_USER_INFO_KEY = 'grpc_user_info_key';
	/** @var string 多文件上传 */
	const GRPC_ATTACHMENT_KEY = 'grpc_attachment_key';
	/** @var string 单文件上传 */
	const GRPC_FILE_KEY = 'grpc_file_key';
	/** @var string jwt_token */
	const GRPC_ACCESS_TOKEN_KEY = 'grpc_access_token_key';
}