<?php
/**
 * Class HttpGateWay
 * 作者: su
 * 时间: 2021/7/23 9:34
 * 备注:
 */

namespace App\Gateway;


use App\Controller\AbstractController;
use Hyperf\Di\Annotation\Inject;

class DispatcherController extends AbstractController
{
	/**
	 * @Inject()
	 * @var DispatcherService
	 */
	protected $dispatcherService;

	/**
	 *
	 * 在route.php加入路由规则
	 * Router::addServer('http2grpc', function () {
		Router::addRoute(['GET', 'POST', 'HEAD'], '{path:.*}', 'App\Gateway\DispatcherController@httpDispatch');
		});
	 *
	 * @return mixed
	 * @throws \JsonException
	 */
	public function httpDispatch()
	{
		return $this->dispatcherService->dispatch($this->request);
	}
}