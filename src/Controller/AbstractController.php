<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace Chive\Controller;

use Chive\Constants\CommonConstants;
use Chive\Exception\BusinessException;
use Chive\Helper\ErrorHelper;
use Chive\Request\VerificationRequest;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Hyperf\Utils\Context;
use Psr\Container\ContainerInterface;

abstract class AbstractController
{
	/**
	 * @Inject
	 * @var ContainerInterface
	 */
	protected $container;

	/**
	 * @Inject
	 * @var VerificationRequest
	 */
	protected $request;

	/**
	 * @Inject
	 * @var ResponseInterface
	 */
	protected $response;

	/**
	 * 成功返回
	 * @param array $data
	 * @return \Psr\Http\Message\ResponseInterface
	 */
	protected function success($data = [])
	{
		if (!is_array($data)) {
			throw new BusinessException(ErrorHelper::FAIL_CODE, '返回格式必须是数组');
		}
		if (isset($data['data']) && isset($data['total'])) {
			$ret = ErrorHelper::returnFormat(ErrorHelper::SUCCESS_CODE, ErrorHelper::STR_SUCCESS, $data['data'], $data['total']);
		} else {
			$ret = ErrorHelper::returnFormat(ErrorHelper::SUCCESS_CODE, ErrorHelper::STR_SUCCESS, $data, (empty($data) ? 0 : 1));
		}
		// 如果是grpc,返回数组
		if (Context::get(CommonConstants::IS_GRPC_KEY, false)) {
			return $ret;
		}
		return $this->response->json($ret);
	}

	/**
	 * 失败返回
	 * @param int    $code
	 * @param string $message
	 * @param array  $data
	 * @return \Psr\Http\Message\ResponseInterface
	 */
	protected function fail($code = ErrorHelper::FAIL_CODE, $message = 'fail', $data = [])
	{
		if (!is_array($data)) {
			throw new BusinessException(ErrorHelper::FAIL_CODE, '返回格式必须是数组');
		}
		$ret = ErrorHelper::returnFormat($code, $message, $data, (empty($data) ? 0 : 1));
		if (Context::get(CommonConstants::IS_GRPC_KEY, false)) {
			return $ret;
		}
		return $this->response->json($ret);
	}
}
