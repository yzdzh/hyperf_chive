<?php
/**
 * Class TimeCasts
 * 作者: su
 * 时间: 2020/6/12 14:24
 * 备注:
 */

namespace Chive\Model\Casts;


use Carbon\Carbon;
use Chive\Helper\TimeHelper;
use Hyperf\Contract\CastsAttributes;

class TimeCasts implements CastsAttributes
{

    /**
     * @inheritDoc
     */
    public function get($model, string $key, $value, array $attributes)
    {
        if(empty($value) || !is_numeric($value)) {
            return '';
        }
        return $this->getTimeString(intval($value));
    }

    /**
     * @inheritDoc
     */
    public function set($model, string $key, $value, array $attributes)
    {
        return $value;
    }

    public function getTimeString(int $unixTime, int $diffSec = 3600): string
    {
        if (0 == $unixTime || !is_numeric($unixTime)) return '';
//        if (abs(time() - $unixTime) < $diffSec) {
//            return $this->timeToUtc($unixTime) . '(' . Carbon::createFromTimestamp($unixTime)->locale('zh_CN')->longRelativeDiffForHumans() . ')';
//        }
        return TimeHelper::time2Utc($unixTime);
    }

}