<?php
/**
 * Class Grpc2HttpController
 * 作者: su
 * 时间: 2021/7/8 10:07
 * 备注: grpc和http通用参考代码
 */

namespace App\Controller;


use Chive\Annotation\Route\ClassRoute;
use Chive\Constants\CommonConstants;
use Chive\Helper\AESHelper;
use Chive\Helper\ErrorHelper;
use Chive\Helper\LogHelper;
use Hyperf\Utils\Codec\Json;
use Hyperf\Utils\Context;
use Pb\Params;
use Pb\Reply;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;

/**
 * @ClassRoute(server="pb",prefix="/pb.client")
 */
class Grpc2HttpController extends AbstractController
{
	public function curdClient(Params $params)
	{
		$className = $params->getController();
		$method    = $params->getMethod();
		$client    = $this->container->get($className);
		$map       = '';
		if (!empty($params->getRequest())) {
			$requestData = AESHelper::opensslDecrypt($params->getRequest());
			$map         = Json::decode($requestData);
		}
		// 将参数存上下文
		Context::set(CommonConstants::GRPC_REQUEST_KEY, $map);
		Context::set(CommonConstants::IS_GRPC_KEY, true);
		$random = mt_rand(1, 999999);
		LogHelper::info("[{$random}]" . $className . "::" . $method . "入 " . json_encode($map, JSON_UNESCAPED_UNICODE), 'info', 'grpcLog', 0);
		/** @var PsrResponseInterface $res */
		$res     = $client->{$method}($map);
		$message = new Reply();
		$message->setErrCode($res[ErrorHelper::RET_CODE]);
		$message->setMsg($res[ErrorHelper::RET_MSG]);
		$res[ErrorHelper::RET_DATA] = $res[ErrorHelper::RET_DATA]
			? AESHelper::opensslEncrypt(Json::encode($res[ErrorHelper::RET_DATA])) : '';
		$message->setData($res[ErrorHelper::RET_DATA]);
		$message->setTotal($res[ErrorHelper::RET_TOTAL]);
		LogHelper::info("[{$random}]" . $className . "::" . $method . "出 " . json_encode($res, JSON_UNESCAPED_UNICODE), 'info', 'grpcLog', 0);
		return $message;
	}
}