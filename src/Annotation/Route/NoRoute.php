<?php
/**
 * Class NoRoute
 * 作者: su
 * 时间: 2021/8/2 16:50
 * 备注:
 */

namespace Chive\Annotation\Route;

/**
 * 和MethodRoute相反，标识在方法上标识不生成路由
 * @Annotation
 * @Target({"CLASS","METHOD"})
 */
class NoRoute
{

}