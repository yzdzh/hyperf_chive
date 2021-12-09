<?php
/**
 * Class SignHelper
 * 作者: su
 * 时间: 2021/7/27 10:15
 * 备注: 生成签名
 */

namespace Chive\Helper;


class SignHelper
{
	/** @var int 签名有效期10分钟 */
	const TIME_LIMIT = 600;

	/**
	 * 发送参数生成md5
	 * @param array  $requestData 需要发送的数据，需包含appKey
	 * @param string $appSecret   appKey密钥
	 * @return mixed [type]              [description]
	 */
	public static function addSign($requestData, $appSecret)
	{
		$requestData['sign_at'] = time();
		$requestData['sign']    = self::createSign($requestData, $appSecret);
		return $requestData;
	}

	/**
	 * 生成签名
	 * @param $requestData
	 * @param $appSecret
	 * @return string
	 */
	protected static function createSign($requestData, $appSecret)
	{
		unset($requestData['sign']);
		$requestData['appSecret'] = $appSecret;
		ksort($requestData);

		$queryStr = "";
		foreach ($requestData as $key => $value) {
			if (is_array($value) || is_object($value)) {
				$value = md5(json_encode($value, JSON_UNESCAPED_UNICODE));
			}
			if ($value !== '') {
				$queryStr .= $key . "=" . $value . "&";
			}
		}
		return md5($queryStr . $appSecret);
	}

	/**
	 * 验证签名是否合法
	 * 验签失败返回false， 成功返回去掉签名等字段后的data
	 * @param $requestData
	 * @param $appSecret
	 * @return array|bool
	 */
	public static function checkSign($requestData, $appSecret)
	{
		if (empty($requestData['sign_at'])) {
			return false;
		}
		if ($requestData['sign_at'] + self::TIME_LIMIT < time()) {
			return false;
		}
		$sign = self::createSign($requestData, $appSecret);
		if ($requestData['sign'] != $sign) {
			return false;
		}
		unset($requestData['sign']);
		unset($requestData['sign_at']);
		return $requestData;
	}


}