<?php

namespace Chive\Helper;

use Chive\Exception\BusinessException;
use Firebase\JWT\JWT;

/**
 * ★使用需引入包：composer require firebase/php-jwt
 */
class JwtHelper
{
	/**
	 * 获取jwtToken
	 * @param array     $data   需要发送的数据
	 * @param string    $key    签发者 iss
	 * @param string    $secret 密钥
	 * @param float|int $expire 过期时间，秒
	 * @return string
	 */
	public static function getToken($data, $key, $secret, $expire = 3600 * 24)
	{
		$time  = time(); //当前时间
		$token = [
			'iss'  => $key, //签发者 可选
			'aud'  => '', //接收该JWT的一方，可选
			'iat'  => $time, //签发时间
			'nbf'  => $time, //(Not Before)：某个时间点后才能访问，比如设置time+30，表示当前时间30秒后才能使用
			'exp'  => $time + ($expire), //过期时间,这里设置5小时，这里也可以配置化处理
			'data' => $data, //自定义信息，不要定义敏感信息，一般放用户id，足以。
		];
		return JWT::encode($token, $secret); //输出Token
	}

	/**
	 * 校验jwt权限API
	 * @param string $token
	 * @return array|bool|string
	 */
	public static function checkToken($token, $secret)
	{
		if (empty($token)) return false;
		$jwt_list = explode(" ", $token);
		if ($jwt_list[0] != 'Bearer') {
			throw new BusinessException(ErrorHelper::FAIL_CODE, 'Token格式不对');
		}
		$jwt = $jwt_list[1];
		try {
			JWT::$leeway = 60; //当前时间减去60，把时间留点余地
			$decoded     = JWT::decode($jwt, $secret, ['HS256']); //HS256方式，这里要和签发的时候对应

			$arr = (array)$decoded;
		} catch (\Exception $e) {
			//Firebase定义了多个 throw new，我们可以捕获多个catch来定义问题，catch加入自己的业务，比如token过期可以用当前Token刷新一个新Token
			throw new BusinessException(ErrorHelper::FAIL_CODE, $e->getMessage());
		}
		return (array)$arr['data'];
	}
}












