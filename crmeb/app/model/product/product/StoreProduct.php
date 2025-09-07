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

namespace app\model\product\product;

use app\model\product\sku\StoreProductAttrValue;
use crmeb\basic\BaseModel;
use crmeb\traits\ModelTrait;
use think\Model;

/**
 *  商品Model
 * Class StoreProduct
 * @package app\model\product\product
 */
class StoreProduct extends BaseModel
{
    use  ModelTrait;

    /**
     * 数据表主键
     * @var string
     */
    protected $pk = 'id';

    /**
     * 模型名称
     * @var string
     */
    protected $name = 'store_product';

    /**
     * 一对一关联
     * 商品关联商品商品详情
     * @return \think\model\relation\HasOne
     */
    public function description()
    {
        return $this->hasOne(StoreDescription::class, 'product_id', 'id')->where('type', 0)->bind(['description']);
    }

    /**
     * 一对多关联
     * 商品关联优惠卷模板id
     * @return \think\model\relation\HasMany
     */
    public function couponId()
    {
        return $this->hasMany(StoreProductCoupon::class, 'product_id', 'id');
    }

    /**
     * 优惠券名称一对多
     * @return \think\model\relation\HasMany
     */
    public function coupons()
    {
        return $this->hasMany(StoreProductCoupon::class, 'product_id', 'id');
    }

    /**
     * 评论一对多
     * @return \think\model\relation\HasMany
     */
    public function star()
    {
        return $this->hasMany(StoreProductReply::class, 'product_id', 'id')->where('is_del', 0)->field('product_score,product_id');
    }

    /**
     * 分类一对多
     * @return \think\model\relation\HasMany
     */
    public function cateName()
    {
        return $this->hasMany(StoreProductCate::class, 'product_id', 'id')->with('cateName');
    }

    public function attrs()
    {
        return $this->hasMany(StoreProductAttrValue::class, 'product_id', 'id')->where('type', 0);
    }


    /**
     * 轮播图获取器
     * @param $value
     * @return array|mixed
     */
    public function getSliderImageAttr($value)
    {
        return is_string($value) ? json_decode($value, true) : [];
    }

    /**
     * 是否显示搜索器
     * @param $query
     * @param $value
     */
    public function searchIsShowAttr($query, $value)
    {
        if ($value != -1) $query->where('is_show', $value ?? 1);
    }

    /**
     * @param Model $query
     * @param $value
     */
    public function searchIdAttr($query, $value)
    {
        if (is_array($value)) {
            $query->whereIn('id', $value);
        } else {
            $query->where('id', $value);
        }
    }

    /**
     * 是否删除搜索器
     * @param Model $query
     * @param $value
     */
    public function searchIsDelAttr($query, $value)
    {
        $query->where('is_del', $value ?: 0);
    }

    /**
     * 商户ID搜索器
     * @param Model $query
     * @param $value
     */
    public function searchMerIdAttr($query, $value)
    {
        $query->where('mer_id', $value ?? 0);
    }

    /**
     * keyword搜索器
     * @param Model $query
     * @param $value
     * @param $data
     */
    public function searchStoreNameAttr($query, $value, $data)
    {
        if ($value != '') {
            $field = 'keyword|store_name|store_info|id';
            if (is_string($value)) {
                $query->whereLike($field, htmlspecialchars("%" . trim($value) . "%"));
            } elseif (is_array($value) && count($value) > 0) {
                $query->where(function ($q) use ($value, $field) {
                    $data = [];
                    foreach ($value as $k) {
                        $data[] = [$field, 'like', "%" . trim($k) . "%"];
                    }
                    $q->whereOr($data);
                });
            }
        }
    }

    /**
     * 新品商品搜索器
     * @param Model $query
     * @param int $value
     */
    public function searchIsNewAttr($query, $value)
    {
        if ($value) $query->where('is_new', $value);
    }

    /**
     * 优惠商品搜索器
     * @param Model $query
     * @param int $value
     */
    public function searchIsBenefitAttr($query, $value)
    {
        $query->where('is_benefit', $value ?? 1);
    }

    /**
     * 热卖商品搜索器
     * @param Model $query
     * @param int $value
     */
    public function searchIsHotAttr($query, $value)
    {
        $query->where('is_hot', $value ?? 1);
    }

    /**
     * 精品商品搜索器
     * @param Model $query
     * @param int $value
     */
    public function searchIsBestAttr($query, $value)
    {
        $query->where('is_best', $value ?? 1);
    }

    /**
     * 精品商品搜索器
     * @param Model $query
     * @param int $value
     */
    public function searchIsGoodAttr($query, $value)
    {
        $query->where('is_good', $value ?? 1);
    }

    /**
     * 标签商品搜索器
     * @param Model $query
     * @param int $value
     */
    public function searchLabelIdAttr($query, $value)
    {
        $query->whereFindInSet('label_id', $value);
    }

    /**
     * SPU搜索器
     * @param Model $query
     * @param int $value
     */
    public function searchSpuAttr($query, $value)
    {
        $query->where('spu', $value);
    }

    /**
     * 库存搜索器
     * @param Model $query
     * @param int $value
     */
    public function searchStockAttr($query, $value)
    {
        $query->where('stock', $value);
    }

    /**
     * 会员专属商品搜索器
     * @param Model $query
     * @param int $value
     */
    public function searchVipUserAttr($query, $value)
    {
        if ($value === 0) {
            $query->where('vip_product', 0);
        }
    }

    /**
     * 是否虚拟商品搜索器
     * @param $query
     * @param $value
     */
    public function searchIsVirtualAttr($query, $value)
    {
        if ($value == 0) {
            $query->where('virtual_type', 0)->where('vip_product', 0)->where('presale', 0);
        }
    }

    /**
     * 是否预售商品
     * @param $query
     * @param $value
     */
    public function searchIsPresaleAttr($query, $value)
    {
        if ($value >= 0) {
            $query->where('presale', $value);
        }
    }

    /**
     * 分类搜索器
     * @param Model $query
     * @param int $value
     */
    public function searchCateIdAttr($query, $value)
    {
        if ($value) {
            if (is_array($value)) {
                $query->whereIn('id', function ($query) use ($value) {
                    $query->name('store_product_cate')->where('cate_id', 'IN', $value)->whereOr('cate_pid', 'IN', $value)->field('product_id')->select();
                });
            } else {
                $query->whereFindInSet('cate_id', $value);
            }
        }
    }

    /**
     * 商品数量条件搜索器
     * @param Model $query
     * @param $value
     * @param $data
     */
    public function searchTypeAttr($query, $value, $data)
    {
        switch ((int)$value) {
            case 1:
                $query->where(['is_show' => 1, 'is_del' => 0]);
                break;
            case 2:
                $query->where(['is_show' => 0, 'is_del' => 0]);
                break;
            case 3:
                $query->where(['is_del' => 0, 'vip_product' => 0]);
                break;
            case 4:
                $query->where(['is_del' => 0])->where(function ($query) {
                    $query->whereIn('id', function ($query) {
                        $query->name('store_product_attr_value')->where('stock', 0)->where('type', 0)->field('product_id')->select();
                    })->whereOr('stock', 0);
                });
                break;
            case 5:
                if (isset($data['store_stock']) && $data['store_stock']) {
                    $store_stock = $data['store_stock'];
                    $query->whereIn('id', function ($query) use ($store_stock) {
                        $query->name('store_product_attr_value')->where('stock', '<', $store_stock)->where('stock', '>', 0)->where('type', 0)->field('product_id')->select();
                    });
                } else {
                    $query->where(['is_show' => 1, 'is_del' => 0])->where('stock', '>', 0);
                }
                break;
            case 6:
                $query->where(['is_del' => 1]);
                break;
            case 7:
                $query->where(['is_del' => 0, 'vip_product' => 0, 'virtual_type' => 0]);
                break;
        }
    }

    /**
     * 在当前id中查询
     * @param $query
     * @param $value
     */
    public function searchIdsAttr($query, $value)
    {
        if (is_string($value)) {
            if ($value !== '') {
                $value = explode(',', $value);
            } else {
                $value = [];
            }
        }
        if (count($value)) $query->whereIn('id', $value);
    }

    /**
     * 不在当前id中查询
     * @param $query
     * @param $value
     */
    public function searchNotIdsAttr($query, $value)
    {
        if ($value != '') $query->whereNotIn('id', $value);
    }

    /**
     * 自定义表单搜索器
     * @param $query
     * @param $value
     * @author wuhaotian
     * @email 442384644@qq.com
     * @date 2025/1/14
     */
    public function searchCustomFormAttr($query, $value)
    {
        if ($value !== '') $query->whereLike('custom_form', '%' . $value . '%');
    }

    /**
     * 虚拟类型搜索器
     * @param $query
     * @param $value
     * @author wuhaotian
     * @email 442384644@qq.com
     * @date 2025/1/14
     */
    public function searchVirtualTypeAttr($query, $value)
    {
        if ($value !== '') $query->where('virtual_type', $value);
    }

    /**
     * 规格类型搜索器
     * @param $query
     * @param $value
     * @author wuhaotian
     * @email 442384644@qq.com
     * @date 2025/1/14
     */
    public function searchSpecTypeAttr($query, $value)
    {
        if ($value !== '') $query->where('spec_type', $value);
    }

    /**
     * 是否礼品搜索器
     * @param $query
     * @param $value
     * @author wuhaotian
     * @email 442384644@qq.com
     * @date 2025/1/14
     */
    public function searchIsGiftAttr($query, $value)
    {
        if ($value !== '') $query->where('is_gift', $value);
    }

    /**
     * 会员专属商品搜索器
     * @param $query
     * @param $value
     * @author wuhaotian
     * @email 442384644@qq.com
     * @date 2025/1/14
     */
    public function searchVipProductAttr($query, $value)
    {
        if ($value !== '') $query->where('vip_product', $value);
    }

    /**
     * 价格区间搜索器
     * @param $query
     * @param $value
     * @author wuhaotian
     * @email 442384644@qq.com
     * @date 2025/1/14
     */
    public function searchPriceSAttr($query, $value)
    {
        if (count($value) == 2 && ($value[0] !== '' || $value[1] !== '')) {
            if ($value[0] !== '' && $value[1] !== '') {
                $query->whereBetween('price', [$value[0], $value[1]]);
            } elseif ($value[0] !== '') {
                $query->where('price', '>=', $value[0]);
            } elseif ($value[1] !== '') {
                $query->where('price', '<=', $value[1]);
            }
        }
    }

    /**
     * 库存区间搜索器
     * @param $query
     * @param $value
     * @author wuhaotian
     * @email 442384644@qq.com
     * @date 2025/1/14
     */
    public function searchStockSAttr($query, $value)
    {
        if (count($value) == 2 && ($value[0] !== '' || $value[1] !== '')) {
            if ($value[0] !== '' && $value[1] !== '') {
                $query->whereBetween('stock', [$value[0], $value[1]]);
            } elseif ($value[0] !== '') {
                $query->where('stock', '>=', $value[0]);
            } elseif ($value[1] !== '') {
                $query->where('stock', '<=', $value[1]);
            }
        }
    }

    /**
     * 销量区间搜索器
     * @param $query
     * @param $value
     * @author wuhaotian
     * @email 442384644@qq.com
     * @date 2025/1/14
     */
    public function searchSalesSAttr($query, $value)
    {
        if (count($value) == 2 && ($value[0] !== '' || $value[1] !== '')) {
            if ($value[0] !== '' && $value[1] !== '') {
                $query->whereBetween('sales', [$value[0], $value[1]]);
            } elseif ($value[0] !== '') {
                $query->where('sales', '>=', $value[0]);
            } elseif ($value[1] !== '') {
                $query->where('sales', '<=', $value[1]);
            }
        }
    }

    /**
     * 标签搜索器
     * @param $query
     * @param $value
     * @author wuhaotian
     * @email 442384644@qq.com
     * @date 2025/2/19
     */
    public function searchStoreLabelIdAttr($query, $value)
    {
        if (count($value)) {
            $query->where(function ($query) use ($value) {
                foreach ($value as $item) {
                    $query->whereOr('FIND_IN_SET(:value, label_list)', ['value' => $item]);
                }
            });
        }
    }

    /**
     * 配送方式搜索器
     * @param $query
     * @param $value
     * @author wuhaotian
     * @email 442384644@qq.com
     * @date 2025/2/19
     */
    public function searchLogisticsAttr($query, $value)
    {
        if ($value !== '') $query->whereFindInSet('logistics', $value);
    }

    /**
     * 商品类型搜索器
     * @param $query
     * @param $value
     * @author wuhaotian
     * @email 442384644@qq.com
     * @date 2025/2/19
     */
    public function searchVirtualeTypeAttr($query, $value)
    {
        if ($value !== '') $query->where('virtual_type', $value);
    }


}
