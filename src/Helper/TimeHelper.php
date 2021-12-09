<?php
/**
 * Class TimeHelper
 * 作者: su
 * 时间: 2021/5/18 9:56
 * 备注: 时间处理
 */

namespace Chive\Helper;


class TimeHelper
{
	/**
	 * 将秒数转为剩余时间字符串
	 * @param $time
	 * @return false|string
	 */
	public static function Sec2Time($time)
	{
		if (!is_numeric($time)) {
			return false;
		}
		$value = array(
			'years'   => 0, 'days' => 0, 'hours' => 0,
			'minutes' => 0, 'seconds' => 0,
		);
		if ($time >= 31556926) {
			$value['years'] = floor($time / 31556926);
			$time           = ($time % 31556926);
		}
		if ($time >= 86400) {
			$value['days'] = floor($time / 86400);
			$time          = ($time % 86400);
		}
		if ($time >= 3600) {
			$value['hours'] = floor($time / 3600);
			$time           = ($time % 3600);
		}
		if ($time >= 60) {
			$value['minutes'] = floor($time / 60);
			$time             = ($time % 60);
		}
		$value['seconds'] = floor($time);
		$str              = '';
		if ($value['years'] > 0) {
			$str .= $value['years'] . '年';
		}
		if ($value['days']) {
			$str .= $value['days'] . '天';
		}
		if ($value['hours']) {
			$str .= $value['hours'] . '小时';
		}
		if ($value['minutes']) {
			$str .= $value['minutes'] . '分';
		}
		if ($value['seconds']) {
			$str .= $value['seconds'] . '秒';
		}
		return $str;

	}

	/**
	 * 计算一个时间是本周的第几天
	 * 周一为1，周日为7
	 * @param $time
	 * @return mixed
	 */
	public static function week2Which($time)
	{
		$weekarray = array(7, 1, 2, 3, 4, 5, 6);
		return $weekarray[date("w", strtotime($time))];
	}

	/**
	 * 计算一个时间是一个周期的第几天
	 * @param string $start_time 周期开始时间
	 * @param string $time       计算时间
	 * @param int    $cycle      周期天数，例：7天一个周期，15天一个周期
	 */
	public static function cycle2Which($start_time, $time, $cycle)
	{
		$startdate = strtotime($start_time);
		$enddate   = strtotime($time);
		$diff      = round(($enddate - $startdate) / 3600 / 24);
		return ($diff % $cycle) + 1;
	}

	/**
	 * 计算2个时间戳隔了多少天
	 * @param string $start_time 只能传日期 2021-5-24
	 * @param string $end_time   2021-5-25
	 * @return float|int
	 */
	public static function diffDay($start_time, $end_time, $precision = 2)
	{
		$startDate = strtotime($start_time);
		$endDate   = strtotime($end_time);
		$diff      = ($endDate - $startDate) / 3600 / 24;
		$diff      = round($diff, $precision);
		return floor($diff);
	}

	/**
	 * 计算2个时间戳隔了多少小时(只能传同一天内的时间)
	 * @param string $start_time //传 9:00 这样的时间
	 * @param string $end_time   //传 9:00 这样的时间
	 * @param int    $precision  小数点后几位
	 * @return float|int
	 */
	public static function diffHourOneDay($start_time, $end_time, $precision = 2)
	{
		$startDate = strtotime($start_time);
		$endDate   = strtotime($end_time);
		$diff      = ($endDate - $startDate) % 86400 / 3600;
		$diff      = round($diff, $precision);
		return $diff;
	}

	/**
	 * 计算两个时间戳直接过了多少天
	 * 例如  $start_time 2021-10-3  $end_time 2021-10-5  则返回 [2021-10-3,2021-10-4,2021-10-5]
	 * @param int $start_time 时间戳
	 * @param int $end_time   时间戳
	 * @return array
	 */
	public static function intervalDate($start_time, $end_time)
	{
		$startDay = date('Y-m-d', $start_time);
		$endDay   = date('Y-m-d', $end_time);
		if ($startDay == $endDay) {//如果是同一天直接返回
			return [$startDay];
		}

		$diffDay = self::diffDay($startDay, $endDay);
		$data[]  = $startDay;
		$time    = $start_time;
		for ($i = 1; $i <= $diffDay; $i++) {
			$time   = $time + 86400;
			$data[] = date('Y-m-d', $time);
		}
		return $data;
	}

	/**
	 * 时间戳转时间格式
	 * @param $time
	 * @return false|string
	 */
	public static function time2Utc($time)
	{
		return $time ? date('Y/m/d H:i:s', (int)$time) : '';
	}

	/**
	 * 时间戳转日期格式
	 * @param $time
	 * @return false|string
	 */
	public static function time2Date($time)
	{
		return $time ? date('Y/m/d', (int)$time) : '';
	}

}