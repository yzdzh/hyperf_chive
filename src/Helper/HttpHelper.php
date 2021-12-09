<?php

namespace Chive\Helper;

use GuzzleHttp\Exception\RequestException;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Guzzle\ClientFactory;
use Psr\Log\LoggerInterface;

/**
 * Http封装类
 * ★使用需要引入包：composer require hyperf/guzzle
 */
class HttpHelper
{
	/**
	 * @Inject()
	 * @var \Hyperf\Guzzle\ClientFactory
	 */
	protected $clientFactory;

	/** @var 日志标识名 */
	protected $logName = 'guzzle';
	/** @var 日志组 */
	protected $logGroup = 'http';

	/**
	 * 打开日志记录开关
	 * @param string $name
	 * @param string $group
	 */
	public function log($name = 'guzzle', $group = 'http')
	{
		$this->logName  = $name;
		$this->logGroup = $group;
		return $this;
	}

	/**
	 * 请求Http请求
	 * @param $url
	 * @param $options
	 * @param $isLog
	 * @return \GuzzleHttp\Client
	 * @throws \GuzzleHttp\GuzzleException
	 */
	public function get(string $url, array $options = [], $isLog = false)
	{
		return $this->request('get', $url, $options, $isLog);
	}

	/**
	 * @param string $url
	 * @param array  $options
	 * @param bool   $isLog
	 * @return \GuzzleHttp\Client
	 * @throws \GuzzleHttp\GuzzleException
	 */
	public function post(string $url, array $options = [], $isLog = false)
	{
		return $this->request('post', $url, $options, $isLog);
	}

	public function get2(string $url, $sendData = [], $header = [], $isLog = false)
	{

		return $this->requestProxy('get', $url, $sendData, $header, $isLog);
	}

	public function post2(string $url, $sendData = [], $header = [], $isLog = false)
	{
		return $this->requestProxy('post', $url, $sendData, $header, $isLog);
	}

	/**
	 * get2,post2代理方法
	 */
	private function requestProxy($method, $url, $sendData, $header, $isLog)
	{
		$options = [];
		if (!empty($sendData)) {
			$options['form_params'] = $sendData;
		}
		if (!empty($header)) {
			$options['headers'] = $header;
		}
		return $this->request($method, $url, $options, $isLog);
	}

	/**
	 * @param string $method
	 * @param string $url
	 * @param array  $options ['header' => [header信息], 'form_params' => [发送的数据]]
	 * @param bool   $isLog
	 * @return null|\Psr\Http\Message\ResponseInterface
	 * @throws \GuzzleHttp\GuzzleException
	 */
	public function request(string $method, string $url = '/', array $options = [], $isLog = false)
	{
		$client   = $this->clientFactory->create();
		$log      = '';
		$response = null;
		if ($isLog) {
			$log .= "[" . $method . "]" . $url . " options:" . json_encode($options, JSON_UNESCAPED_UNICODE);
		}
		try {
			$response = $client->request($method, $url, $options);
			if ($isLog) {
				LogHelper::info($log . " result:" . $response->getBody(), $this->logName, $this->logGroup, 0);
			}
		} catch (RequestException $e) {
			LogHelper::info($log . " result:" . $e->getMessage(), $this->logName, $this->logGroup, 0);
		}
		return $response;
	}
}