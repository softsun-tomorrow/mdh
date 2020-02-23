<?php

return
[
    'order_sn' => '订单号',
    'master_order_sn' => '主订单号',
    'user_id' => '用户',
    'order_status' => '订单状态', //订单状态.0待确认，1已确认，2已收货，3已取消，4已完成，5已作废
    'shipping_status' => '发货状态',//发货状态:0=未发货，1=已发货，2=部分发货
    'pay_status' => '支付状态',//支付状态.0待支付，1已支付，2支付失败，3已退款，4拒绝退款,5已取消
    'province_id' => '收货人',
    'city_id' => '',
    'district_id' => '',
    'address' => '详细地址',
    'mobile' => '联系电话',
    'pay_type' => '支付方式',//支付方式:0=会员卡,1=支付宝,2=微信
    'goods_price' => '商品总价',
    'order_amount' => '应付款金额',
    'created_at' => '下单时间',
    'pay_time' => '支付时间',
    'confirm_time' => '收货确认时间',
    'transaction_id' => '第三方支付流水号',
    'user_note' => '用户备注',
    'admin_note' => '管理员备注',
    'store_id' => '店铺',
    'deleted_at' => '',
    'area' => '省市区',
    'shipping_code' => '配送方式编号',//配送方式编号：ems=快递配送，same_city=同城配送，in_shop=到点自取
    'shipping_name' => '配送方式名称',
    'shipping_price' => '邮费',
    'user_account' => '使用麦穗',
    'user_account_money' => '麦穗抵扣金额',
    'coupon_price' => '优惠金额',
    'total_amount' => '订单总价',
    'shipping_time' => '发货时间',
    'is_comment' => '是否评价',//是否评价（0：未评价；1：已评价）
    'order_prom_id' => '',
    'order_prom_type' => '',
    'order_prom_amount' => '',
    'coupon_ids' => '',
    'shipper_code' => '快递公司编码',
    'logistic_code' => '物流单号',
    'shipper_name' => '快递名称',
    'consignee' => '收货人姓名',
    'pick_code' => '取货码',
    'order_prom_type' => '活动类型'

];