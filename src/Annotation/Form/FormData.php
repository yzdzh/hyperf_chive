<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
namespace Chive\Annotation\Form;

/**
 * @Annotation
 * @Target({"METHOD"})
 */
class FormData extends Param
{
    public $in = 'formData';
}
