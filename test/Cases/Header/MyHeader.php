<?php
/**
 * Class BackgroundHeader
 * 作者: su
 * 时间: 2021/10/29 18:14
 * 备注: 前台header
 */

namespace HyperfTest\Cases\Header;

use App\Dao\DeveloperDao;
use App\Helper\SignHelper;
use App\Model\Developer;
use App\Service\FrontLoginService;
use Chive\Exception\BusinessException;
use Chive\Helper\ErrorHelper;
use HyperfTest\Cases\Constant;

/**
 * 后台header信息获取，主要用于处理token
 */
class MyHeader extends AbstractHeader
{

    /**
     * @return mixed|void
     */
    public function process()
    {
        $appKey = Constant::APP_KEY;

        /** @var Developer $developer */
        $developer = make(DeveloperDao::class)->getOne(['app_key' => $appKey]);
        if (empty($developer)) {
            throw new BusinessException(ErrorHelper::FAIL_CODE, '找不到app配置');
        }
        $params = [
            'appid' => $appKey,
            'mid' => Constant::MID,
            'm_name' => '公司名',
            'account' => '公司账号xxx',
        ];
        $data = SignHelper::addSign($params, $developer->app_secret);
        $token = make(FrontLoginService::class)->getToken($data);
        $this->header = ['authorization' => 'Bearer ' . $token['token']];
    }
}
