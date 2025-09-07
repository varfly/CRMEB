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

namespace app\services\other\export;

use app\services\activity\bargain\StoreBargainServices;
use app\services\activity\combination\StoreCombinationServices;
use app\services\activity\seckill\StoreSeckillServices;
use app\services\BaseServices;
use app\services\order\StoreOrderServices;
use app\services\product\product\StoreCategoryServices;
use app\services\product\product\StoreDescriptionServices;
use app\services\product\product\StoreProductServices;
use app\services\product\sku\StoreProductAttrResultServices;
use app\services\user\member\MemberCardServices;
use app\services\user\UserServices;
use crmeb\services\SpreadsheetExcelService;

class ExportServices extends BaseServices
{
    /**
     * 用户导出
     * @param $where
     * @return array
     */
    public function exportUserList($where)
    {
        /** @var UserServices $userServices */
        $userServices = app()->make(UserServices::class);
        $data = $userServices->index($where)['list'];
        $header = ['用户ID', '昵称', '真实姓名', '性别', '电话', '用户等级', '用户分组', '用户标签', '用户类型', '用户余额', '最后登录时间', '注册时间', '是否注销'];
        $filename = '用户列表_' . date('YmdHis', time());
        $export = $fileKey = [];
        if (!empty($data)) {
            $i = 0;
            foreach ($data as $item) {
                $one_data = [
                    'uid' => $item['uid'],
                    'nickname' => $item['nickname'],
                    'real_name' => $item['real_name'],
                    'sex' => $item['sex'],
                    'phone' => $item['phone'],
                    'level' => $item['level'],
                    'group_id' => $item['group_id'],
                    'labels' => $item['labels'],
                    'user_type' => $item['user_type'],
                    'now_money' => $item['now_money'],
                    'last_time' => date('Y-m-d H:i:s', $item['last_time']),
                    'add_time' => date('Y-m-d H:i:s', $item['add_time']),
                    'is_del' => $item['is_del'] ? '已注销' : '正常'
                ];
                $export[] = $one_data;
                if ($i == 0) {
                    $fileKey = array_keys($one_data);
                }
                $i++;
            }
        }
        return compact('header', 'fileKey', 'export', 'filename');
    }

    /**
     * 订单导出
     * @param $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function exportOrderList($where)
    {
        $header = ['订单号', '收货人姓名', '收货人电话', '收货地址', '商品名称', '规格', '数量', '价格', '总价格', '实际支付', '支付状态', '支付时间', '订单状态', '下单时间', '用户备注', '商家备注', '表单信息'];
        $filename = '订单列表_' . date('YmdHis', time());
        $export = $fileKey = [];
        /** @var StoreOrderServices $orderServices */
        $orderServices = app()->make(StoreOrderServices::class);
        $data = $orderServices->getOrderList($where)['data'];
        if (!empty($data)) {
            $i = 0;
            foreach ($data as $item) {
                if ($item['paid'] == 1) {
                    switch ($item['pay_type']) {
                        case 'weixin':
                            $item['pay_type_name'] = '微信支付';
                            break;
                        case 'yue':
                            $item['pay_type_name'] = '余额支付';
                            break;
                        case 'offline':
                            $item['pay_type_name'] = '线下支付';
                            break;
                        default:
                            $item['pay_type_name'] = '其他支付';
                            break;
                    }
                } else {
                    switch ($item['pay_type']) {
                        default:
                            $item['pay_type_name'] = '未支付';
                            break;
                        case 'offline':
                            $item['pay_type_name'] = '线下支付';
                            break;
                    }
                }
                if ($item['paid'] == 0 && $item['status'] == 0) {
                    $item['status_name'] = '未支付';
                } else if ($item['paid'] == 1 && $item['status'] == 0 && $item['shipping_type'] == 1 && $item['refund_status'] == 0) {
                    $item['status_name'] = '未发货';
                } else if ($item['paid'] == 1 && $item['status'] == 0 && $item['shipping_type'] == 2 && $item['refund_status'] == 0) {
                    $item['status_name'] = '未核销';
                } else if ($item['paid'] == 1 && $item['status'] == 1 && $item['shipping_type'] == 1 && $item['refund_status'] == 0) {
                    $item['status_name'] = '待收货';
                } else if ($item['paid'] == 1 && $item['status'] == 1 && $item['shipping_type'] == 2 && $item['refund_status'] == 0) {
                    $item['status_name'] = '未核销';
                } else if ($item['paid'] == 1 && $item['status'] == 2 && $item['refund_status'] == 0) {
                    $item['status_name'] = '待评价';
                } else if ($item['paid'] == 1 && $item['status'] == 3 && $item['refund_status'] == 0) {
                    $item['status_name'] = '已完成';
                } else if ($item['paid'] == 1 && $item['refund_status'] == 1) {
                    $item['status_name'] = '正在退款';
                } else if ($item['paid'] == 1 && $item['refund_status'] == 2) {
                    $item['status_name'] = '已退款';
                }
                $custom_form = '';
                foreach ($item['custom_form'] as $custom_form_value) {
                    if (is_string($custom_form_value['value'])) {
                        $custom_form .= $custom_form_value['title'] . '：' . $custom_form_value['value'] . '；';
                    } elseif (is_array($custom_form_value['value'])) {
                        $custom_form .= $custom_form_value['title'] . '：' . implode(',', $custom_form_value['value']) . '；';
                    }
                }

//                $goodsName = [];
//                foreach ($item['_info'] as $value) {
//                    $_info = $value['cart_info'];
//                    $sku = '';
//                    if (isset($_info['productInfo']['attrInfo'])) {
//                        if (isset($_info['productInfo']['attrInfo']['suk'])) {
//                            $sku = '(' . $_info['productInfo']['attrInfo']['suk'] . ')';
//                        }
//                    }
//                    if (isset($_info['productInfo']['store_name'])) {
//                        $goodsName[] = implode(' ',
//                            [$_info['productInfo']['store_name'],
//                                $sku,
//                                "[{$_info['cart_num']} * {$_info['truePrice']}]"
//                            ]);
//                    }
//                }
//                $one_data = [
//                    'order_id' => $item['order_id'],
//                    'real_name' => $item['real_name'],
//                    'user_phone' => $item['user_phone'],
//                    'user_address' => $item['user_address'],
//                    'goods_name' => $goodsName ? implode("\n", $goodsName) : '',
//                    'total_price' => $item['total_price'],
//                    'pay_price' => $item['pay_price'],
//                    'pay_type_name' => $item['pay_type_name'],
//                    'pay_time' => $item['pay_time'] > 0 ? date('Y-m-d H:i', (int)$item['pay_time']) : '暂无',
//                    'status_name' => $item['status_name'] ?? '未知状态',
//                    'add_time' => $item['add_time'],
//                    'mark' => $item['mark'],
//                    'remark' => $item['remark'],
//                    'custom_form' => $custom_form,
//                ];
                $goodsInfo = [];
                foreach ($item['_info'] as $value) {
                    $goodsInfo[] = [
                        $value['cart_info']['productInfo']['store_name'],
                        $value['cart_info']['productInfo']['attrInfo']['suk'],
                        $value['cart_info']['cart_num'],
                        $value['cart_info']['truePrice'],
                    ];
                }
                $one_data = [
                    $item['order_id'],
                    $item['real_name'],
                    $item['user_phone'],
                    $item['user_address'],
                    $goodsInfo,
                    $item['total_price'],
                    $item['pay_price'],
                    $item['pay_type_name'],
                    $item['pay_time'] > 0 ? date('Y-m-d H:i', (int)$item['pay_time']) : '暂无',
                    $item['status_name'] ?? '未知状态',
                    $item['add_time'],
                    $item['mark'],
                    $item['remark'],
                    $custom_form,
                ];
                $export[] = $one_data;
                if ($i == 0) {
                    $fileKey = array_keys($one_data);
                }
                $i++;
            }
        }
        return compact('header', 'fileKey', 'export', 'filename');
    }

    /**
     * 订单导出
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function exportOrderDeliveryList()
    {
        $header = ['订单ID', '订单号', '快递名称', '快递编码', '快递单号', '收货人姓名', '收货人电话', '收货地址', '商品信息', '实际支付', '用户备注'];
        $filename = '发货单_' . date('YmdHis', time());
        $export = $fileKey = [];
        /** @var StoreOrderServices $orderServices */
        $orderServices = app()->make(StoreOrderServices::class);
        $data = $orderServices->getOrderList(['status' => 1, 'shipping_type' => 1, 'virtual_type' => 0])['data'];
        if (!empty($data)) {
            $i = 0;
            foreach ($data as $item) {
                $goodsName = [];
                foreach ($item['_info'] as $value) {
                    $_info = $value['cart_info'];
                    $sku = '';
                    if (isset($_info['productInfo']['attrInfo'])) {
                        if (isset($_info['productInfo']['attrInfo']['suk'])) {
                            $sku = '(' . $_info['productInfo']['attrInfo']['suk'] . ')';
                        }
                    }
                    if (isset($_info['productInfo']['store_name'])) {
                        $goodsName[] = implode(' ',
                            [$_info['productInfo']['store_name'],
                                $sku,
                                "[{$_info['cart_num']} * {$_info['truePrice']}]"
                            ]);
                    }
                }
                $one_data = [
                    'id' => $item['id'],
                    'order_id' => $item['order_id'],
                    'delivery_name' => '',
                    'delivery_code' => '',
                    'delivery_id' => '',
                    'real_name' => $item['real_name'],
                    'user_phone' => $item['user_phone'],
                    'user_address' => $item['user_address'],
                    'goods_name' => $goodsName ? implode("\n", $goodsName) : '',
                    'pay_price' => $item['pay_price'],
                    'mark' => $item['mark'],
                ];
                $export[] = $one_data;
                if ($i == 0) {
                    $fileKey = array_keys($one_data);
                }
                $i++;
            }
        }
        return compact('header', 'fileKey', 'export', 'filename');
    }

    /**
     * 商品导出
     * @param $where
     * @return array
     */
    public function exportProductList($where)
    {
        /** @var StoreProductServices $productServices */
        $productServices = app()->make(StoreProductServices::class);
        [$page, $limit] = $this->getPageValue();
        $cateIds = [];
        if (isset($where['cate_id']) && $where['cate_id']) {
            /** @var StoreCategoryServices $storeCategory */
            $storeCategory = app()->make(StoreCategoryServices::class);
            $cateIds = $storeCategory->getColumn(['pid' => $where['cate_id']], 'id');
        }
        if ($cateIds) {
            $cateIds[] = $where['cate_id'];
            $where['cate_id'] = $cateIds;
        }
        $productList = $productServices->dao->getList($where, $page, $limit);
        $header = [
            '商品编号',
            '商品名称', '商品类型', '商品分类(一级)', '商品分类(二级)', '商品单位',
            '已售数量', '起购数量',
            '规格类型', '规格名称', '售价', '划线价', '成本价', '库存', '重量', '体积', '商品编码', '条形码',
            '商品简介', '商品关键字', '商品口令',
            '购买送积分'
        ];
        $filename = '商品导出_' . date('YmdHis', time());
        $virtualType = ['普通商品', '卡密/网盘', '优惠券', '虚拟商品'];
        $export = $fileKey = [];
        if (!empty($productList)) {
            $productList = array_column($productList, null, 'id');
            $productIds = array_column($productList, 'id');
            $descriptionArr = app()->make(StoreDescriptionServices::class)->getColumn([['product_id', 'in', $productIds], ['type', '=', 0]], 'description', 'product_id');
            $cateIds = implode(',', array_column($productList, 'cate_id'));
            /** @var StoreCategoryServices $categoryService */
            $categoryService = app()->make(StoreCategoryServices::class);
            $cateList = $categoryService->getCateParentAndChildName($cateIds);
            $attrResultArr = app()->make(StoreProductAttrResultServices::class)->getColumn([['product_id', 'in', $productIds], ['type', '=', 0]], 'result', 'product_id');
            $i = 0;
            foreach ($attrResultArr as $product_id => &$attrResult) {
                $attrResult = json_decode($attrResult, true);
                foreach ($attrResult['value'] as &$value) {
                    $productInfo = $productList[$product_id];
                    $cateName = array_filter($cateList, function ($val) use ($productInfo) {
                        if (in_array($val['id'], explode(',', $productInfo['cate_id']))) {
                            return $val;
                        }
                    });
                    $skuArr = array_combine(array_column($attrResult['attr'], 'value'), $value['detail']);
                    $attrArr = [];
                    foreach ($attrResult['attr'] as $attrArray) {
                        // 将每个子数组的 'value' 和 'detail' 组合成字符串
                        if (isset($attrArray['detail'][0]['value'])) {
                            $attrArray['detail'] = array_column($attrArray['detail'], 'value');
                        }
                        $detailString = implode(',', $attrArray['detail']); // 将 detail 数组转换为逗号分隔的字符串
                        $attrArr[] = $attrArray['value'] . '=' . $detailString;
                    }
                    $attrString = implode(';', $attrArr);
                    $one_data = [
                        'id' => intval($product_id),
                        'store_name' => $productInfo['store_name'],
                        'virtual_type' => $virtualType[$productInfo['virtual_type']],
                        'cate_name_one' => reset($cateName)['one'] ?? '',
                        'cate_name_two' => reset($cateName)['two'] ?? '',
                        'unit_name' => $productInfo['unit_name'],
                        'ficti' => intval($productInfo['ficti']),
                        'min_qty' => intval($productInfo['min_qty']),
                        'spec_type' => intval($productInfo['spec_type']) == 1 ? '多规格' : '单规格',
                        'sku_name' => implode(',', $value['detail']),
                        'price' => floatval($value['price']),
                        'ot_price' => floatval($value['ot_price']),
                        'cost' => floatval($value['cost']),
                        'stock' => intval($value['stock']),
                        'volume' => intval($value['volume'] ?? 0),
                        'weight' => intval($value['weight'] ?? 0),
                        'bar_code' => $value['bar_code'] ?? '',
                        'bar_code_number' => $value['bar_code_number'] ?? '',
                        'store_info' => $productInfo['store_info'],
                        'keyword' => $productInfo['keyword'],
                        'command_word' => $productInfo['command_word'],
                        'give_integral' => $productInfo['give_integral'],
                    ];
                    $export[] = $one_data;
                    if ($i == 0) {
                        $fileKey = array_keys($one_data);
                    }
                    $i++;
                }
            }
        }
        return compact('header', 'fileKey', 'export', 'filename');
    }

    /**
     * 砍价商品导出
     * @param $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function exportBargainList($where)
    {
        $header = ['砍价名称', '起始价格', '最低价', '参与人数', '成功人数', '剩余库存', '活动状态', '活动时间', '添加时间'];
        $filename = '砍价列表_' . date('YmdHis', time());
        $export = $fileKey = [];
        /** @var StoreBargainServices $bargainServices */
        $bargainServices = app()->make(StoreBargainServices::class);
        $data = $bargainServices->getStoreBargainList($where)['list'];
        if (!empty($data)) {
            $i = 0;
            foreach ($data as $item) {
                $one_data = [
                    'title' => $item['title'],
                    'price' => $item['price'],
                    'min_price' => $item['min_price'],
                    'count_people_all' => $item['count_people_all'],
                    'count_people_success' => $item['count_people_success'],
                    'quota' => $item['quota'],
                    'start_name' => $item['start_name'],
                    'activity_time' => $item['start_time'] . '至' . $item['stop_time'],
                    'add_time' => $item['add_time']
                ];
                $export[] = $one_data;
                if ($i == 0) {
                    $fileKey = array_keys($one_data);
                }
                $i++;
            }
        }
        return compact('header', 'fileKey', 'export', 'filename');
    }

    /**
     * 拼团商品导出
     * @param $where
     * @return array
     */
    public function exportCombinationList($where)
    {
        $header = ['拼团名称', '拼团价', '原价', '拼团人数', '参与人数', '成团数量', '剩余库存', '活动状态', '活动时间', '添加时间'];
        $filename = '拼团列表_' . date('YmdHis', time());
        $export = $fileKey = [];
        /** @var StoreCombinationServices $combinationServices */
        $combinationServices = app()->make(StoreCombinationServices::class);
        $data = $combinationServices->systemPage($where)['list'];
        if (!empty($data)) {
            $i = 0;
            foreach ($data as $item) {
                $one_data = [
                    'title' => $item['title'],
                    'price' => $item['price'],
                    'ot_price' => $item['ot_price'],
                    'count_people' => $item['count_people'],
                    'count_people_all' => $item['count_people_all'],
                    'count_people_pink' => $item['count_people_pink'],
                    'quota' => $item['quota'],
                    'start_name' => $item['start_name'],
                    'activity_time' => $item['start_time'] . '至' . $item['stop_time'],
                    'add_time' => $item['add_time']
                ];
                $export[] = $one_data;
                if ($i == 0) {
                    $fileKey = array_keys($one_data);
                }
                $i++;
            }
        }
        return compact('header', 'fileKey', 'export', 'filename');
    }

    /**
     * 秒杀导出
     * @param $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function exportSeckillList($where)
    {
        $header = ['秒杀名称', '秒杀价', '原价', '剩余库存', '活动状态', '活动时间', '添加时间'];
        $filename = '秒杀列表_' . date('YmdHis', time());
        $export = $fileKey = [];
        /** @var StoreSeckillServices $seckillServices */
        $seckillServices = app()->make(StoreSeckillServices::class);
        $data = $seckillServices->systemPage($where)['list'];
        if (!empty($data)) {
            $i = 0;
            foreach ($data as $item) {
                $one_data = [
                    'title' => $item['title'],
                    'price' => $item['price'],
                    'ot_price' => $item['ot_price'],
                    'quota' => $item['quota'],
                    'start_name' => $item['start_name'],
                    'activity_time' => $item['start_time'] . '至' . $item['stop_time'],
                    'add_time' => $item['add_time']
                ];
                $export[] = $one_data;
                if ($i == 0) {
                    $fileKey = array_keys($one_data);
                }
                $i++;
            }
        }
        return compact('header', 'fileKey', 'export', 'filename');
    }

    /**
     * 会员卡导出
     * @param $id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function exportMemberCard($id)
    {
        /** @var MemberCardServices $memberCardServices */
        $memberCardServices = app()->make(MemberCardServices::class);
        $data = $memberCardServices->getExportData(['batch_card_id' => $id]);
        $header = ['会员卡号', '密码', '领取人', '领取人手机号', '领取时间', '是否使用'];
        $filename = $data['title'] . '批次列表_' . date('YmdHis', time());
        $export = $fileKey = [];
        if (!empty($data['data'])) {
            $userIds = array_column($data['data']->toArray(), 'use_uid');
            /** @var  UserServices $userService */
            $userService = app()->make(UserServices::class);
            $userList = $userService->getColumn([['uid', 'in', $userIds]], 'nickname,phone,real_name', 'uid');


            $i = 0;
            foreach ($data['data'] as $item) {
                $one_data = [
                    'card_number' => $item['card_number'],
                    'card_password' => $item['card_password'],
                    'user_name' => $userList[$item['use_uid']]['real_name'] ?? $userList[$item['use_uid']]['nickname'] ?? '',
                    'user_phone' => $userList[$item['use_uid']]['phone'] ?? "",
                    'use_time' => $item['use_time'],
                    'use_uid' => $item['use_uid'] ? '已领取' : '未领取'
                ];
                $export[] = $one_data;
                if ($i == 0) {
                    $fileKey = array_keys($one_data);
                }
                $i++;
            }
        }
        return compact('header', 'fileKey', 'export', 'filename');
    }

    /**
     * 真实请求导出
     * @param $header excel表头
     * @param $title 标题
     * @param array $export 填充数据
     * @param string $filename 保存文件名称
     * @param string $suffix 保存文件后缀
     * @param bool $is_save true|false 是否保存到本地
     * @return mixed
     */
    public function export($header, $title_arr, $export = [], $filename = '', $suffix = 'xlsx', $is_save = false)
    {
        $title = isset($title_arr[0]) && !empty($title_arr[0]) ? $title_arr[0] : '导出数据';
        $name = isset($title_arr[1]) && !empty($title_arr[1]) ? $title_arr[1] : '导出数据';
        $info = isset($title_arr[2]) && !empty($title_arr[2]) ? $title_arr[2] : date('Y-m-d H:i:s', time());

        $path = SpreadsheetExcelService::instance()->setExcelHeader($header)
            ->setExcelTile($title, $name, $info)
            ->setExcelContent($export)
            ->excelSave($filename, $suffix, $is_save);
        $path = $this->siteUrl() . $path;
        return [$path];
    }

    /**
     * 获取系统接口域名
     * @return string
     */
    public function siteUrl()
    {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        $domainName = $_SERVER['HTTP_HOST'];
        return $protocol . $domainName;
    }


    /**
     * 用户资金导出
     * @param $data 导出数据
     */
    public function userFinance($data = [])
    {
        $export = [];
        if (!empty($data)) {
            foreach ($data as $value) {
                $export[] = [
                    $value['uid'],
                    $value['nickname'],
                    $value['pm'] == 0 ? '-' . $value['number'] : $value['number'],
                    $value['title'],
                    $value['mark'],
                    $value['add_time'],
                ];
            }
        }
        $header = ['会员ID', '昵称', '金额/积分', '类型', '备注', '创建时间'];
        $title = ['资金监控', '资金监控', date('Y-m-d H:i:s', time())];
        $filename = '资金监控_' . date('YmdHis', time());
        $suffix = 'xlsx';
        $is_save = true;
        return $this->export($header, $title, $export, $filename, $suffix, $is_save);
    }

    /**
     * 用户佣金导出
     * @param $data 导出数据
     */
    public function userCommission($data = [])
    {
        $export = [];
        if (!empty($data)) {
            foreach ($data as &$value) {
                $export[] = [
                    $value['nickname'],
                    $value['sum_number'],
                    $value['now_money'],
                    $value['brokerage_price'],
                    $value['extract_price'],
                ];
            }
        }
        $header = ['昵称/姓名', '总佣金金额', '账户余额', '账户佣金', '提现到账佣金'];
        $title = ['拥金记录', '拥金记录' . time(), ' 生成时间：' . date('Y-m-d H:i:s', time())];
        $filename = '拥金记录_' . date('YmdHis', time());
        $suffix = 'xlsx';
        $is_save = true;
        return $this->export($header, $title, $export, $filename, $suffix, $is_save);
    }

    /**
     * 用户积分导出
     * @param $data 导出数据
     */
    public function userPoint($data = [])
    {
        $export = [];
        if (!empty($data)) {
            foreach ($data as $key => $item) {
                $export[] = [
                    $item['id'],
                    $item['title'],
                    $item['balance'],
                    $item['number'],
                    $item['mark'],
                    $item['nickname'],
                    $item['add_time'],
                ];
            }
        }
        $header = ['编号', '标题', '变动前积分', '积分变动', '备注', '用户微信昵称', '添加时间'];
        $title = ['积分日志', '积分日志' . time(), '生成时间：' . date('Y-m-d H:i:s', time())];
        $filename = '积分日志_' . date('YmdHis', time());
        $suffix = 'xlsx';
        $is_save = true;
        return $this->export($header, $title, $export, $filename, $suffix, $is_save);
    }

    /**
     * 用户充值导出
     * @param $data 导出数据
     */
    public function userRecharge($data = [])
    {
        $export = [];
        if (!empty($data)) {
            foreach ($data as $item) {
                $item['_pay_time'] = $item['pay_time'] ? date('Y-m-d H:i:s', $item['pay_time']) : '暂无';
                $item['_add_time'] = $item['add_time'] ? date('Y-m-d H:i:s', $item['add_time']) : '暂无';
                $item['paid_type'] = $item['paid'] ? '已支付' : '未支付';

                $export[] = [
                    $item['nickname'],
                    $item['order_id'],
                    $item['price'],
                    $item['paid_type'],
                    $item['_recharge_type'],
                    $item['_pay_time'],
                    $item['paid'] == 1 && $item['refund_price'] == $item['price'] ? '已退款' : '未退款'
                ];
            }
        }
        $header = ['昵称/姓名', '订单号', '充值金额', '是否支付', '充值类型', '支付时间', '是否退款'];
        $title = ['充值记录', '充值记录' . time(), ' 生成时间：' . date('Y-m-d H:i:s', time())];
        $filename = '充值记录_' . date('YmdHis', time());
        $suffix = 'xlsx';
        $is_save = true;
        return $this->export($header, $title, $export, $filename, $suffix, $is_save);
    }

    /**
     * 用户推广导出
     * @param $data 导出数据
     */
    public function userAgent($data = [])
    {
        $export = [];
        if (!empty($data)) {
            foreach ($data as $index => $item) {
                $export[] = [
                    $item['uid'],
                    $item['nickname'],
                    $item['phone'],
                    $item['spread_count'],
                    $item['spread_order']['order_count'],
                    $item['spread_order']['order_price'],
                    $item['brokerage_money'],
                    $item['extract_count_price'],
                    $item['extract_count_num'],
                    $item['brokerage_price'],
                    $item['spread_name'],
                ];
            }
        }
        $header = ['用户编号', '昵称', '电话号码', '推广用户数量', '推广订单数量', '推广订单金额', '佣金金额', '已提现金额', '提现次数', '未提现金额', '上级推广人'];
        $title = ['推广用户', '推广用户导出' . time(), ' 生成时间：' . date('Y-m-d H:i:s', time())];
        $filename = '推广用户_' . date('YmdHis', time());
        $suffix = 'xlsx';
        $is_save = true;
        return $this->export($header, $title, $export, $filename, $suffix, $is_save);
    }

    /**
     * 微信用户导出
     * @param $data 导出数据
     */
    public function wechatUser($data = [])
    {
        $export = [];
        if (!empty($data)) {
            foreach ($data as $index => $item) {
                $export[] = [
                    $item['nickname'],
                    $item['sex'],
                    $item['country'] . $item['province'] . $item['city'],
                    $item['subscribe'] == 1 ? '关注' : '未关注',
                ];
            }
        }
        $header = ['名称', '性别', '地区', '是否关注公众号'];
        $title = ['微信用户导出', '微信用户导出' . time(), ' 生成时间：' . date('Y-m-d H:i:s', time())];
        $filename = '微信用户导出_' . date('YmdHis', time());
        $suffix = 'xlsx';
        $is_save = true;
        return $this->export($header, $title, $export, $filename, $suffix, $is_save);
    }

    /**
     * 订单资金导出
     * @param $data 导出数据
     */
    public function orderFinance($data = [])
    {
        $export = [];
        if (!empty($data)) {
            foreach ($data as $info) {
                $time = $info['pay_time'];
                $price = $info['total_price'] + $info['pay_postage'];
                $zhichu = $info['coupon_price'] + $info['deduction_price'] + $info['cost'];
                $profit = ($info['total_price'] + $info['pay_postage']) - ($info['coupon_price'] + $info['deduction_price'] + $info['cost']);
                $deduction = $info['deduction_price'];//积分抵扣
                $coupon = $info['coupon_price'];//优惠
                $cost = $info['cost'];//成本
                $export[] = [$time, $price, $zhichu, $cost, $coupon, $deduction, $profit];
            }
        }
        $header = ['时间', '营业额(元)', '支出(元)', '成本', '优惠', '积分抵扣', '盈利(元)'];
        $title = ['财务统计', '财务统计', date('Y-m-d H:i:s', time())];
        $filename = '财务统计_' . date('YmdHis', time());
        $suffix = 'xlsx';
        $is_save = true;
        return $this->export($header, $title, $export, $filename, $suffix, $is_save);
    }

    /**
     * 商铺砍价活动导出
     * @param $data 导出数据
     */
    public function storeBargain($data = [])
    {
        $export = [];
        if (!empty($data)) {
            foreach ($data as $index => $item) {
                $export[] = [
                    $item['title'],
                    $item['info'],
                    '￥' . $item['price'],
                    $item['bargain_num'],
                    $item['status'] ? '开启' : '关闭',
                    empty($item['start_time']) ? '' : date('Y-m-d H:i:s', (int)$item['start_time']),
                    empty($item['stop_time']) ? '' : date('Y-m-d H:i:s', (int)$item['stop_time']),
                    $item['sales'],
                    $item['quota'],
                    empty($item['add_time']) ? '' : $item['add_time'],
                ];
            }
        }
        $header = ['砍价活动名称', '砍价活动简介', '砍价金额', '用户每次砍价的次数', '砍价状态', '砍价开启时间', '砍价结束时间', '销量', '限量', '添加时间'];
        $title = ['砍价商品导出', '商品信息' . time(), ' 生成时间：' . date('Y-m-d H:i:s', time())];
        $filename = '砍价商品导出_' . date('YmdHis', time());
        $suffix = 'xlsx';
        $is_save = true;
        return $this->export($header, $title, $export, $filename, $suffix, $is_save);
    }

    /**
     * 商铺拼团导出
     * @param $data 导出数据
     */
    public function storeCombination($data = [])
    {
        $export = [];
        if (!empty($data)) {
            foreach ($data as $item) {
                $export[] = [
                    $item['id'],
                    $item['title'],
                    $item['ot_price'],
                    $item['price'],
                    $item['quota'],
                    $item['count_people'],
                    $item['count_people_all'],
                    $item['count_people_pink'],
                    $item['sales'] ?? 0,
                    $item['is_show'] ? '开启' : '关闭',
                    empty($item['stop_time']) ? '' : date('Y/m/d H:i:s', (int)$item['stop_time'])
                ];
            }
        }
        $header = ['编号', '拼团名称', '原价', '拼团价', '限量', '拼团人数', '参与人数', '成团数量', '销量', '商品状态', '结束时间'];
        $title = ['拼团商品导出', '商品信息' . time(), ' 生成时间：' . date('Y-m-d H:i:s', time())];
        $filename = '拼团商品导出_' . date('YmdHis', time());
        $suffix = 'xlsx';
        $is_save = true;
        return $this->export($header, $title, $export, $filename, $suffix, $is_save);
    }

    /**
     * 商铺秒杀活动导出
     * @param $data 导出数据
     */
    public function storeSeckill($data = [])
    {
        $export = [];
        if (!empty($data)) {
            foreach ($data as $item) {
                if ($item['status']) {
                    if ($item['start_time'] > time())
                        $item['start_name'] = '活动未开始';
                    else if ($item['stop_time'] < time())
                        $item['start_name'] = '活动已结束';
                    else if ($item['stop_time'] > time() && $item['start_time'] < time())
                        $item['start_name'] = '正在进行中';
                } else {
                    $item['start_name'] = '活动已结束';
                }
                $export[] = [
                    $item['id'],
                    $item['title'],
                    $item['info'],
                    $item['ot_price'],
                    $item['price'],
                    $item['quota'],
                    $item['sales'],
                    $item['start_name'],
                    $item['stop_time'] ? date('Y-m-d H:i:s', $item['stop_time']) : '/',
                    $item['status'] ? '开启' : '关闭',
                ];
            }
        }
        $header = ['编号', '活动标题', '活动简介', '原价', '秒杀价', '限量', '销量', '秒杀状态', '结束时间', '状态'];
        $title = ['秒杀商品导出', ' ', ' 生成时间：' . date('Y-m-d H:i:s', time())];
        $filename = '秒杀商品导出_' . date('YmdHis', time());
        $suffix = 'xlsx';
        $is_save = true;
        return $this->export($header, $title, $export, $filename, $suffix, $is_save);
    }

    /**
     * 商铺商品导出
     * @param $data 导出数据
     */
    public function storeProduct($data = [])
    {
        $export = [];
        if (!empty($data)) {
            foreach ($data as $index => $item) {
                $export[] = [
                    $item['store_name'],
                    $item['store_info'],
                    $item['cate_name'],
                    '￥' . $item['price'],
                    $item['stock'],
                    $item['sales'],
                    $item['visitor'],
                ];
            }
        }
        $header = ['商品名称', '商品简介', '商品分类', '价格', '库存', '销量', '浏览量'];
        $title = ['商品导出', '商品信息' . time(), ' 生成时间：' . date('Y-m-d H:i:s', time())];
        $filename = '商品导出_' . date('YmdHis', time());
        $suffix = 'xlsx';
        $is_save = true;
        return $this->export($header, $title, $export, $filename, $suffix, $is_save);
    }


    /**
     * 商铺自提点导出
     * @param $data 导出数据
     */
    public function storeMerchant($data = [])
    {
        $export = [];
        if (!empty($data)) {
            foreach ($data as $index => $item) {
                $export[] = [
                    $item['name'],
                    $item['phone'],
                    $item['address'] . '' . $item['detailed_address'],
                    $item['day_time'],
                    $item['is_show'] ? '开启' : '关闭'
                ];
            }
        }
        $header = ['提货点名称', '提货点', '地址', '营业时间', '状态'];
        $title = ['提货点导出', '提货点信息' . time(), ' 生成时间：' . date('Y-m-d H:i:s', time())];
        $filename = '提货点导出_' . date('YmdHis', time());
        $suffix = 'xlsx';
        $is_save = true;
        return $this->export($header, $title, $export, $filename, $suffix, $is_save);
    }

    public function memberCard($data = [])
    {
        $export = [];
        if (!empty($data)) {
            foreach ($data['data'] as $index => $item) {
                $export[] = [
                    $item['card_number'],
                    $item['card_password'],
                    $item['user_name'],
                    $item['user_phone'],
                    $item['use_time'],
                    $item['use_uid'] ? '已领取' : '未领取'
                ];
            }
        }
        $header = ['会员卡号', '密码', '领取人', '领取人手机号', '领取时间', '是否使用'];
        $title = ['会员卡导出', '会员卡导出' . time(), ' 生成时间：' . date('Y-m-d H:i:s', time())];
        $filename = $data['title'] ? ("卡密会员_" . trim(str_replace(["\r\n", "\r", "\\", "\n", "/", "<", ">", "=", " "], '', $data['title']))) : "";
        $suffix = 'xlsx';
        $is_save = true;
        return $this->export($header, $title, $export, $filename, $suffix, $is_save);
    }

    public function tradeData($data = [], $tradeTitle = "交易统计")
    {
        $export = $header = [];
        if (!empty($data)) {
            $header = ['时间'];
            $headerArray = array_column($data['series'], 'name');
            $header = array_merge($header, $headerArray);
            $export = [];
            foreach ($data['series'] as $index => $item) {
                foreach ($data['x'] as $k => $v) {
                    $export[$v]['time'] = $v;
                    $export[$v][] = $item['value'][$k];
                }
            }
        }
        $title = [$tradeTitle, $tradeTitle, ' 生成时间：' . date('Y-m-d H:i:s', time())];
        $filename = $tradeTitle;
        $suffix = 'xlsx';
        $is_save = true;
        return $this->export($header, $title, $export, $filename, $suffix, $is_save);
    }


    /**
     * 商品统计
     * @param $data 导出数据
     */
    public function productTrade($data = [])
    {
        $export = [];
        if (!empty($data)) {
            foreach ($data as &$value) {
                $export[] = [
                    $value['time'],
                    $value['browse'],
                    $value['user'],
                    $value['cart'],
                    $value['order'],
                    $value['payNum'],
                    $value['pay'],
                    $value['cost'],
                    $value['refund'],
                    $value['refundNum'],
                    $value['changes'] . '%'
                ];
            }
        }
        $header = ['日期/时间', '商品浏览量', '商品访客数', '加购件数', '下单件数', '支付件数', '支付金额', '成本金额', '退款金额', '退款件数', '访客-支付转化率'];
        $title = ['商品统计', '商品统计' . time(), ' 生成时间：' . date('Y-m-d H:i:s', time())];
        $filename = '商品统计_' . date('YmdHis', time());
        $suffix = 'xlsx';
        $is_save = true;
        return $this->export($header, $title, $export, $filename, $suffix, $is_save);
    }

    public function userTrade($data = [])
    {
        $export = [];
        if (!empty($data)) {
            foreach ($data as &$value) {
                $export[] = [
                    $value['time'],
                    $value['user'],
                    $value['browse'],
                    $value['new'],
                    $value['paid'],
                    $value['vip'],
                ];
            }
        }
        $header = ['日期/时间', '访客数', '浏览量', '新增用户数', '成交用户数', '付费会员数'];
        $title = ['用户统计', '用户统计' . time(), ' 生成时间：' . date('Y-m-d H:i:s', time())];
        $filename = '用户统计_' . date('YmdHis', time());
        $suffix = 'xlsx';
        $is_save = true;
        return $this->export($header, $title, $export, $filename, $suffix, $is_save);
    }
}
