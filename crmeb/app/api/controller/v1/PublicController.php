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
namespace app\api\controller\v1;


use app\services\activity\combination\StorePinkServices;
use app\services\activity\lottery\LuckLotteryRecordServices;
use app\services\diy\DiyServices;
use app\services\kefu\service\StoreServiceServices;
use app\services\order\DeliveryServiceServices;
use app\services\order\StoreOrderCartInfoServices;
use app\services\order\StoreOrderServices;
use app\services\other\AgreementServices;
use app\services\other\CacheServices;
use app\services\product\product\StoreCategoryServices;
use app\services\product\product\StoreProductServices;
use app\services\shipping\ExpressServices;
use app\services\shipping\SystemCityServices;
use app\services\system\AppVersionServices;
use app\services\system\attachment\SystemAttachmentServices;
use app\services\system\config\SystemConfigServices;
use app\services\system\config\SystemStorageServices;
use app\services\system\lang\LangCodeServices;
use app\services\system\lang\LangCountryServices;
use app\services\system\lang\LangTypeServices;
use app\services\system\store\SystemStoreServices;
use app\services\system\store\SystemStoreStaffServices;
use app\services\user\UserBillServices;
use app\services\user\UserExtractServices;
use app\services\user\UserInvoiceServices;
use app\services\user\UserServices;
use app\services\wechat\RoutineSchemeServices;
use app\services\wechat\WechatUserServices;
use app\Request;
use crmeb\services\CacheService;
use app\services\other\UploadService;
use crmeb\services\pay\Pay;
use crmeb\services\workerman\ChannelService;

/**
 * 公共类
 * Class PublicController
 * @package app\api\controller
 */
class PublicController
{
    /**
     * 主页获取
     * @param Request $request
     * @return mixed
     */
    public function index(Request $request)
    {
        $banner = sys_data('routine_home_banner') ?: [];//TODO 首页banner图
        $menus = sys_data('routine_home_menus') ?: [];//TODO 首页按钮
        $roll = sys_data('routine_home_roll_news') ?: [];//TODO 首页滚动新闻
        $activity = sys_data('routine_home_activity', 3) ?: [];//TODO 首页活动区域图片
        $explosive_money = sys_data('index_categy_images') ?: [];//TODO 首页超值爆款
        $site_name = sys_config('site_name');
        $routine_index_page = sys_data('routine_index_page');
        $info['fastInfo'] = $routine_index_page[0]['fast_info'] ?? '';//TODO 快速选择简介
        $info['bastInfo'] = $routine_index_page[0]['bast_info'] ?? '';//TODO 精品推荐简介
        $info['firstInfo'] = $routine_index_page[0]['first_info'] ?? '';//TODO 首发新品简介
        $info['salesInfo'] = $routine_index_page[0]['sales_info'] ?? '';//TODO 促销单品简介
        $logoUrl = sys_config('routine_index_logo');//TODO 促销单品简介
        if (strstr($logoUrl, 'http') === false && $logoUrl) {
            $logoUrl = sys_config('site_url') . $logoUrl;
        }
        $logoUrl = str_replace('\\', '/', $logoUrl);
        $fastNumber = (int)sys_config('fast_number', 0);//TODO 快速选择分类个数

        /** @var StoreCategoryServices $categoryService */
        $categoryService = app()->make(StoreCategoryServices::class);
        $info['fastList'] = $fastNumber ? $categoryService->byIndexList($fastNumber, 'id,cate_name,pid,pic') : [];//TODO 快速选择分类个数
        /** @var StoreProductServices $storeProductServices */
        $storeProductServices = app()->make(StoreProductServices::class);
        //获取推荐商品
        [$baseList, $firstList, $benefit, $likeInfo, $vipList] = $storeProductServices->getRecommendProductArr((int)$request->uid(), ['is_best', 'is_new', 'is_benefit', 'is_hot']);
        $info['bastList'] = $baseList;//TODO 精品推荐个数
        $info['firstList'] = $firstList;//TODO 首发新品个数
        $info['bastBanner'] = sys_data('routine_home_bast_banner') ?? [];//TODO 首页精品推荐图片
        $lovely = sys_data('routine_home_new_banner') ?: [];//TODO 首发新品顶部图
        if ($request->uid()) {
            /** @var WechatUserServices $wechatUserService */
            $wechatUserService = app()->make(WechatUserServices::class);
            $subscribe = (bool)$wechatUserService->value(['uid' => $request->uid()], 'subscribe');
        } else {
            $subscribe = true;
        }
        $newGoodsBananr = sys_config('new_goods_bananr');
        $tengxun_map_key = sys_config('tengxun_map_key');
        return app('json')->success(compact('banner', 'menus', 'roll', 'info', 'activity', 'lovely', 'benefit', 'likeInfo', 'logoUrl', 'site_name', 'subscribe', 'newGoodsBananr', 'tengxun_map_key', 'explosive_money'));
    }

    /**
     * 获取分享配置
     * @return mixed
     */
    public function share()
    {
        $data['img'] = sys_config('wechat_share_img');
        if (strstr($data['img'], 'http') === false && $data['img'] != '') {
            $data['img'] = sys_config('site_url') . $data['img'];
        }
        $data['img'] = str_replace('\\', '/', $data['img']);
        $data['title'] = sys_config('wechat_share_title');
        $data['synopsis'] = sys_config('wechat_share_synopsis');
        return app('json')->success($data);
    }

    /**
     * 获取网站配置
     * @return mixed
     */
    public function getSiteConfig()
    {
        $data['record_No'] = sys_config('record_No');
        $data['icp_url'] = sys_config('icp_url');
        $data['network_security'] = sys_config('network_security');
        $data['network_security_url'] = sys_config('network_security_url');
        return app('json')->success($data);
    }

    /**
     * 获取个人中心菜单
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function menu_user(Request $request)
    {
        $menusInfo = sys_data('routine_my_menus') ?? [];
        $uid = 0;
        $userInfo = [];
        if ($request->hasMacro('user')) $userInfo = $request->user();
        if ($request->hasMacro('uid')) $uid = $request->uid();

        //用户等级开关
        $vipOpen = sys_config('member_func_status');
        //分销功能开关
        $brokerageFuncStatus = sys_config('brokerage_func_status');
        //余额功能开关
        $balanceFuncStatus = sys_config('balance_func_status');
        //付费会员开关
        $svipOpen = sys_config('member_card_status');
        $userService = $userOrder = $userVerifyStatus = $deliveryUser = $invoiceStatus = $isUserPromoter = false;
        if ($uid && $userInfo) {
            /** @var StoreServiceServices $storeService */
            $storeService = app()->make(StoreServiceServices::class);
            //是否客服
            $userService = $storeService->checkoutIsService(['uid' => $uid, 'status' => 1]);
            //是否订单管理
            $userOrder = $storeService->checkoutIsService(['uid' => $uid, 'status' => 1, 'customer' => 1]);
            //是否核销员
            $userVerifyStatus = app()->make(SystemStoreStaffServices::class)->verifyStatus($uid);
            //是否配送员
            $deliveryUser = app()->make(DeliveryServiceServices::class)->checkoutIsService($uid);
            //发票功能开关
            $invoiceStatus = app()->make(UserInvoiceServices::class)->invoiceFuncStatus(false);
            //是否分销员
            $isUserPromoter = app()->make(UserServices::class)->checkUserPromoter($uid, $userInfo);
        }
        $auth = [];
        $auth['/pages/users/user_vip/index'] = $vipOpen;
        $auth['/pages/users/user_spread_user/index'] = $brokerageFuncStatus && $isUserPromoter;
        $auth['/pages/annex/settled/index'] = $brokerageFuncStatus && sys_config('store_brokerage_statu') == 1 && !$isUserPromoter;
        $auth['/pages/users/user_money/index'] = $balanceFuncStatus;
        $auth['/pages/admin/order/index'] = $userOrder;
        $auth['/pages/admin/order_cancellation/index'] = $userVerifyStatus || $deliveryUser;
        $auth['/pages/users/user_invoice_list/index'] = $invoiceStatus;
        $auth['/pages/annex/vip_paid/index'] = $svipOpen;
        $auth['/kefu/mobile_list'] = $userService;
        foreach ($menusInfo as $key => &$value) {
            if ($value['url'] == '/pages/users/user_spread_user/index' && $auth['/pages/annex/settled/index']) {
                $value['name'] = '分销申请';
                $value['url'] = '/pages/annex/settled/index';
            }
            if (isset($auth[$value['url']]) && !$auth[$value['url']]) {
                unset($menusInfo[$key]);
                continue;
            }
            if ($value['url'] == '/kefu/mobile_list') {
                $value['url'] = sys_config('site_url') . $value['url'];
                if ($request->isRoutine()) {
                    $value['url'] = str_replace('http://', 'https://', $value['url']);
                }
            }
        }
        /** @var SystemConfigServices $systemConfigServices */
        $systemConfigServices = app()->make(SystemConfigServices::class);
        $bannerInfo = $systemConfigServices->getSpreadBanner() ?? [];
        $my_banner = sys_data('routine_my_banner');
        $routine_contact_type = sys_config('routine_contact_type', 0);
        /** @var DiyServices $diyServices */
        $diyServices = app()->make(DiyServices::class);
        $diy_data = $diyServices->get(['template_name' => 'member', 'type' => 1], ['value', 'order_status', 'my_banner_status', 'my_menus_status', 'business_status']);
        $diy_data = $diy_data ? $diy_data->toArray() : [];
        return app('json')->success(['routine_my_menus' => array_merge($menusInfo), 'routine_my_banner' => $my_banner, 'routine_spread_banner' => $bannerInfo, 'routine_contact_type' => $routine_contact_type, 'diy_data' => $diy_data]);
    }

    /**
     * 热门搜索关键字获取
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function search()
    {
        $routineHotSearch = sys_data('routine_hot_search') ?? [];
        $searchKeyword = [];
        if (count($routineHotSearch)) {
            foreach ($routineHotSearch as $key => &$item) {
                array_push($searchKeyword, $item['title']);
            }
        }
        return app('json')->success($searchKeyword);
    }


    /**
     * 图片上传
     * @param Request $request
     * @param SystemAttachmentServices $services
     * @return mixed
     */
    public function upload_image(Request $request, SystemAttachmentServices $services)
    {
        $data = $request->postMore([
            ['filename', 'file'],
        ]);
        if (!$data['filename']) return app('json')->fail(100100);
        if (CacheService::has('start_uploads_' . $request->uid()) && CacheService::get('start_uploads_' . $request->uid()) >= 100) return app('json')->fail(100101);
        $upload = UploadService::init();
        $info = $upload->to('store/comment')->validate()->move($data['filename']);
        if ($info === false) {
            return app('json')->fail($upload->getError());
        }
        $res = $upload->getUploadInfo();
        $services->attachmentAdd($res['name'], $res['size'], $res['type'], $res['dir'], $res['thumb_path'], 1, (int)sys_config('upload_type', 1), $res['time'], 3);
        if (CacheService::has('start_uploads_' . $request->uid()))
            $start_uploads = (int)CacheService::get('start_uploads_' . $request->uid());
        else
            $start_uploads = 0;
        $start_uploads++;
        CacheService::set('start_uploads_' . $request->uid(), $start_uploads, 86400);
        $res['dir'] = path_to_url($res['dir']);
        if (strpos($res['dir'], 'http') === false) $res['dir'] = $request->domain() . $res['dir'];
        return app('json')->success(100009, ['name' => $res['name'], 'url' => $res['dir']]);
    }

    /**
     * 物流公司
     * @return mixed
     */
    public function logistics(ExpressServices $services)
    {
        $expressList = $services->expressList();
        return app('json')->success($expressList ?? []);
    }

    /**
     * 短信购买异步通知
     *
     * @param Request $request
     * @return mixed
     */
    public function sms_pay_notify(Request $request)
    {
        [$order_id, $price, $status, $num, $pay_time, $attach] = $request->postMore([
            ['order_id', ''],
            ['price', 0.00],
            ['status', 400],
            ['num', 0],
            ['pay_time', time()],
            ['attach', 0],
        ], true);
        if ($status == 200) {
            try {
                ChannelService::instance()->send('PAY_SMS_SUCCESS', ['price' => $price, 'number' => $num], [$attach]);
            } catch (\Throwable $e) {
            }
            return app('json')->success(100010);
        }
        return app('json')->fail(100005);
    }

    /**
     * 记录用户分享
     * @param Request $request
     * @param UserBillServices $services
     * @return mixed
     */
    public function user_share(Request $request, UserBillServices $services)
    {
        $uid = (int)$request->uid();
        $services->setUserShare($uid);
        return app('json')->success(100012);
    }

    /**
     * 获取图片base64
     * @param Request $request
     * @return mixed
     */
    public function get_image_base64(Request $request)
    {
        [$imageUrl, $codeUrl] = $request->postMore([
            ['image', ''],
            ['code', ''],
        ], true);
        /** @var SystemStorageServices $systemStorageServices */
        $systemStorageServices = app()->make(SystemStorageServices::class);
        $domainArr = $systemStorageServices->getColumn([], 'domain');
        $domainArr = array_merge($domainArr, [$request->host()]);
        $domainArr = array_unique(array_diff($domainArr, ['']));
        if (count($domainArr)) {
            $domainArr = array_map(function ($item) {
                return str_replace(['https://', 'http://'], '', $item);
            }, $domainArr);
        }
        $domainArr[] = 'mp.weixin.qq.com';
        $imageUrlHost = $imageUrl ? (parse_url($imageUrl)['host'] ?? $imageUrl) : $imageUrl;
        $codeUrlHost = $codeUrl ? (parse_url($codeUrl)['host'] ?? $codeUrl) : $codeUrl;
        if ($domainArr && (($imageUrl && !in_array($imageUrlHost, $domainArr)) || ($codeUrl && !in_array($codeUrlHost, $domainArr)))) {
            return app('json')->success(['code' => false, 'image' => false]);
        }
        if ($imageUrl !== '' && !preg_match('/.*(\.png|\.jpg|\.jpeg|\.gif)$/', $imageUrl) && strpos(strtolower($imageUrl), "phar://") !== false) {
            return app('json')->success(['code' => false, 'image' => false]);
        }
        if ($codeUrl !== '' && !(preg_match('/.*(\.png|\.jpg|\.jpeg|\.gif)$/', $codeUrl) || strpos($codeUrl, 'https://mp.weixin.qq.com/cgi-bin/showqrcode') !== false) && strpos(strtolower($codeUrl), "phar://") !== false) {
            return app('json')->success(['code' => false, 'image' => false]);
        }
        try {
            $code = CacheService::remember($codeUrl, function () use ($codeUrl) {
                $codeTmp = $code = $codeUrl ? image_to_base64($codeUrl) : false;
                if (!$codeTmp) {
                    $putCodeUrl = put_image($codeUrl);
                    //TODO
                    $code = $putCodeUrl ? image_to_base64(app()->request->domain(true) . '/' . $putCodeUrl) : false;
                    if ($putCodeUrl) {
                        unlink($_SERVER["DOCUMENT_ROOT"] . DS . $putCodeUrl);
                    }
                }
                return $code;
            });
            $image = CacheService::remember($imageUrl, function () use ($imageUrl) {
                $imageTmp = $image = $imageUrl ? image_to_base64($imageUrl) : false;
                if (!$imageTmp) {
                    $putImageUrl = put_image($imageUrl);
                    //TODO
                    $image = $putImageUrl ? image_to_base64(app()->request->domain(true) . '/' . $putImageUrl) : false;
                    if ($putImageUrl) {
                        unlink($_SERVER["DOCUMENT_ROOT"] . DS . $putImageUrl);
                    }
                }
                return $image;
            });
            return app('json')->success(compact('code', 'image'));
        } catch (\Exception $e) {
            return app('json')->fail(100005);
        }
    }

    /**
     * 门店列表
     * @return mixed
     */
    public function store_list(Request $request, SystemStoreServices $services)
    {
        list($latitude, $longitude) = $request->getMore([
            ['latitude', ''],
            ['longitude', ''],
        ], true);
        $data['list'] = $services->getStoreList(['type' => 0], ['id', 'name', 'phone', 'address', 'detailed_address', 'image', 'latitude', 'longitude'], $latitude, $longitude);
        $data['tengxun_map_key'] = sys_config('tengxun_map_key');
        return app('json')->success($data);
    }

    /**
     * 查找城市数据
     * @param Request $request
     * @return mixed
     */
    public function city_list(Request $request)
    {
        /** @var SystemCityServices $systemCity */
        $systemCity = app()->make(SystemCityServices::class);
        return app('json')->success($systemCity->cityList());
    }

    /**
     * 获取拼团数据
     * @return mixed
     */
    public function pink(StorePinkServices $pink, UserServices $user)
    {
        $data['pink_count'] = $pink->getCount(['is_refund' => 0]);
        $uids = array_flip($pink->getColumn(['is_refund' => 0], 'uid'));
        if (count($uids)) {
            $uids = array_rand($uids, count($uids) < 3 ? count($uids) : 3);
        }
        $data['avatars'] = $uids ? $user->getColumn(is_array($uids) ? [['uid', 'in', $uids]] : ['uid' => $uids], 'avatar') : [];
        foreach ($data['avatars'] as &$avatar) {
            if (strpos($avatar, '/statics/system_images/') !== false) {
                $avatar = set_file_url($avatar);
            }
        }
        return app('json')->success($data);
    }

    /**
     * 复制口令接口
     * @return mixed
     */
    public function copy_words()
    {
        $data['words'] = sys_config('copy_words');
        return app('json')->success($data);
    }

    /**生成口令关键字
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function copy_share_words(Request $request)
    {
        list($productId) = $request->getMore([
            ['product_id', ''],
        ], true);
        /** @var StoreProductServices $productService */
        $productService = app()->make(StoreProductServices::class);
        $keyWords['key_words'] = $productService->getProductWords($productId);
        return app('json')->success($keyWords);
    }

    /**
     * 获取页面数据
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getDiy(DiyServices $services, $id = 0)
    {
        return app('json')->success($services->getDiyInfo((int)$id));
    }

    /**
     * 获取底部导航
     * @param DiyServices $services
     * @param string $template_name
     * @return mixed
     */
    public function getNavigation(DiyServices $services, string $template_name = '')
    {
        return app('json')->success($services->getNavigation($template_name));
    }

    /**
     * 首页商品数据
     * @param Request $request
     */
    public function home_products_list(Request $request, DiyServices $services)
    {
        $data = $request->getMore([
            ['priceOrder', ''],
            ['newsOrder', ''],
            ['salesOrder', ''],
            [['type', 'd'], 0],
            ['ids', ''],
            [['selectId', 'd'], ''],
            ['selectType', 0],
            ['isType', 0],
        ]);
        $where = [];
        $where['is_show'] = 1;
        $where['is_del'] = 0;
        $where['productId'] = '';
        if ($data['selectType'] == 1) {
            if (!$data['ids']) {
                return app('json')->success(100011);
            }
            $where['ids'] = $data['ids'] ? explode(',', $data['ids']) : [];
            if ($data['type'] != 2 && $data['type'] != 3 && $data['type'] != 8) {
                $where['type'] = 0;
            } else {
                $where['type'] = $data['type'];
            }
        } else {
            $where['priceOrder'] = $data['priceOrder'];
            $where['newsOrder'] = $data['newsOrder'];
            $where['salesOrder'] = $data['salesOrder'];
            $where['type'] = $data['type'];
            if ($data['selectId']) {
                /** @var StoreCategoryServices $storeCategoryServices */
                $storeCategoryServices = app()->make(StoreCategoryServices::class);
                if ($storeCategoryServices->value(['id' => $data['selectId']], 'pid')) {
                    $where['sid'] = $data['selectId'];
                } else {
                    $where['cid'] = $data['selectId'];
                }
            }
        }
        return app('json')->success($services->homeProductList($where, $request->uid()));
    }

    public function getNewAppVersion($platform)
    {
        /** @var AppVersionServices $appService */
        $appService = app()->make(AppVersionServices::class);
        return app('json')->success($appService->getNewInfo($platform));
    }

    public function getCustomerType()
    {
        $data = [];
        $data['customer_type'] = sys_config('customer_type', 0);
        $data['customer_phone'] = sys_config('customer_phone', 0);
        $data['customer_url'] = sys_config('customer_url', 0);
        $data['customer_corpId'] = sys_config('customer_corpId', 0);
        return app('json')->success($data);
    }


    /**
     * 统计代码
     * @return array|string
     */
    public function getScript()
    {
        return sys_config('statistic_script', '');
    }

    public function customPcJs()
    {
        return sys_config('custom_pc_js', '');
    }

    /**
     * 获取workerman请求域名
     * @return mixed
     */
    public function getWorkerManUrl()
    {
        return app('json')->success(getWorkerManUrl());
    }

    /**
     * 首页开屏广告
     * @return mixed
     */
    public function getOpenAdv()
    {
        /** @var CacheServices $cache */
        $cache = app()->make(CacheServices::class);
        $data = $cache->getDbCache('open_adv', '');
        return app('json')->success($data);
    }

    /**
     * 获取用户协议内容
     * @return mixed
     */
    public function getUserAgreement()
    {
        /** @var CacheServices $cache */
        $cache = app()->make(CacheServices::class);
        $content = $cache->getDbCache('user_agreement', '');
        return app('json')->success(compact('content'));
    }

    /**
     * 获取协议
     * @param AgreementServices $agreementServices
     * @param $type
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getAgreement(AgreementServices $agreementServices, $type)
    {
        $data = $agreementServices->getAgreementBytype($type);
        return app('json')->success($data);
    }

    /**
     * 查询版权信息
     * @return mixed
     */
    public function copyright()
    {
        $copyrightContext = sys_config('nncnL_crmeb_copyright', '');
        $copyrightImage = sys_config('nncnL_crmeb_copyright_image', '');
        $siteName = sys_config('site_name', '');
        $siteLogo = sys_config('wap_login_logo', '');
        return app('json')->success(compact('copyrightContext', 'copyrightImage', 'siteName', 'siteLogo'));
    }

    /**
     * 获取多语言类型列表
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getLangTypeList()
    {
        /** @var LangTypeServices $langTypeServices */
        $langTypeServices = app()->make(LangTypeServices::class);
        $list = $langTypeServices->langTypeList(['status' => 1, 'is_del' => 0])['list'];
        $data = [];
        foreach ($list as $item) {
            $data[] = ['name' => $item['language_name'], 'value' => $item['file_name']];
        }
        return app('json')->success($data);
    }

    /**
     * 获取当前语言json
     * @return mixed
     * @throws \Throwable
     */
    public function getLangJson()
    {
        /** @var LangTypeServices $langTypeServices */
        $langTypeServices = app()->make(LangTypeServices::class);
        /** @var LangCountryServices $langCountryServices */
        $langCountryServices = app()->make(LangCountryServices::class);

        $request = app()->request;
        //获取接口传入的语言类型
        if (!$range = $request->header('cb-lang')) {
            //没有传入则使用系统默认语言显示
            if (!$range = $langTypeServices->value(['is_default' => 1], 'file_name')) {
                //系统没有设置默认语言的话，根据浏览器语言显示，如果浏览器语言在库中找不到，则使用简体中文
                if ($request->header('accept-language') !== null) {
                    $range = explode(',', $request->header('accept-language'))[0];
                } else {
                    $range = 'zh-CN';
                }
            }
        }
        // 获取type_id
        $typeId = $langCountryServices->value(['code' => $range], 'type_id') ?: 1;

        // 获取缓存key
        $langData = $langTypeServices->getColumn(['status' => 1, 'is_del' => 0], 'file_name', 'id');
        $langStr = 'api_lang_' . str_replace('-', '_', $langData[$typeId]);

        //读取当前语言的语言包
        $lang = CacheService::remember($langStr, function () use ($typeId, $range) {
            /** @var LangCodeServices $langCodeServices */
            $langCodeServices = app()->make(LangCodeServices::class);
            return $langCodeServices->getColumn(['type_id' => $typeId, 'is_admin' => 0], 'lang_explain', 'code');
        }, 3600);
        return app('json')->success([$range => $lang]);
    }

    /**
     * 获取当前后台设置的默认语言类型
     * @return mixed
     */
    public function getDefaultLangType()
    {
        /** @var LangTypeServices $langTypeServices */
        $langTypeServices = app()->make(LangTypeServices::class);
        $lang_type = $langTypeServices->value(['is_default' => 1], 'file_name');
        return app('json')->success(compact('lang_type'));
    }

    /**
     * 获取版本号
     * @return mixed
     */
    public function getVersion()
    {
        $version = parse_ini_file(app()->getRootPath() . '.version');
        return app('json')->success(['version' => $version['version'], 'version_code' => $version['version_code']]);
    }

    /**
     * 获取多语言缓存
     * @return \think\Response
     * @author 吴汐
     * @email 442384644@qq.com
     * @date 2023/03/06
     */
    public function getLangVersion()
    {
        return app('json')->success(app()->make(LangCodeServices::class)->getLangVersion());
    }

    /**
     * 商城基础配置汇总接口
     * @return \think\Response
     * @author 吴汐
     * @email 442384644@qq.com
     * @date 2023/04/03
     */
    public function getMallBasicConfig()
    {
        $data['site_name'] = sys_config('site_name');//网站名称
        $data['site_url'] = sys_config('site_url');//网站地址
        $data['wap_login_logo'] = sys_config('wap_login_logo');//移动端登录logo
        $data['record_No'] = sys_config('record_No');//备案号
        $data['icp_url'] = sys_config('icp_url');//备案号链接
        $data['network_security'] = sys_config('network_security');//网安备案
        $data['network_security_url'] = sys_config('network_security_url');//网安备案链接
        $data['store_self_mention'] = sys_config('store_self_mention');//是否开启到店自提
        $data['invoice_func_status'] = sys_config('invoice_func_status');//发票功能启用
        $data['special_invoice_status'] = sys_config('special_invoice_status');//专用发票启用
        $data['member_func_status'] = sys_config('member_func_status');//用户等级启用
        $data['balance_func_status'] = sys_config('balance_func_status');//余额功能启用
        $data['recharge_switch'] = sys_config('recharge_switch');//小程序充值开关
        $data['member_card_status'] = sys_config('member_card_status');//是否开启付费会员
        $data['member_price_status'] = sys_config('member_price_status');//商品会员折扣价展示启用
        $data['ali_pay_status'] = sys_config('ali_pay_status') != '0';//支付宝是否启用
        $data['pay_weixin_open'] = sys_config('pay_weixin_open') != '0';//微信是否启用
        $data['yue_pay_status'] = sys_config('yue_pay_status') == 1 && sys_config('balance_func_status') != 0;//余额是否启用
        $data['offline_pay_status'] = sys_config('offline_pay_status') == 1;//线下是否启用
        $data['friend_pay_status'] = sys_config('friend_pay_status') == 1;//好友是否启用
        $data['wechat_auth_switch'] = (int)in_array(1, sys_config('routine_auth_type'));//微信登录开关
        $data['phone_auth_switch'] = (int)in_array(2, sys_config('routine_auth_type'));//手机号登录开关
        $data['wechat_status'] = sys_config('wechat_appid') != '' && sys_config('wechat_appsecret') != '';//公众号是否配置
        $data['site_func'] = sys_config('model_checkbox', ['seckill', 'bargain', 'combination']);
        return app('json')->success($data);
    }

    /**
     * 小程序跳转链接接口
     * @param $id
     * @author wuhaotian
     * @email 442384644@qq.com
     * @date 2024/2/26
     */
    public function getSchemeUrl($id)
    {
        $url = app()->make(RoutineSchemeServices::class)->value($id, 'url');
        if ($url) {
            echo '<script>window.location.href="' . $url . '";</script>';
        } else {
            echo '<h1>未找到跳转路径</h1>';
        }
    }

    /**
     * 微信服务商支付
     * @param Request $request
     * @return \think\Response
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author wuhaotian
     * @email 442384644@qq.com
     * @date 2024/4/7
     */
    public function servicePayResult(Request $request)
    {
        [$sub_mch_id, $out_trade_no, $check_code] = $request->getMore([
            ['sub_mch_id', ''],
            ['out_trade_no', ''],
            ['check_code', ''],
        ], true);
        $data['site_name'] = sys_config('site_name');//网站名称
        $data['site_url'] = sys_config('site_url');//网站地址
        $data['site_logo'] = sys_config('wap_login_logo');//移动端登录logo
        $order = app()->make(StoreOrderServices::class)->getOne(['order_id' => $out_trade_no]);
        $data['goods_name'] = app()->make(StoreOrderCartInfoServices::class)->getCarIdByProductTitle((int)$order['id']);
        $data['pay_price'] = $order['pay_price'];
        $data['jump_url'] = sys_config('site_url') . '/pages/goods/order_pay_status/index?order_id=' . $out_trade_no . '&msg=支付成功&type=3&totalPrice=' . $data['pay_price'];
        return app('json')->header(['X-Frame-Options' => 'payapp.weixin.qq.com'])->success($data);
    }

    public function getTransferInfo(Request $request, $order_id, $type)
    {
        $extractServices = app()->make(UserExtractServices::class);
        $lotteryRecordServices = app()->make(LuckLotteryRecordServices::class);
        $uid = (int)$request->uid();
        if ($type == 1) {
            $info = $extractServices->getExtractByOrderId($uid, $order_id);
            $info['true_extract_price'] = bcsub($info['extract_price'], $info['extract_fee'], 2);
        } else {
            $info = $lotteryRecordServices->getRecordByOrderId($uid, $order_id);
            $info['true_extract_price'] = $info['num'];
        }
        if ($info['state'] == 'WAIT_USER_CONFIRM') {
            $pay = new Pay('v3_wechat_pay');
            $res = $pay->queryTransferBills($order_id);
            if (isset($res['fail_reason']) && $res['fail_reason'] != '') {
                if ($type == 1) {
                    $extractServices->refuse((int)$info['id'], '提现失败，原因：超时为领取');
                    $extractServices->update($info['id'], ['state' => 'FAIL']);
                } else {
                    $lotteryRecordServices->update($info['id'], ['state' => 'FAIL']);
                }
                $info['state'] = 'FAIL';
            }
        }
        switch ($info['channel_type']) {
            case 'wechat':
                $info['wechat_appid'] = sys_config('wechat_appid');
                break;
            case 'routine':
                $info['wechat_appid'] = sys_config('routine_appid');
                break;
            case 'app':
                $info['wechat_appid'] = sys_config('app_appid');
                break;
        }
        $info['mchid'] = sys_config('pay_weixin_mchid');
        return app('json')->success($info);
    }
}
