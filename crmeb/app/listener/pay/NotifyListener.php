<?php
// +----------------------------------------------------------------------
// | CRMEB [ CRMEB赋能开发者，助力企业发展 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016~2023 https://www.crmeb.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed CRMEB并不是自由软件，未经许可不能去掉CRMEB相关版权
// +----------------------------------------------------------------------
// | Author: CRMEB Team <admin@crmeb.com>
// +----------------------------------------------------------------------

namespace app\listener\pay;


use app\services\pay\PayNotifyServices;
use app\services\pay\PayTransferNotifyServices;
use app\services\wechat\WechatMessageServices;
use crmeb\utils\Hook;

/**
 * 支付异步回调
 * Class NotifyListener
 * @package app\listener\pay
 */
class NotifyListener
{
    /**
     * @param $event
     * @return bool
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function handle($event)
    {
        [$notify, $payType] = $event;

        if (isset($notify['out_bill_no']) && $notify['out_bill_no']) {
            return (new Hook(PayTransferNotifyServices::class, 'wechat'))->listen(
                substr($notify['out_bill_no'], 0, 2),
                $notify['out_bill_no'],
                $notify['transfer_bill_no'],
                $notify['state'],
                $notify['fail_reason'] ?? ''
            );
        } else {
            if (isset($notify['attach']) && $notify['attach']) {
                if (($count = strpos($notify['out_trade_no'], '_')) !== false) {
                    $notify['out_trade_no'] = substr($notify['out_trade_no'], $count + 1);
                }
                return (new Hook(PayNotifyServices::class, 'wechat'))->listen($notify['attach'], $notify['out_trade_no'], $notify['transaction_id'], $payType);
            }

            if ($notify['attach'] === 'wechat' && isset($notify['out_trade_no'])) {
                /** @var WechatMessageServices $wechatMessageService */
                $wechatMessageService = app()->make(WechatMessageServices::class);
                $wechatMessageService->setOnceMessage($notify, $notify['openid'], 'payment_success', $notify['out_trade_no']);
            }
        }

        return false;
    }
}
