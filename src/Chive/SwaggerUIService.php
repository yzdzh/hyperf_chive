<?php
/**
 * Class SwaggerUIService
 * 作者: su
 * 时间: 2021/5/6 16:39
 * 备注:
 */

namespace Chive\Chive;


use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Utils\ApplicationContext;

class SwaggerUIService
{
	public function main()
	{
		if (config('chive.start_swagger_enable') == true) {
			$this->handle();
		}
		return;
	}

	public function handle($port = null)
	{
		$dir          = __DIR__;
		$root         = realpath($dir . '/../../ui');
		$config       = ApplicationContext::getContainer()->get(ConfigInterface::class);
		$swagger_file = $config->get('chive.swagger_output_file');
		$servers      = $config->get('server.servers');
		$ui           = 'default';
		$host         = '0.0.0.0';
		var_dump($port);
		if (empty($port)) {
			$port = (int)$config->get('chive.start_swagger_port');
		}
		
		$http = new \Swoole\Http\Server($host, $port);
		$http->set([
			'document_root'         => $root . '/' . $ui,
			'enable_static_handler' => true,
			'http_index_files'      => ['index.html', 'doc.html'],
		]);

		$http->on('start', function ($server) use ($root, $swagger_file, $ui, $host, $port, $servers) {
			$stdout = make(StdoutLoggerInterface::class);
			$stdout->info(sprintf('Swagger UI is started at http://%s:%s', $host, $port));

			foreach ($servers as $index => $server) {
				$copy_file = str_replace('{server}', $server['name'], $swagger_file);
				$copy_json = sprintf('cp %s %s', $copy_file, $root . '/' . $ui);
				system($copy_json);
				\Swoole\Timer::tick(1000, function () use ($copy_json) {
					system($copy_json);
				});
				if ($index === 0) {
					$index_html = $root . '/' . $ui;
					$html       = file_get_contents($index_html . '/index.html');
					$path_info  = explode('/', $copy_file);
					$html       = str_replace('{swagger-json-url}', end($path_info), $html);
					file_put_contents($index_html . '/index.html', $html);
				}
			}

			\Swoole\Timer::after(1000, function () use ($host, $port) {
				// TODO win下
				system(sprintf('open http://%s:%s', $host, $port));
			});
		});

		$http->on('request', function ($request, $response) {
			$response->header('Content-Type', 'text/plain');
			$response->end("This is swagger ui server.\n");
		});
		$http->start();
	}
}