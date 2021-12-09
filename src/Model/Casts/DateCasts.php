<?php
/**
 * Class DateCasts
 * 作者: su
 * 时间: 2020/12/11 16:59
 * 备注:
 */

namespace Chive\Model\Casts;


use Chive\Helper\TimeHelper;
use Hyperf\Contract\CastsAttributes;

class DateCasts implements CastsAttributes
{

    public function get($model, string $key, $value, array $attributes)
    {
        if (empty($value) || !is_numeric($value)) {
            return '';
        }
        return TimeHelper::time2Date($value);
    }

    public function set($model, string $key, $value, array $attributes)
    {
        return $value;
    }
}