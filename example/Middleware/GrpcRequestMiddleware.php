<?php

declare(strict_types=1);

namespace App\Middleware;

use Chive\Constants\CommonConstants;
use Grpc\Request;
use Hyperf\Grpc\Parser;
use Hyperf\Utils\Context;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class GrpcRequestMiddleware implements MiddlewareInterface
{
	/**
	 * @var ContainerInterface
	 */
	protected $container;

	public function __construct(ContainerInterface $container)
	{
		$this->container = $container;
	}

	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{
		$stream = $request->getBody();
		/** @var Request $grpcRequest */
		$grpcRequest = Parser::deserializeMessage([Request::class, null], $stream->getContents());

		Context::set(CommonConstants::IS_GRPC_KEY, true);
		empty($grpcRequest->getRequestParam())
			? Context::set(CommonConstants::GRPC_REQUEST_KEY, [])
			: Context::set(CommonConstants::GRPC_REQUEST_KEY, json_decode($grpcRequest->getRequestParam(), true));
		empty($grpcRequest->getUserInfo())
			? Context::set(CommonConstants::GRPC_USER_INFO_KEY, [])
			: Context::set(CommonConstants::GRPC_USER_INFO_KEY, json_decode($grpcRequest->getUserInfo(), true));
		Context::set(CommonConstants::GRPC_MODULE_KEY, $grpcRequest->getModule());
		Context::set(CommonConstants::GRPC_CONTROLLER_KEY, $grpcRequest->getController());
		Context::set(CommonConstants::GRPC_METHOD_KEY, $grpcRequest->getMethod());
		empty($grpcRequest->getAttachment())
			? Context::set(CommonConstants::GRPC_ATTACHMENT_KEY, [])
			: Context::set(CommonConstants::GRPC_ATTACHMENT_KEY, json_decode(base64_decode($grpcRequest->getAttachment()), true));
		empty($grpcRequest->getFile())
			? Context::set(CommonConstants::GRPC_FILE_KEY, [])
			: Context::set(CommonConstants::GRPC_FILE_KEY, json_encode(base64_decode($grpcRequest->getFile()), true));

		return $handler->handle($request);
	}
}