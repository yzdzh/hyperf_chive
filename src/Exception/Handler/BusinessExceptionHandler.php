<?php
declare(strict_types=1);
/**
 * Class BusinessExceptionHandler
 * 作者: caiwh
 * 时间: 2020/5/26 10:35
 * 备注:
 */

namespace Chive\Exception\Handler;


use Chive\Constants\CommonConstants;
use Chive\Exception\BusinessException;
use Chive\Helper\ErrorHelper;
use Chive\Middleware\RecordRequestMiddleware;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Di\Annotation\Inject;
use Hyperf\ExceptionHandler\ExceptionHandler;
use Hyperf\Utils\Context;
use Hyperf\Validation\ValidationException;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Hyperf\Logger\LoggerFactory;
use Throwable;
use Hyperf\HttpServer\Response;

class BusinessExceptionHandler extends ExceptionHandler
{
	/**
	 * @var ContainerInterface
	 */
	protected $container;

	/**
	 * @var Response
	 */
	protected $response;

	/**
	 * @var StdoutLoggerInterface
	 */
	protected $logger;

	/**
	 * @var StdoutLoggerInterface
	 */
	protected $stdoutLogger;

	public function __construct(ContainerInterface $container, LoggerFactory $loggerFactory, StdoutLoggerInterface $stdoutLogger, Response $response)
	{
		$this->container    = $container;
		$this->stdoutLogger = $stdoutLogger;
		$this->logger       = $loggerFactory->get('error', 'error');
		$this->response     = $response;
	}


	public function handle(Throwable $throwable, ResponseInterface $response)
	{
		// 记录请求日志
		RecordRequestMiddleware::writeLog($throwable);
		$isGrpc = Context::get(CommonConstants::IS_GRPC_KEY, false);

		if ($throwable instanceof BusinessException) {
			// 记录上级抛出的错误
			self::printPreviousError($throwable, $this->logger, $this->stdoutLogger);
			$ret = ErrorHelper::returnFormat($throwable->getCode(), $throwable->getMessage(), []);
			if ($isGrpc) return $ret;
			return $this->response->json($ret);
		}

		if ($throwable instanceof ValidationException) {
			$message = $throwable->validator->errors()->first();
			$ret     = ErrorHelper::returnFormat(ErrorHelper::FAIL_CODE, $message, []);
			if ($isGrpc) return $ret;
			return $this->response->json($ret);
		}

		if ($throwable instanceof \LogicException) {
			$ret = ErrorHelper::returnFormat(ErrorHelper::TOKEN_ERROR, $throwable->getMessage(), []);
			if ($isGrpc) return $ret;
			return $this->response->json($ret);
		}

		if (env('APP_ENV') == 'dev')
			$this->stdoutLogger->error(sprintf('%s in %s[%s]', $throwable->getMessage(), $throwable->getFile(), $throwable->getLine()));

		$this->logger->error(sprintf('%s in %s[%s]', $throwable->getMessage(), $throwable->getFile(), $throwable->getLine()));
		$ret = ErrorHelper::returnFormat(ErrorHelper::FAIL_CODE, ErrorHelper::SYSTEM_ERROR . ':' . $throwable->getMessage(), []);
		if ($isGrpc) return $ret;
		return $this->response->json($ret);
	}

	/**
	 * 递归打印上一级报错信息
	 * @param $throwable
	 * @param $logger
	 * @param $stdoutLogger
	 */
	private static function printPreviousError($throwable, $logger, $stdoutLogger)
	{
		if ($throwable->getPrevious()) {
			$previousThrowable = $throwable->getPrevious();
			if (env('APP_ENV') == 'dev')
				$stdoutLogger->error(sprintf('%s in %s[%s]', $previousThrowable->getMessage(), $previousThrowable->getFile(), $previousThrowable->getLine()));
			$logger->error(sprintf('%s in %s[%s]', $previousThrowable->getMessage(), $previousThrowable->getFile(), $previousThrowable->getLine()));
			self::printPreviousError($previousThrowable, $logger, $stdoutLogger);
		}
	}

	public function isValid(Throwable $throwable): bool
	{
		return true;
	}
}