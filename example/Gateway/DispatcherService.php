<?php

declare(strict_types=1);

namespace App\Gateway;

use App\Constants\Common;
use App\Grpc\CommonGrpcClient;
use App\Grpc\MyselfGrpcClient;
use App\Service\Base\BaseService;
use Chive\Helper\ErrorHelper;
use Grpc\Reply;
use Grpc\Request;
use Hyperf\GrpcClient\BaseClient;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\Utils\Context;

class DispatcherService
{
	/**
	 * 将http请求转发到grpc
	 *
	 * @param RequestInterface $httpRequest
	 * @return mixed
	 */
	public function dispatch(RequestInterface $httpRequest)
	{
		$route   = $httpRequest->getUri()->getPath();
		$request = new Request();
		$request->setRequestParam(json_encode($httpRequest->getParsedBody(), JSON_UNESCAPED_UNICODE));
		$request->setUserInfo(json_encode([], JSON_UNESCAPED_UNICODE));

		//批量上传
		$attachments = $httpRequest->file('attachment');
		if ($attachments) {
			array_walk($attachments, function (&$value) {
				$value = $value->toArray();
			});
			$request->setAttachment(base64_encode(json_encode($attachments, JSON_THROW_ON_ERROR)));
		}
		//单个上传
		if ($httpRequest->hasFile('file')) {
			$request->setFile(base64_encode(json_encode($httpRequest->file("file")->toArray(), JSON_THROW_ON_ERROR)));
		}

		$client = new MyselfGrpcClient();

		list($reply, $status) = $client->grpcSend($route, $request);
		$response[ErrorHelper::RET_CODE]  = $reply->getCode();
		$response[ErrorHelper::RET_MSG]   = $reply->getMsg();
		$response[ErrorHelper::RET_DATA]  = json_decode($reply->getData(), true);
		$response[ErrorHelper::RET_TOTAL] = $reply->getTotal();
		return $response;
	}

}