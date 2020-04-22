SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE `app_counters` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `counter` int(11) NOT NULL COMMENT '收款二维码编号',
  `mch_id` int(11) NOT NULL COMMENT '微信支付商户号',
  `shop_id` int(11) NOT NULL DEFAULT '0' COMMENT '门店ID',
  `tenpay_shopid` varchar(32) NOT NULL COMMENT '腾讯云支付SHOPID',
  `merchant_name` varchar(50) NOT NULL COMMENT '商户品牌名简称',
  `branch_name` varchar(50) NOT NULL COMMENT '商户分店名称',
  `counter_type` enum('self','scan') DEFAULT 'scan' COMMENT '默认扫码付款，self代表小程序内自助买单',
  `name` varchar(32) NOT NULL COMMENT '收款识别的名称',
  `qrcode_url` varchar(255) NOT NULL COMMENT '收款码URL',
  `wxcode_url` varchar(255) NOT NULL COMMENT '收款小程序码RUL，已弃用',
  `cloud_device` varchar(32) NOT NULL COMMENT '飞鱼云喇叭设备号',
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `c` (`counter`)
) ENGINE=InnoDB AUTO_INCREMENT=86 DEFAULT CHARSET=utf8;

CREATE TABLE `app_grades` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '会员等级表自增ID',
  `mch_id` int(11) NOT NULL COMMENT '商户号',
  `name` varchar(32) NOT NULL COMMENT '会员等级名称',
  `condition` varchar(255) NOT NULL COMMENT '升级会员等级条件文字描述',
  `catch_type` enum('pay','recharge','frequency','amount','scan') DEFAULT 'scan' COMMENT '升级会员等级条件，pay=>付费升级，recharge=>储值升级,frequency=>累积消费次数升级，amount=>累积消费金额升级，scan=>扫码注册升级，默认为扫码开卡',
  `catch_value` int(11) NOT NULL DEFAULT '0' COMMENT '达到条件的值',
  `privilege` varchar(255) NOT NULL COMMENT '权益',
  `grade` tinyint(1) NOT NULL DEFAULT '1' COMMENT '等级',
  `valid_days` int(5) NOT NULL DEFAULT '0' COMMENT '有效期，0表示永久有效',
  `discount` float(4,1) NOT NULL DEFAULT '0.0' COMMENT '享受折扣',
  `point_speed` float(2,1) NOT NULL DEFAULT '1.0' COMMENT '积分加速',
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=102 DEFAULT CHARSET=utf8;

CREATE TABLE `app_index_pics` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '会员小程序首页顶部轮播图自增ID',
  `mch_id` int(11) NOT NULL COMMENT '商户微信商户号',
  `pic_url` varchar(255) NOT NULL COMMENT '图上URL',
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `app_opengifts` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '商户会员开卡礼自增ID',
  `mch_id` int(11) NOT NULL COMMENT '商户微信支付商户号',
  `grade` int(11) NOT NULL DEFAULT '1' COMMENT '商户会员等级',
  `coupon_id` int(11) NOT NULL COMMENT '优惠券ID',
  `coupon_name` varchar(100) NOT NULL COMMENT '优惠券名称',
  `coupon_total` int(2) NOT NULL COMMENT '优惠券数量',
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=130 DEFAULT CHARSET=utf8;

CREATE TABLE `app_payed_gifts` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '此表已弃用',
  `mch_id` int(11) NOT NULL,
  `consume` int(5) NOT NULL DEFAULT '0',
  `coupon_id` int(11) NOT NULL,
  `coupon_name` varchar(32) NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

CREATE TABLE `app_payed_shares` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '支付裂变券活动列表自增ID',
  `mch_id` int(11) NOT NULL COMMENT '微信支付商户号',
  `coupon_id` int(11) NOT NULL COMMENT '优惠券ID',
  `coupon_name` varchar(32) NOT NULL COMMENT '优惠券名称',
  `percent` int(2) NOT NULL DEFAULT '0' COMMENT '抢中机率',
  `coupon_stock_id` int(11) NOT NULL COMMENT '已弃用',
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8;

CREATE TABLE `app_point_exchange_rules` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '商户积分兑换表自增ID',
  `mch_id` int(32) NOT NULL COMMENT '商户微信支付商户号',
  `point` int(11) NOT NULL COMMENT '积分数量',
  `is_limit` tinyint(1) NOT NULL DEFAULT '0' COMMENT '兑换是否有限制',
  `exchange_limit` int(11) NOT NULL DEFAULT '99999' COMMENT '总兑换份数',
  `single_limit` int(11) NOT NULL DEFAULT '0' COMMENT '每人最多可兑换',
  `coupon_id` int(11) NOT NULL COMMENT '优惠券ID',
  `card_id` varchar(32) NOT NULL COMMENT '微信卡券ID',
  `coupon_name` varchar(100) NOT NULL COMMENT '优惠券名称',
  `exchanged` int(11) NOT NULL DEFAULT '0' COMMENT '已兑换数量',
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `aid` (`mch_id`)
) ENGINE=InnoDB AUTO_INCREMENT=136 DEFAULT CHARSET=utf8;

CREATE TABLE `app_point_rules` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '商户积分权益配置表自增ID',
  `mch_id` int(11) NOT NULL COMMENT '微信商户号',
  `award_need_consume` int(11) NOT NULL DEFAULT '1' COMMENT '返1积分所需消费金额',
  `can_used_for_money` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否可抵扣现金使用',
  `exchange_need_points` int(11) NOT NULL COMMENT '多少积分可抵扣一元现金',
  `recharge_point_speed` float(3,1) NOT NULL DEFAULT '0.0' COMMENT '储值消费返积分加速倍数',
  `updated_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `aid` (`mch_id`)
) ENGINE=InnoDB AUTO_INCREMENT=57 DEFAULT CHARSET=utf8;

CREATE TABLE `app_recharge_coupon_rules` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '商户储值赠券活动表',
  `mch_id` int(11) NOT NULL COMMENT '微信支付商户号',
  `recharge_id` int(11) NOT NULL COMMENT '储值活动ID',
  `touch` int(11) NOT NULL COMMENT '储值金额',
  `coupon_id` int(11) NOT NULL COMMENT '赠送优惠券ID',
  `coupon_name` varchar(50) NOT NULL COMMENT '优惠券名称',
  `total` int(11) NOT NULL COMMENT '赠送张数',
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8;

CREATE TABLE `app_recharge_rules` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '商户储值活动表ID',
  `mch_id` int(11) NOT NULL COMMENT '微信支付商户号',
  `touch` int(11) DEFAULT NULL COMMENT '储值达到多少钱',
  `award_type` enum('money_constant','money_percent','coupon') DEFAULT NULL COMMENT '储值奖励类型,money_constant=>奖励固定金额,money_percent=>奖励百分比，coupon=>奖励优惠券',
  `amount` int(11) DEFAULT NULL COMMENT '奖励金额',
  `percent` int(11) DEFAULT NULL COMMENT '奖励百分比',
  `remark` varchar(255) NOT NULL COMMENT '储值说明',
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `aid` (`mch_id`)
) ENGINE=InnoDB AUTO_INCREMENT=57 DEFAULT CHARSET=utf8;

CREATE TABLE `app_subscribe_list` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '商户独立小程序订阅消息表',
  `mch_id` int(11) NOT NULL,
  `subscribe_type` varchar(32) NOT NULL COMMENT '订阅消息类型',
  `tid` int(5) NOT NULL COMMENT '订阅消息tid',
  `title` varchar(100) NOT NULL COMMENT '模板名称',
  `template_id` varchar(80) NOT NULL COMMENT '模板id',
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8;

CREATE TABLE `app_wechat_groups` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '商户微信群',
  `mch_id` int(11) NOT NULL COMMENT '商户号',
  `media` varchar(255) NOT NULL COMMENT '微信群图片素材的media_id',
  `guide` varchar(255) NOT NULL COMMENT '入群说明',
  `expire_at` date NOT NULL COMMENT '群二维码过期时间',
  `updated_at` datetime NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8;

CREATE TABLE `app_wechat_pays` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '商户购买短信费订单表自增ID',
  `appid` varchar(32) NOT NULL COMMENT '购买短信费用的下单小程序appid',
  `mch_id` int(11) NOT NULL COMMENT '微信支付商户号',
  `openid` varchar(32) NOT NULL COMMENT '下单小程序openid',
  `out_trade_no` varchar(32) NOT NULL COMMENT '商户订单号',
  `transaction_id` varchar(50) NOT NULL COMMENT '微信支付订单号',
  `prepay_id` varchar(50) NOT NULL COMMENT '微信支付prepay_id',
  `bank` varchar(32) NOT NULL COMMENT '银行',
  `trade` float(6,2) NOT NULL COMMENT '订单金额-元',
  `total_fee` int(11) NOT NULL COMMENT '订单金额-分',
  `service_fee` float(6,2) NOT NULL COMMENT '手续费-分',
  `detail` varchar(255) NOT NULL COMMENT '订单详情',
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tid` (`transaction_id`) USING BTREE,
  KEY `soid` (`openid`)
) ENGINE=InnoDB AUTO_INCREMENT=51 DEFAULT CHARSET=utf8;

CREATE TABLE `apps` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '商户独立营销小程序表自增ID',
  `user_mch_submit_id` int(11) NOT NULL DEFAULT '0' COMMENT '申请表ID',
  `appid` varchar(32) NOT NULL COMMENT '小程序appid',
  `open_appid` varchar(32) NOT NULL COMMENT '开放平台账号',
  `mch_id` int(11) NOT NULL COMMENT '商户号',
  `nickname` varchar(100) NOT NULL COMMENT '小程序昵称',
  `head_img` varchar(255) NOT NULL COMMENT '小程序头像',
  `service_type_info` tinyint(1) NOT NULL COMMENT '授权方公众号类型，0代表订阅号，1代表由历史老帐号升级后的订阅号，2代表服务号\n',
  `verify_type_info` tinyint(1) NOT NULL,
  `user_name` varchar(100) NOT NULL COMMENT '授权方公众号的原始ID\n',
  `principal_name` varchar(32) NOT NULL COMMENT '公众号的主体名称',
  `alias` varchar(32) NOT NULL COMMENT '授权方公众号所设置的微信号，可能为空\n',
  `open_store` tinyint(1) DEFAULT '1',
  `open_pay` tinyint(1) DEFAULT '1',
  `open_card` tinyint(1) DEFAULT '1',
  `qrcode_url` varchar(255) NOT NULL,
  `func_info` varchar(255) NOT NULL,
  `cate_first` int(5) NOT NULL COMMENT '小程序一级类目ID',
  `cate_second` int(5) NOT NULL COMMENT '小程序二级类目ID',
  `first_class` varchar(50) NOT NULL COMMENT '小程序一级类目名称',
  `second_class` varchar(50) NOT NULL COMMENT '小程序二级类目名称',
  `legal_persona_wechat` varchar(32) NOT NULL COMMENT '法人微信号',
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8;

CREATE TABLE `campaigns` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '商户营销活动表自增ID',
  `mch_id` int(32) NOT NULL COMMENT '微信支付商户号',
  `grade` int(11) NOT NULL DEFAULT '0' COMMENT '会员等级',
  `title` varchar(100) NOT NULL COMMENT '活动名称',
  `campaign_type` enum('send_coupon','rebate','point','reduce','discount','opengift','send_sms','member_day','wakeup','pay_gift','payed_share','lbs_coupon','paybuycoupon','rechargenopay','waimai_reduce') NOT NULL COMMENT 'send_coupon群发优惠券，rebate消费返券，point积分加速， reduce消费立减，discount消费折扣，opengift开卡礼，send_sms群发短信，memberday会员日，wakeup沉睡唤醒，pay_gift支付裂变券，pay_buycoupon支付加价购券，rechargenopay激励储值，waimai_reduce外卖消费立减',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '活动状态',
  `coupon_id` int(11) NOT NULL COMMENT '优惠券ID',
  `coupon_total` int(1) NOT NULL DEFAULT '0' COMMENT '优惠券张数',
  `send_at` datetime NOT NULL COMMENT '活动发送时间',
  `date_start` date NOT NULL COMMENT '活动开始日期',
  `date_end` date NOT NULL COMMENT '活动结束日期',
  `day` int(1) NOT NULL COMMENT '周几会员日',
  `award_condition` enum('ge','egt') NOT NULL COMMENT 'ge=>每满\negt=>大于等于',
  `discount` float(5,1) NOT NULL DEFAULT '0.0' COMMENT '折扣',
  `consume` float NOT NULL COMMENT '消费满多少触达活动',
  `reduce` float NOT NULL DEFAULT '0' COMMENT '立减多少',
  `reduce_max` float NOT NULL DEFAULT '0' COMMENT '最多优惠金额',
  `total` int(2) NOT NULL,
  `point_speed` float(3,1) NOT NULL DEFAULT '1.0' COMMENT '积分加速倍数',
  `detail` varchar(255) NOT NULL COMMENT '活动详情',
  `sms_params` varchar(255) NOT NULL COMMENT '阿里云短信参数',
  `sms_template_id` varchar(32) NOT NULL COMMENT '阿里云短信模板ID',
  `is_stop` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否已终止',
  `send_coupons` int(11) NOT NULL DEFAULT '0' COMMENT '券发放量',
  `used_coupons` int(11) NOT NULL DEFAULT '0' COMMENT '券使用量',
  `coupon_used_ratio` float(11,0) NOT NULL DEFAULT '0' COMMENT '券回收率',
  `bring_trade_count` int(11) NOT NULL DEFAULT '0' COMMENT '带动交易笔数',
  `bring_trade_amount` int(11) NOT NULL DEFAULT '0' COMMENT '带动交易金额',
  `bring_trade_cash` int(11) NOT NULL DEFAULT '0' COMMENT '带动实收',
  `coupon_amount` int(11) NOT NULL DEFAULT '0' COMMENT '券抵扣金额',
  `updated_at` datetime NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=170 DEFAULT CHARSET=utf8;

CREATE TABLE `coupon_images` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '优惠券详情图片列表自增ID',
  `mch_id` int(11) NOT NULL COMMENT '微信支付商户号',
  `coupon_id` int(11) NOT NULL COMMENT '优惠券ID',
  `image_url` varchar(255) NOT NULL COMMENT '图片素材URL',
  `is_icon` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否是优惠券的封面',
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=348 DEFAULT CHARSET=utf8;

CREATE TABLE `coupons` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '优惠券表自增ID',
  `mch_id` int(11) NOT NULL COMMENT '微信支付商户号',
  `coupon_type` enum('gift','cash','discount','groupon','wechat_cash','timing','waimai','mall') NOT NULL COMMENT '优惠券类型，gift礼品券，cash代金券，discount折扣券，groupon团购券，timing次卡券，waimai外卖代金券，mall商城代金券，wechat_cash微信支付券',
  `name` varchar(255) NOT NULL COMMENT '券名称',
  `discount` float(5,1) NOT NULL DEFAULT '0.0' COMMENT '折扣大小',
  `amount` float(5,1) NOT NULL DEFAULT '0.0' COMMENT '券面值',
  `validity_type` enum('relative','hard') NOT NULL COMMENT '券有效期类型，relative相对有效，hard固定日期有效期',
  `date_start` date NOT NULL COMMENT '开始日期',
  `date_end` date NOT NULL COMMENT '结束日期',
  `total_days` int(5) NOT NULL DEFAULT '0' COMMENT '相对有效期，多少天内有效',
  `is_usefully_sendday` tinyint(1) NOT NULL DEFAULT '1' COMMENT '发出当天是否有效',
  `is_single` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否指定商品可用',
  `consume_limit` int(11) NOT NULL DEFAULT '0' COMMENT '最低消费金额',
  `description` text NOT NULL COMMENT '券使用规则',
  `coupon_stock_id` int(11) NOT NULL DEFAULT '0' COMMENT '微信支付代金券批次ID',
  `deal_detail` text NOT NULL COMMENT '团购券详情描述',
  `wechat_cardid` varchar(32) NOT NULL COMMENT '微信原生卡券ID',
  `wechat_qrcode_url` varchar(255) NOT NULL COMMENT '小程序领取码',
  `balance` int(6) NOT NULL DEFAULT '0' COMMENT '库存',
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `wid` (`wechat_cardid`)
) ENGINE=InnoDB AUTO_INCREMENT=278 DEFAULT CHARSET=utf8;

CREATE TABLE `coupons_used` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '优惠券使用表',
  `mch_id` int(11) NOT NULL,
  `shop_id` int(11) NOT NULL DEFAULT '0',
  `openid` varchar(32) NOT NULL,
  `coupon_id` int(11) NOT NULL,
  `coupon_name` varchar(32) NOT NULL,
  `code` varchar(12) NOT NULL COMMENT '券编号',
  `amount` float(6,2) NOT NULL DEFAULT '0.00' COMMENT '券抵扣金额',
  `trade_no` varchar(25) NOT NULL COMMENT '订单编号',
  `created_by_uname` varchar(16) NOT NULL COMMENT '核销店员名称',
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2606 DEFAULT CHARSET=utf8;

CREATE TABLE `logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '商户日志表',
  `mch_id` int(11) NOT NULL,
  `openid` varchar(32) NOT NULL COMMENT '店员openid',
  `message` varchar(255) NOT NULL COMMENT '日志详情',
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ai` (`mch_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1481 DEFAULT CHARSET=utf8;

CREATE TABLE `mch_cash_out_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '已弃用',
  `mch_id` int(11) NOT NULL,
  `openid` varchar(32) NOT NULL,
  `partner_trade_no` varchar(32) NOT NULL,
  `re_user_name` varchar(32) NOT NULL,
  `cash_out` float(9,2) NOT NULL DEFAULT '0.00',
  `spbill_create_ip` varchar(32) NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8;

CREATE TABLE `mch_form_list` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '会员调研表自增ID',
  `mch_id` int(11) NOT NULL,
  `is_open` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否开启活动',
  `formid` varchar(32) NOT NULL COMMENT '表单大师的formid',
  `award_type` enum('none','point','coupon') NOT NULL DEFAULT 'none' COMMENT '奖励类型，none无奖励，point奖励积分，coupon奖励优惠券',
  `award_value` int(8) NOT NULL DEFAULT '0' COMMENT '奖励类型的数额',
  `coupon_name` varchar(100) NOT NULL COMMENT '奖励优惠券名称',
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;

CREATE TABLE `mch_groupon_distributes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `mch_id` int(11) NOT NULL,
  `groupon_id` int(11) NOT NULL,
  `openid` varchar(32) NOT NULL,
  `distribute_bonus` float(6,2) NOT NULL DEFAULT '0.00' COMMENT '单笔佣金',
  `distribute_url` varchar(255) NOT NULL,
  `clicks` int(11) NOT NULL COMMENT '浏览人数',
  `pays` float(11,2) NOT NULL DEFAULT '0.00' COMMENT '交易笔数',
  `total_trade` float(11,2) NOT NULL DEFAULT '0.00' COMMENT '产生的交易金额',
  `bonus` float(11,2) NOT NULL DEFAULT '0.00',
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=86 DEFAULT CHARSET=utf8;

CREATE TABLE `mch_groupons` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '商户团购活动表',
  `mch_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL COMMENT '团购活动名称',
  `coupon_id` int(11) NOT NULL COMMENT '优惠券ID',
  `coupon_type` enum('groupon','timing') NOT NULL DEFAULT 'groupon' COMMENT 'groupon团购券，timing次卡券',
  `coupon_name` varchar(50) NOT NULL,
  `coupon_total` int(3) NOT NULL DEFAULT '0' COMMENT '券张数',
  `amount` float(6,2) NOT NULL DEFAULT '0.00' COMMENT '券面值金额',
  `price` float(6,2) NOT NULL DEFAULT '0.00' COMMENT '售卖金额',
  `total_limit` int(11) NOT NULL COMMENT '限量多少份',
  `single_limit` int(2) NOT NULL DEFAULT '0' COMMENT '每人限购多少份，0为不限购',
  `is_member_limit` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否仅会员购买',
  `sold` int(11) NOT NULL DEFAULT '0' COMMENT '已售多少份',
  `date_start` date NOT NULL COMMENT '售卖开始日期',
  `date_end` date NOT NULL COMMENT '售卖结束日期',
  `consumed` int(11) NOT NULL DEFAULT '0' COMMENT '已售卖',
  `expired` int(11) NOT NULL DEFAULT '0' COMMENT '券过期数量',
  `revenue` float(11,2) NOT NULL DEFAULT '0.00' COMMENT '营收金额',
  `refund` float(6,2) NOT NULL DEFAULT '0.00' COMMENT '退款',
  `qrcode_url` varchar(255) NOT NULL COMMENT '活动二维码',
  `is_stop` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否已停止',
  `is_distribute` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否分销',
  `distribute_grade` int(2) NOT NULL DEFAULT '0' COMMENT '有分销的会员等级，包含该等级以上的会员等级',
  `distribute_bonus` float(6,2) NOT NULL DEFAULT '0.00' COMMENT '分销一单收益金额',
  `distribute_sold` int(11) NOT NULL DEFAULT '0' COMMENT '分销交易笔数',
  `distribute_revenue` float(11,2) NOT NULL DEFAULT '0.00' COMMENT '分销产生的交易金额',
  `distribute_bonus_total` float(11,2) NOT NULL DEFAULT '0.00' COMMENT '分销发放的提成',
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=57 DEFAULT CHARSET=utf8;

CREATE TABLE `mch_index_top_images` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '商户会员小程序首页轮播图列表',
  `mch_id` int(11) NOT NULL,
  `pic_url` varchar(255) NOT NULL COMMENT '图片URL',
  `path` varchar(100) NOT NULL COMMENT '链接小程序路径',
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=44 DEFAULT CHARSET=utf8;

CREATE TABLE `mch_mall_configs` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '商城表',
  `mch_id` int(11) NOT NULL,
  `can_self` tinyint(1) NOT NULL DEFAULT '1' COMMENT '是否允许自提',
  `can_recharge` tinyint(1) NOT NULL DEFAULT '1' COMMENT '是否允许储值支付',
  `delivery_cost` int(5) NOT NULL DEFAULT '0' COMMENT '运费',
  `delivery_free_atleast` int(5) NOT NULL DEFAULT '0' COMMENT '消费满多少免运费',
  `delivery_tip` text NOT NULL COMMENT '快递说明',
  `jiabo_device_no` varchar(32) NOT NULL COMMENT '佳博云打印机编码',
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;

CREATE TABLE `mch_mall_products` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '商城商品表',
  `mch_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL COMMENT '商品名称',
  `icon_url` varchar(255) NOT NULL COMMENT '封面图片URL',
  `detail_images` text NOT NULL COMMENT '详情图片列表',
  `detail` text NOT NULL COMMENT '详情说明文字',
  `amount` float(9,2) NOT NULL DEFAULT '0.00' COMMENT '原价',
  `price` float(9,2) NOT NULL DEFAULT '0.00' COMMENT '售价',
  `total_limit` int(5) NOT NULL COMMENT '库存',
  `single_limit` int(5) NOT NULL COMMENT '每人限购',
  `is_member_limit` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否仅会员购买',
  `sold` int(5) NOT NULL DEFAULT '0' COMMENT '已售',
  `revenue` float(9,2) DEFAULT '0.00' COMMENT '收益金额',
  `qrcode_url` varchar(255) NOT NULL COMMENT '活动二维码',
  `is_selling` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否上架',
  `distribute_grade` int(5) NOT NULL DEFAULT '0' COMMENT '分销会员等级',
  `is_distribute` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否分销',
  `distribute_bonus` float(5,2) NOT NULL DEFAULT '0.00' COMMENT '分销一笔佣金',
  `distribute_sold` int(5) NOT NULL DEFAULT '0' COMMENT '分销笔数',
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;

CREATE TABLE `mch_marketing_share_profits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `mch_id` int(11) NOT NULL,
  `profit_type` enum('groupon','recharge','pintuan','vipcard','timing','waimai') NOT NULL,
  `out_trade_no` varchar(32) NOT NULL,
  `transaction_id` varchar(32) NOT NULL,
  `out_order_no` varchar(32) NOT NULL COMMENT '分账订单号',
  `cash_fee` float(11,0) NOT NULL,
  `profit_fee` float(11,0) NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8;

CREATE TABLE `mch_ordering_configs` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '商户自助点餐配置',
  `mch_id` int(11) NOT NULL,
  `is_open` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否开启自助点餐',
  `formid` varchar(32) NOT NULL COMMENT '表单大师的formid',
  `pay_first` tinyint(1) NOT NULL DEFAULT '1' COMMENT '是否必须先支付后取餐',
  `order_time_start` varchar(32) NOT NULL COMMENT '点餐功能开放开始时间',
  `order_time_end` varchar(32) NOT NULL COMMENT '结束时间',
  `jiabo_device_no` varchar(32) NOT NULL COMMENT '佳博云打印机编码',
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;

CREATE TABLE `mch_ordering_tables` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '自助点餐桌号表ID',
  `mch_id` int(11) NOT NULL,
  `table_id` int(5) NOT NULL COMMENT '桌号',
  `table_name` varchar(50) NOT NULL COMMENT '桌号名称',
  `seats` int(3) NOT NULL DEFAULT '0' COMMENT '座位数',
  `is_seat` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否已有顾客入座',
  `qrcode_url` varchar(255) NOT NULL COMMENT '点餐码',
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8;

CREATE TABLE `mch_pay_result_recommends` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '已弃用',
  `mch_id` int(11) NOT NULL,
  `campaign_type` enum('point','recharge','groupon') NOT NULL,
  `campaign_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8;

CREATE TABLE `mch_revenues` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '已弃用',
  `mch_id` int(11) NOT NULL,
  `groupon_revenue` float(11,2) NOT NULL DEFAULT '0.00',
  `wait_cash_out` float(11,2) NOT NULL DEFAULT '0.00',
  `cash_out` float(11,2) NOT NULL DEFAULT '0.00',
  `total_revenue` float(11,2) NOT NULL DEFAULT '0.00',
  `service_fee` float(11,2) NOT NULL DEFAULT '0.00',
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;

CREATE TABLE `mch_tenpay_configs` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '对接腾讯云支付表',
  `mch_id` int(11) NOT NULL,
  `authen_key` varchar(32) NOT NULL COMMENT '腾讯云支付商户的authen_key',
  `out_shop_id` varchar(32) NOT NULL COMMENT '云支付的对外商户id',
  `out_mch_id` varchar(32) NOT NULL COMMENT '去支付的对外商户号',
  `out_sub_mch_id` varchar(32) NOT NULL COMMENT '云支付的对外子商户号',
  `cloud_cashier_id` varchar(10) NOT NULL COMMENT '云支付订单号前缀',
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `mch_togethers` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '商户拼团表',
  `mch_id` int(11) NOT NULL,
  `coupon_id` int(11) NOT NULL,
  `coupon_name` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL COMMENT '拼团活动名称',
  `sold` int(11) NOT NULL DEFAULT '0' COMMENT '已售多少份',
  `amount` float(9,2) DEFAULT '0.00' COMMENT '原价',
  `price` float(9,2) NOT NULL DEFAULT '0.00' COMMENT '售价',
  `people` int(11) NOT NULL DEFAULT '0' COMMENT '成团人数',
  `expire_times` int(11) NOT NULL DEFAULT '0' COMMENT '开团有效小时数',
  `is_limit` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否销售限制',
  `total_limit` int(11) NOT NULL DEFAULT '0' COMMENT '库存',
  `single_limit` int(11) NOT NULL DEFAULT '0' COMMENT '单人限购几份',
  `opens` int(11) NOT NULL COMMENT '开团数',
  `expires` int(11) NOT NULL COMMENT '未成团数',
  `success` int(11) NOT NULL COMMENT '成团数',
  `is_stop` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否终止活动',
  `consumed` int(11) NOT NULL DEFAULT '0' COMMENT '券核销数',
  `revenue` float(9,2) DEFAULT '0.00' COMMENT '收益金额',
  `date_start` date NOT NULL COMMENT '活动开始日期',
  `date_end` date NOT NULL COMMENT '活动结束日期',
  `qrcode_url` varchar(255) NOT NULL COMMENT '活动小程序码',
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8;

CREATE TABLE `mch_vipcards` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '商户付费卡售卖表',
  `mch_id` int(11) NOT NULL,
  `merchant_name` varchar(100) NOT NULL COMMENT '商户名称',
  `grade` int(11) NOT NULL COMMENT '付费卡等级',
  `grade_name` varchar(32) NOT NULL COMMENT '付费卡名称',
  `valid_days` int(5) NOT NULL DEFAULT '0' COMMENT '有效期天数',
  `price` float(7,2) NOT NULL DEFAULT '0.00' COMMENT '售价',
  `is_limit` tinyint(1) NOT NULL COMMENT '是否限购',
  `total_limit` int(11) NOT NULL COMMENT '限购数量',
  `pic_url` varchar(255) NOT NULL COMMENT '封面图片URL',
  `sold` int(11) NOT NULL COMMENT '售出数量',
  `revenue` float(9,2) NOT NULL DEFAULT '0.00' COMMENT '收益金额',
  `is_stop` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否终止活动',
  `qrcode_url` varchar(255) NOT NULL COMMENT '活动小程序码',
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8;

CREATE TABLE `mch_vlogs` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '商户VLOG表',
  `mch_id` int(11) NOT NULL,
  `vedio_url` varchar(255) NOT NULL COMMENT '视频URL',
  `thumb_url` varchar(255) NOT NULL COMMENT '缩略图URL',
  `detail` varchar(255) NOT NULL COMMENT '视频文字说明',
  `groupon_id` int(5) NOT NULL COMMENT '团购活动ID',
  `groupon_name` varchar(255) NOT NULL COMMENT '团购活动标题',
  `loves` int(6) NOT NULL COMMENT '点赞数',
  `share_pic_url` varchar(255) NOT NULL COMMENT '分享图片的链接',
  `width` int(5) NOT NULL COMMENT '视频宽度',
  `height` int(5) NOT NULL COMMENT '视频高度',
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8;

CREATE TABLE `mch_waimai_configs` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '商户外卖配置表',
  `mch_id` int(11) NOT NULL,
  `is_open` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否开户外卖',
  `formid` varchar(32) NOT NULL COMMENT '表单大师的formid',
  `can_self` tinyint(1) NOT NULL DEFAULT '1' COMMENT '是否允许自提',
  `can_recharge` tinyint(1) NOT NULL DEFAULT '1' COMMENT '是否允许使用储值支付',
  `delivery_distance` float(5,1) NOT NULL DEFAULT '3.0' COMMENT '起送距离',
  `cost_atleast` int(5) NOT NULL DEFAULT '0' COMMENT '最低起送',
  `delivery_time` varchar(255) NOT NULL COMMENT '配送时间',
  `delivery_cost` float(5,2) NOT NULL DEFAULT '0.00' COMMENT '配送费',
  `delivery_free_atleast` int(5) NOT NULL DEFAULT '0' COMMENT '消费满多少免配送费',
  `package_cost` float(5,2) NOT NULL DEFAULT '0.00' COMMENT '每件包装费',
  `delivery_time_start` varchar(32) NOT NULL COMMENT '配送开始时间',
  `delivery_time_end` varchar(32) NOT NULL COMMENT '配送结束时间',
  `jiabo_device_no` varchar(32) NOT NULL COMMENT '佳博云打印机编码',
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;

CREATE TABLE `mchs` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '商户表',
  `mch_id` int(11) NOT NULL COMMENT '微信支付商户号',
  `pay_platform` enum('suishouhui','tenpay','other') DEFAULT 'suishouhui' COMMENT '支付渠道，suishouhui随手惠自有通道，tenpay腾讯云支付通道，other其它通道',
  `appid` varchar(32) NOT NULL COMMENT '商户会员营销小程序appid',
  `shops` int(11) NOT NULL DEFAULT '1' COMMENT '商户门店数量',
  `merchant_name` varchar(32) NOT NULL COMMENT '商户简称',
  `alipay_app_id` varchar(32) NOT NULL COMMENT '支付宝支付的appid',
  `mch_type` enum('xiaowei','getihu','company','general') NOT NULL DEFAULT 'xiaowei' COMMENT '商户类型，xiaowei小微商户（无营业执照），getihu个体户，company企业，general未申请支付',
  `marketing_type` enum('pay','marketing','wisdom','coupon','commission','groupon','waimai') NOT NULL DEFAULT 'pay' COMMENT '营销版本，pay支付版，marketing专业版，coupon优惠券版，groupon团购版，waimai外卖版，其它弃用',
  `is_grade` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否开通会员等级',
  `is_recharge` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否开通储值',
  `is_reduce` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否开通立减',
  `is_pay_gift` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否开通支付返券',
  `is_groupon` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否开通团购',
  `is_together` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否开通拼团',
  `is_memberday` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否开通会员日',
  `is_timing` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否开通次卡',
  `is_sharecoupon` tinyint(1) NOT NULL DEFAULT '0',
  `is_wechatgroup` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否开通微信群',
  `is_waimai` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否开通外卖',
  `is_selftaking` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否开通自提',
  `is_ordering` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否开通自助点餐',
  `is_tables` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否有桌台',
  `is_mall` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否开通商城',
  `is_payed_share` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否开通支付后裂变券',
  `is_rechargenopay` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否开通激励储值',
  `is_paybuycoupon` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否开通加价购券',
  `is_distribute` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否开通分销',
  `is_wakeup` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否开通沉睡唤醒',
  `is_vipcard` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否开通付费卡售卖',
  `wechat_fee_rate` float(5,4) NOT NULL DEFAULT '0.0038' COMMENT '微信支付费率',
  `ali_fee_rate` float(4,4) NOT NULL DEFAULT '0.0038' COMMENT '支付宝费率',
  `marketing_fee_rate` float(4,4) NOT NULL DEFAULT '0.0060' COMMENT '营销费率',
  `wechat_revenue_rate` float(5,4) NOT NULL DEFAULT '0.0000' COMMENT '微信支付利润费率',
  `ali_revenue_rate` float(5,4) NOT NULL DEFAULT '0.0000' COMMENT '支付宝利润费率',
  `is_bind_payqrcode` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否已绑定收款码',
  `expired_at` date NOT NULL COMMENT '商户过期时间',
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=60 DEFAULT CHARSET=utf8;

CREATE TABLE `member_address` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '会员地址表',
  `mch_id` int(11) NOT NULL,
  `openid` varchar(32) NOT NULL,
  `name` varchar(32) NOT NULL COMMENT '姓名',
  `mobile` varchar(32) NOT NULL COMMENT '手机号',
  `address` varchar(100) NOT NULL COMMENT '地址',
  `address_no` varchar(50) NOT NULL COMMENT '门牌号',
  `longitude` float(13,10) NOT NULL COMMENT '地址经度',
  `latitude` float(13,10) NOT NULL COMMENT '地址纬度',
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=732 DEFAULT CHARSET=utf8;

CREATE TABLE `member_cash_out_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '已弃用',
  `mch_id` int(11) NOT NULL,
  `openid` varchar(32) NOT NULL,
  `partner_trade_no` varchar(32) NOT NULL,
  `re_user_name` varchar(32) NOT NULL,
  `cash_out` float(9,2) NOT NULL DEFAULT '0.00',
  `spbill_create_ip` varchar(32) NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `member_coupons` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '会员优惠券列表',
  `openid` varchar(32) NOT NULL COMMENT '会员openid',
  `unionid` varchar(32) NOT NULL COMMENT '开放平台unionid',
  `mch_id` int(11) NOT NULL COMMENT '微信支付商户号',
  `card_id` varchar(32) NOT NULL COMMENT '优惠券的微信卡券ID',
  `coupon_id` int(11) NOT NULL COMMENT '优惠券ID',
  `coupon_name` varchar(50) NOT NULL COMMENT '优惠券名称',
  `coupon_type` enum('cash','gift','discount','groupon','wechat_cash','timing','waimai') NOT NULL DEFAULT 'cash' COMMENT '券类型',
  `amount` int(11) NOT NULL COMMENT '券面值',
  `discount` float(5,2) NOT NULL DEFAULT '0.00' COMMENT '券折扣',
  `consume_limit` int(11) NOT NULL COMMENT '券最低消费金额',
  `code` varchar(12) NOT NULL COMMENT '券码',
  `code_url` varchar(255) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '0代表已核销\n2代表已过期\n1代表已领取\n3代表转赠中',
  `get_type` enum('send','rebate','recharge','adjust','opencard','gift','exchange','get','buy','share','together_buy') NOT NULL COMMENT '券获取途径',
  `detail` varchar(255) NOT NULL COMMENT '券使用规则',
  `date_start` date NOT NULL COMMENT '券有效期开始',
  `date_end` date NOT NULL COMMENT '券有效期结束',
  `in_wechat` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否已领取到微信卡包',
  `coupon_revenue` float(9,2) NOT NULL DEFAULT '0.00' COMMENT '团购券单张券的价值',
  `updated_at` datetime NOT NULL COMMENT '券更新时间',
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14996 DEFAULT CHARSET=utf8;

CREATE TABLE `member_coupons_wait` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '已弃用',
  `mch_id` int(11) NOT NULL,
  `openid` varchar(32) NOT NULL,
  `sub_openid` varchar(32) NOT NULL,
  `coupon_id` int(11) NOT NULL,
  `card_id` varchar(32) NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8;

CREATE TABLE `member_distribute_bonus_list` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '用户分销订单表',
  `mch_id` int(11) NOT NULL,
  `groupon_id` int(11) NOT NULL COMMENT '团购活动ID',
  `groupon_title` varchar(255) NOT NULL,
  `distribute_id` int(11) NOT NULL COMMENT '分销活动ID',
  `transaction_id` varchar(32) NOT NULL COMMENT '微信支付订单号',
  `openid` varchar(32) NOT NULL COMMENT '分销者openid',
  `bonus` float(6,2) NOT NULL DEFAULT '0.00' COMMENT '分销佣金',
  `transaction_openid` varchar(32) NOT NULL COMMENT '购买者openid',
  `nickname` varchar(32) NOT NULL COMMENT '购买者微信昵称',
  `headimgurl` varchar(255) NOT NULL COMMENT '购买者头像',
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8;

CREATE TABLE `member_form_post_list` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '已弃用',
  `mch_id` int(11) NOT NULL,
  `form_id` varchar(32) NOT NULL,
  `openid` varchar(32) NOT NULL,
  `detail` text NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `member_mall_orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '会员商城购买记录表',
  `mch_id` int(11) NOT NULL,
  `openid` varchar(32) NOT NULL COMMENT '会员openid',
  `amount` float(9,2) NOT NULL DEFAULT '0.00' COMMENT '商品金额',
  `delivery_cost` float(9,2) NOT NULL DEFAULT '0.00' COMMENT '快递费',
  `total_amount` float(9,2) NOT NULL DEFAULT '0.00' COMMENT '订单金额',
  `detail` text NOT NULL COMMENT '购买详情',
  `out_trade_no` varchar(32) NOT NULL COMMENT '商户订单号',
  `contact_name` varchar(32) NOT NULL COMMENT '收货人',
  `contact_mobile` varchar(32) NOT NULL COMMENT '手机号',
  `contact_address` varchar(100) NOT NULL COMMENT '地址',
  `remark` varchar(100) NOT NULL COMMENT '备注',
  `distribute_fee` int(11) NOT NULL DEFAULT '0' COMMENT '分销佣金',
  `accept_at` datetime NOT NULL COMMENT '商户接单时间',
  `delivery_at` datetime NOT NULL COMMENT '商户快递发货时间',
  `closed_at` datetime NOT NULL COMMENT '订单结束时间',
  `delivery_type` varchar(32) NOT NULL COMMENT '快递公司',
  `delivery_no` varchar(32) NOT NULL COMMENT '快递单号',
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;

CREATE TABLE `member_ordering_orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '会员自助点餐点单表',
  `mch_id` int(11) NOT NULL,
  `form_id` varchar(32) NOT NULL COMMENT '表单大师formid',
  `entry_id` varchar(32) NOT NULL COMMENT '表单大师的提交数据id',
  `is_pay` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否已支付',
  `openid` varchar(32) NOT NULL,
  `amount` float(9,2) NOT NULL DEFAULT '0.00',
  `total_amount` float(9,2) NOT NULL DEFAULT '0.00',
  `cash_amount` float(9,2) NOT NULL DEFAULT '0.00' COMMENT '实收',
  `detail` text NOT NULL,
  `out_trade_no` varchar(32) NOT NULL,
  `table_id` int(3) NOT NULL DEFAULT '0' COMMENT '桌号',
  `table_name` varchar(50) NOT NULL COMMENT '桌号名称',
  `get_no` varchar(32) NOT NULL COMMENT '取餐号',
  `accept_at` datetime NOT NULL COMMENT '接单时间',
  `closed_at` datetime NOT NULL,
  `contact_name` varchar(32) NOT NULL COMMENT '点单人姓名',
  `grade_title` varchar(32) NOT NULL COMMENT '会员等级名称',
  `remark` varchar(100) NOT NULL,
  `is_accept_remind` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8;

CREATE TABLE `member_point_exchanges` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '会员积分兑换列表',
  `mch_id` int(11) NOT NULL,
  `openid` varchar(32) NOT NULL,
  `rule_id` int(11) NOT NULL COMMENT '积分兑换规则ID',
  `form_id` varchar(32) NOT NULL COMMENT '小程序模板消息formId',
  `point` int(11) NOT NULL COMMENT '积分数额',
  `coupon_id` int(11) NOT NULL COMMENT '优惠券ID',
  `code` int(11) NOT NULL COMMENT '优惠券编号',
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=137 DEFAULT CHARSET=utf8;

CREATE TABLE `member_point_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '会员积分变动表',
  `mch_id` int(11) NOT NULL,
  `openid` varchar(32) NOT NULL,
  `modify_point` int(5) NOT NULL COMMENT '变更积分',
  `detail` varchar(50) NOT NULL COMMENT '详细说明',
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5276 DEFAULT CHARSET=utf8;

CREATE TABLE `member_recharges` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '会员储值记录表',
  `mch_id` int(11) NOT NULL,
  `shop_id` int(11) NOT NULL DEFAULT '0',
  `openid` varchar(32) NOT NULL COMMENT '会员号对应openid',
  `sub_openid` varchar(32) NOT NULL COMMENT '小程序对应openid',
  `recharge` float(11,2) NOT NULL DEFAULT '0.00' COMMENT '储值金额',
  `award_money` int(11) NOT NULL COMMENT '奖励金额',
  `award_coupon` varchar(255) NOT NULL COMMENT '奖励优惠券',
  `transaction_id` varchar(32) NOT NULL COMMENT '微信支付订单编号',
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tid` (`transaction_id`) USING BTREE,
  KEY `oid` (`openid`),
  KEY `soid` (`sub_openid`)
) ENGINE=InnoDB AUTO_INCREMENT=614 DEFAULT CHARSET=utf8;

CREATE TABLE `member_waimai_orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '会员外卖订单表',
  `mch_id` int(11) NOT NULL,
  `form_id` varchar(32) NOT NULL COMMENT '表单大师formid',
  `entry_id` varchar(32) NOT NULL COMMENT '表单数据id',
  `is_pay` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否已支付',
  `is_self` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否自提',
  `openid` varchar(32) NOT NULL,
  `amount` float(9,2) NOT NULL DEFAULT '0.00' COMMENT '商品金额',
  `delivery_cost` float(9,2) NOT NULL DEFAULT '0.00' COMMENT '运费',
  `package_cost` float(9,2) NOT NULL DEFAULT '0.00' COMMENT '包装费',
  `total_amount` float(9,2) NOT NULL DEFAULT '0.00' COMMENT '订单金额',
  `detail` text NOT NULL COMMENT '订单商品详情',
  `out_trade_no` varchar(32) NOT NULL COMMENT '商户订单号',
  `delivery_time` varchar(32) NOT NULL COMMENT '顾客预定配送时间',
  `accept_at` datetime NOT NULL COMMENT '商户接单时间',
  `delivery_at` datetime NOT NULL COMMENT '商户配送时间',
  `closed_at` datetime NOT NULL COMMENT '订单关闭时间 ',
  `contact_name` varchar(32) NOT NULL COMMENT '收件人姓名',
  `contact_mobile` varchar(32) NOT NULL COMMENT '手机号',
  `contact_address` varchar(100) NOT NULL COMMENT '详细地址',
  `remark` varchar(100) NOT NULL COMMENT '备注',
  `is_accept_remind` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否订阅小程序商户接单提醒通知',
  `is_delivery_remind` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否订阅小程序商户配送提醒通知',
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1776 DEFAULT CHARSET=utf8;

CREATE TABLE `members` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '会员表',
  `mch_id` int(11) NOT NULL,
  `shop_id` int(11) NOT NULL DEFAULT '0',
  `openid` varchar(32) NOT NULL COMMENT 'MP平台openid',
  `sub_openid` varchar(32) NOT NULL COMMENT '小程序openid',
  `unionid` varchar(32) NOT NULL COMMENT '开放平台unionid',
  `userid` varchar(32) NOT NULL COMMENT '企业微信中加会员的员工userid',
  `external_userid` varchar(32) NOT NULL COMMENT '企业微信中会员的外部联系人userid',
  `member_cardid` varchar(32) NOT NULL COMMENT '商户微信原生卡包会员卡cardid',
  `cardnum` varchar(12) NOT NULL COMMENT '微信会员卡卡号',
  `grade` tinyint(1) NOT NULL DEFAULT '1' COMMENT '会员等级编号',
  `grade_title` varchar(32) DEFAULT NULL COMMENT '会员等级名称',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `nickname` varchar(32) NOT NULL,
  `headimgurl` varchar(255) NOT NULL,
  `province` varchar(32) NOT NULL,
  `city` varchar(32) NOT NULL,
  `name` varchar(32) NOT NULL,
  `gender` tinyint(1) NOT NULL DEFAULT '1',
  `mobile` varchar(11) NOT NULL,
  `birthday` date NOT NULL,
  `point` int(11) NOT NULL DEFAULT '0' COMMENT '积分余额',
  `recharge` float(11,1) NOT NULL DEFAULT '0.0' COMMENT '储值余额',
  `coupons` int(3) NOT NULL DEFAULT '0' COMMENT '优惠券张数',
  `consumes` int(11) NOT NULL DEFAULT '0' COMMENT '累计消费次数',
  `amount_total` float(11,2) NOT NULL DEFAULT '0.00' COMMENT '累计消费金额',
  `recharge_total` float(11,1) NOT NULL DEFAULT '0.0' COMMENT '累计储值金额',
  `distribute_cash_out` float(11,2) NOT NULL DEFAULT '0.00' COMMENT '累积提现金额',
  `is_deleted` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否已删除会员卡',
  `last_consume_at` datetime NOT NULL COMMENT '最后一次消费时间',
  `upgrade_at` datetime NOT NULL COMMENT '升级时间',
  `expired_at` datetime NOT NULL COMMENT '会员等级过期时间',
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `m` (`mobile`),
  KEY `oid` (`openid`),
  KEY `soid` (`sub_openid`),
  KEY `uid` (`unionid`),
  KEY `cn` (`cardnum`)
) ENGINE=InnoDB AUTO_INCREMENT=8894 DEFAULT CHARSET=utf8;

CREATE TABLE `pos_app_keys` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '已弃用',
  `app_key` varchar(32) NOT NULL,
  `app_secret` varchar(32) NOT NULL,
  `app_name` varchar(100) NOT NULL,
  `company_name` varchar(100) NOT NULL,
  `contact_name` varchar(32) NOT NULL,
  `contact_phone` varchar(32) NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ak` (`app_key`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

CREATE TABLE `qywork_corpids` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '商户接入了企业微信的信息表',
  `appid` varchar(32) NOT NULL COMMENT '会员小程序appid',
  `corpid` varchar(32) NOT NULL COMMENT '商户企业微信corpid',
  `mch_id` int(11) NOT NULL DEFAULT '0',
  `corp_name` varchar(255) NOT NULL COMMENT '商户主体名称',
  `corp_type` varchar(32) NOT NULL,
  `corp_round_logo_url` varchar(100) NOT NULL,
  `corp_square_logo_url` varchar(100) NOT NULL,
  `corp_wxqrcode` varchar(100) NOT NULL,
  `corp_full_name` varchar(255) NOT NULL,
  `subject_type` varchar(32) NOT NULL,
  `corp_scale` varchar(32) NOT NULL,
  `corp_industry` varchar(32) NOT NULL,
  `corp_sub_industry` varchar(100) NOT NULL,
  `location` varchar(32) NOT NULL,
  `userid` varchar(32) NOT NULL COMMENT '商户管理员在企业微信中的userid',
  `name` varchar(32) NOT NULL,
  `avatar` varchar(150) NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;

CREATE TABLE `qywork_secrets` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '商户企业微信外部联系人API接口secret表',
  `corpid` varchar(32) NOT NULL COMMENT '商户企业微信corpid',
  `external_secret` varchar(255) NOT NULL COMMENT '外部联系人API的secret',
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;

CREATE TABLE `shops` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '商户门店表',
  `mch_id` int(32) NOT NULL,
  `poi_id` int(11) NOT NULL COMMENT '弃用',
  `business_name` varchar(32) NOT NULL COMMENT '商户名称',
  `branch_name` varchar(32) NOT NULL COMMENT '分店名称',
  `openid` varchar(32) NOT NULL COMMENT '管理员openid',
  `province` varchar(32) NOT NULL,
  `city` varchar(32) NOT NULL,
  `district` varchar(32) NOT NULL,
  `address` varchar(255) NOT NULL,
  `longitude` float(13,10) NOT NULL,
  `latitude` float(13,10) NOT NULL,
  `telephone` varchar(32) NOT NULL,
  `categories` varchar(100) NOT NULL,
  `recommend` varchar(500) NOT NULL,
  `special` varchar(500) NOT NULL,
  `introduction` varchar(500) NOT NULL,
  `open_time` varchar(255) NOT NULL,
  `avg_price` varchar(32) NOT NULL,
  `logo_url` varchar(255) NOT NULL,
  `store_entrance_url` varchar(255) NOT NULL,
  `card_url` varchar(255) NOT NULL COMMENT '会员码',
  `updated_at` datetime NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=53 DEFAULT CHARSET=utf8;

CREATE TABLE `sms_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '已弃用',
  `mch_id` int(11) NOT NULL,
  `mobile` varchar(32) NOT NULL,
  `content` varchar(500) NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tuitui_cash_out_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '代理商提现记录表',
  `userid` int(11) NOT NULL COMMENT '代理商userid',
  `openid` varchar(32) NOT NULL COMMENT '代理商在代理小程序中的openid',
  `partner_trade_no` varchar(32) NOT NULL COMMENT '订单号',
  `re_user_name` varchar(32) NOT NULL COMMENT '代理商真实姓名',
  `cash_out` float(6,2) NOT NULL DEFAULT '0.00' COMMENT '提现金额',
  `spbill_create_ip` varchar(32) NOT NULL COMMENT '提现请求时的IP',
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

CREATE TABLE `tuitui_user_revenue_days` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '代理商每日收益表',
  `uid` int(11) NOT NULL COMMENT '代理商userid',
  `openid` varchar(32) NOT NULL COMMENT '代理商在代理小程序中的openid',
  `expand_user_total` int(5) NOT NULL COMMENT '招募推手',
  `expand_revenue` float(6,2) NOT NULL DEFAULT '0.00' COMMENT '招募推手收入',
  `expand_reward` float(6,2) NOT NULL DEFAULT '0.00' COMMENT '推手提成',
  `pay_revenue` float(5,2) NOT NULL DEFAULT '0.00' COMMENT '支付返佣收入',
  `wechat_pay_revenue` float(5,2) NOT NULL DEFAULT '0.00' COMMENT '微信支付返佣收入',
  `alipay_revenue` float(5,2) NOT NULL DEFAULT '0.00' COMMENT '支付宝返佣收入',
  `wechat_pay_coupon_revenue` float(5,2) NOT NULL DEFAULT '0.00' COMMENT '弃用',
  `groupon_revenue` float(5,2) NOT NULL DEFAULT '0.00' COMMENT '团购返佣收入',
  `team_pay_revenue` float(5,2) NOT NULL DEFAULT '0.00' COMMENT '团队支付返佣',
  `team_wechat_pay_coupon_revenue` float(5,2) NOT NULL DEFAULT '0.00' COMMENT '弃用',
  `team_groupon_revenue` float(5,2) NOT NULL DEFAULT '0.00' COMMENT '团队团购返佣收入',
  `wait_cash_out` float(5,2) NOT NULL DEFAULT '0.00' COMMENT '待提现金额',
  `day_revenue` float(5,2) NOT NULL DEFAULT '0.00' COMMENT '当天佣金',
  `total_revenues` float(8,2) NOT NULL DEFAULT '0.00' COMMENT '总收益',
  `date_at` date NOT NULL COMMENT '日期',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=580 DEFAULT CHARSET=utf8;

CREATE TABLE `tuitui_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '代理商用户表',
  `openid` varchar(32) NOT NULL COMMENT '代理商在代理后台小程序中的openid',
  `unionid` varchar(32) NOT NULL COMMENT '代理商在开放平台中的unionid',
  `nickname` varchar(32) NOT NULL,
  `headimgurl` varchar(255) NOT NULL,
  `gender` tinyint(1) NOT NULL DEFAULT '1',
  `name` varchar(32) NOT NULL,
  `mobile` varchar(32) NOT NULL,
  `is_leader` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否为团长',
  `leader_id` int(11) NOT NULL COMMENT '上级团长的userid',
  `province` varchar(32) NOT NULL,
  `city` varchar(32) NOT NULL,
  `merchants` int(5) NOT NULL DEFAULT '0' COMMENT '直属商户数',
  `agent_type` enum('pay','marketing','wisdom') DEFAULT 'pay' COMMENT 'pay支付代理商，marketing营销代理商，wisdom弃用',
  `expand_qrcode` varchar(255) NOT NULL COMMENT '拓展二维码',
  `profit_ratio` float(5,2) NOT NULL DEFAULT '0.00' COMMENT '随手惠与该代理商的返佣分成比率，或者团长与该推手的分成比率',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1正常，0禁用',
  `wait_cash_out` float(6,2) NOT NULL DEFAULT '0.00' COMMENT '待提现金额',
  `total_revenue` float(6,2) NOT NULL DEFAULT '0.00' COMMENT '总收益',
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `oid` (`openid`)
) ENGINE=InnoDB AUTO_INCREMENT=148 DEFAULT CHARSET=utf8;

CREATE TABLE `user_mch_submit` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '商户拓展申请表',
  `openid` varchar(32) NOT NULL COMMENT '商户管理员在管理后台小程序中的openid',
  `uid` int(11) NOT NULL DEFAULT '0' COMMENT '代理商的uid',
  `leader_uid` int(11) NOT NULL DEFAULT '0' COMMENT '代理商的团长UID',
  `mch_type` enum('xiaowei','getihu','company','general') DEFAULT 'xiaowei' COMMENT '商户类型，xiaowei小微商户，getihu个体户，company企业，general普通商户(无证照)',
  `marketing_type` enum('marketing','groupon','waimai','pay') NOT NULL DEFAULT 'marketing' COMMENT '营销类型，marketing专业版，groupon团购版，waimai外卖版，pay支付版',
  `mobile` varchar(32) NOT NULL COMMENT '商户管理员联系手机号',
  `dianping_business_id` varchar(11) NOT NULL COMMENT '弃用',
  `dianping_openshopid` varchar(32) NOT NULL DEFAULT '0' COMMENT '弃用',
  `category` varchar(32) NOT NULL COMMENT '经营行业',
  `id_card_name` varchar(32) NOT NULL COMMENT '法人身份证上的姓名',
  `id_card_number` varchar(32) NOT NULL COMMENT '法人身份证号',
  `id_card_valid_time` varchar(32) NOT NULL COMMENT '法人身份证有效期',
  `account_name` varchar(32) NOT NULL COMMENT '法人姓名',
  `account_bank` varchar(100) NOT NULL COMMENT '法人收款银行名称',
  `bank_address_code` varchar(32) NOT NULL COMMENT '银行所在地地址编号-小微商户专用',
  `account_number` varchar(32) NOT NULL COMMENT '银行卡号',
  `store_name` varchar(100) NOT NULL COMMENT '店铺名称',
  `store_street` varchar(100) NOT NULL COMMENT '店铺街道',
  `applyment_id` varchar(32) NOT NULL COMMENT '商户申请单号',
  `merchant_shortname` varchar(50) DEFAULT NULL COMMENT '商户简称',
  `service_phone` varchar(50) DEFAULT NULL COMMENT '客服电话',
  `store_address_code` varchar(32) DEFAULT NULL COMMENT '店铺邮编',
  `business_code` varchar(32) NOT NULL COMMENT '业务申请编号',
  `applyment_state` varchar(32) NOT NULL COMMENT '申请状态',
  `applyment_state_desc` varchar(255) NOT NULL COMMENT '申请错误描述-小微商户专用',
  `id_card_copy` varchar(255) NOT NULL COMMENT '身份证人像面照片',
  `id_card_national` varchar(255) NOT NULL COMMENT '身份证国徽面照片',
  `store_entrance_pic` varchar(255) NOT NULL COMMENT '门头照片mediaid',
  `indoor_pic` varchar(255) NOT NULL COMMENT '店内照片mediaid',
  `logo_url` varchar(255) NOT NULL COMMENT '商户logo',
  `inside_url` varchar(255) NOT NULL COMMENT '店内照片URL',
  `head_url` varchar(255) NOT NULL COMMENT '身份证人像面URL',
  `country_url` varchar(255) NOT NULL COMMENT '身份证国徽面URL',
  `license_url` varchar(255) NOT NULL COMMENT '营业执照',
  `permit_url` varchar(255) NOT NULL COMMENT '餐饮许可证',
  `sub_mch_id` int(11) NOT NULL COMMENT '微信支付子商户号',
  `sign_url` varchar(255) NOT NULL COMMENT '弃用',
  `audit_detail` varchar(32) NOT NULL COMMENT '审核结果',
  `qrcode_url` varchar(255) NOT NULL COMMENT '加会员的小程序码URL',
  `formId` varchar(32) NOT NULL COMMENT '弃用',
  `member_cardid` varchar(32) NOT NULL COMMENT '商户微信原生会员卡cardid',
  `updated_at` datetime NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=68 DEFAULT CHARSET=utf8;

CREATE TABLE `user_mp_openids` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '商户店员在随手惠微信公众号里的openid与unionid对应关系',
  `openid` varchar(32) NOT NULL COMMENT '公众号openid',
  `unionid` varchar(32) NOT NULL COMMENT '开放平台的unionid',
  `nickname` varchar(32) NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `oid` (`unionid`)
) ENGINE=InnoDB AUTO_INCREMENT=54 DEFAULT CHARSET=utf8;

CREATE TABLE `user_reminds` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '店员业务提醒事项表',
  `mch_id` int(11) NOT NULL,
  `openid` varchar(32) NOT NULL COMMENT '店员在管理后台小程序中的openid',
  `unionid` varchar(32) NOT NULL COMMENT '开放平台的unionid',
  `mp_openid` varchar(32) NOT NULL COMMENT '微信公众号openid',
  `is_waimai` tinyint(1) NOT NULL COMMENT '外卖订单提醒',
  `is_mall` tinyint(1) NOT NULL DEFAULT '0' COMMENT '商城订单提醒',
  `is_pay` tinyint(1) NOT NULL DEFAULT '0' COMMENT '支付订单提醒',
  `is_recharge` tinyint(1) NOT NULL DEFAULT '0' COMMENT '储值订单提醒',
  `is_wechat_group` tinyint(1) NOT NULL DEFAULT '0' COMMENT '群二维码过期提醒',
  `is_vipcard` tinyint(1) NOT NULL DEFAULT '0' COMMENT '付费卡订单提醒',
  `is_member` tinyint(1) NOT NULL DEFAULT '0' COMMENT '新会员开卡提醒',
  `is_day` tinyint(1) NOT NULL DEFAULT '0' COMMENT '每日报表提醒',
  `is_week` tinyint(1) NOT NULL DEFAULT '0' COMMENT '每周报表提醒',
  `is_month` tinyint(1) NOT NULL DEFAULT '0' COMMENT '每月报表提醒',
  `is_groupon` tinyint(1) NOT NULL DEFAULT '0' COMMENT '团购订单提醒',
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8;

CREATE TABLE `user_sharecoupon_rules` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '给好友发券的券抢中机率表',
  `share_key` varchar(16) NOT NULL COMMENT '每次分享的活动标识key',
  `mch_id` int(11) NOT NULL COMMENT '微信支付商户号',
  `openid` varchar(32) NOT NULL COMMENT '商户管理员openid',
  `coupon_id` int(11) NOT NULL COMMENT '优惠券ID',
  `coupon_name` varchar(32) NOT NULL COMMENT '券名称',
  `percent` int(2) NOT NULL DEFAULT '0' COMMENT '抢中机率',
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8;

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '商户管理员和员工表',
  `mch_id` int(11) NOT NULL,
  `shop_id` int(11) NOT NULL DEFAULT '0',
  `merchant_name` varchar(50) NOT NULL COMMENT '商户简称',
  `branch_name` varchar(50) NOT NULL COMMENT '分店名称',
  `userid` varchar(32) NOT NULL COMMENT '企业微信员工userid',
  `openid` varchar(32) NOT NULL COMMENT '店员在管理后台小程序中的openid',
  `head_img` varchar(255) NOT NULL,
  `name` varchar(32) NOT NULL,
  `mobile` varchar(32) NOT NULL,
  `role` enum('assistant','manager','admin') NOT NULL DEFAULT 'admin' COMMENT '角色，assistanct店员，manager店长，admin管理员',
  `sms_total` int(11) NOT NULL COMMENT '短信数',
  `is_admin` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否为管理员',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1可用，0禁用',
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=185 DEFAULT CHARSET=utf8;

CREATE TABLE `wechat_groupon_pays` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '商户团购订单表',
  `appid` varchar(32) NOT NULL COMMENT '团购下单的小程序appid',
  `mch_id` int(11) NOT NULL,
  `groupon_id` int(11) NOT NULL COMMENT '团购活动id',
  `distribute_id` int(11) NOT NULL DEFAULT '0' COMMENT '分销id',
  `is_together` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否为拼团',
  `is_head` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否为拼团团长',
  `together_no` varchar(12) NOT NULL DEFAULT '0' COMMENT '拼团编号',
  `together_status` enum('open','success','expire') NOT NULL DEFAULT 'open' COMMENT '拼团状态,open拼团中，success拼团成功，expire拼团过期失败',
  `openid` varchar(32) NOT NULL COMMENT '会员小程序openid',
  `coupon_id` int(11) NOT NULL COMMENT '团购券ID',
  `coupon_name` varchar(100) NOT NULL COMMENT '团购券名称',
  `coupon_total` int(11) NOT NULL COMMENT '团购券张数',
  `buy_total` int(11) NOT NULL COMMENT '购买份数',
  `out_trade_no` varchar(32) NOT NULL COMMENT '商户订单号',
  `transaction_id` varchar(50) NOT NULL COMMENT '微信支付订单号',
  `prepay_id` varchar(50) NOT NULL COMMENT '微信支付prepay_id',
  `bank` varchar(32) NOT NULL COMMENT '银行',
  `total_fee` int(11) NOT NULL COMMENT '订单支付金额-分',
  `cash_fee` int(11) NOT NULL COMMENT '实际支付金额-分',
  `coupon_fee` int(11) NOT NULL DEFAULT '0' COMMENT '免充值代金券金额',
  `settlement_total_fee` int(11) NOT NULL DEFAULT '0' COMMENT '应结订单金额',
  `service_fee` int(11) NOT NULL DEFAULT '0' COMMENT '微信支付手续费',
  `refund_fee` float(11,2) NOT NULL DEFAULT '0.00' COMMENT '退款金额',
  `distribute_fee` int(11) NOT NULL DEFAULT '0' COMMENT '分销佣金',
  `together_expired_at` datetime NOT NULL COMMENT '拼团过期时间',
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tid` (`transaction_id`) USING BTREE,
  KEY `openid` (`openid`),
  KEY `tn` (`together_no`)
) ENGINE=InnoDB AUTO_INCREMENT=452 DEFAULT CHARSET=utf8;

CREATE TABLE `wechat_groupon_pays_today` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '商户团购每日营收表',
  `mch_id` int(11) NOT NULL,
  `trade_total` int(11) NOT NULL DEFAULT '0' COMMENT '订单笔数',
  `total` int(11) NOT NULL DEFAULT '0' COMMENT '购买份数',
  `trade` int(11) NOT NULL DEFAULT '0' COMMENT '订单金额',
  `together_total` int(11) NOT NULL DEFAULT '0' COMMENT '拼团订单数',
  `together_success` int(11) NOT NULL DEFAULT '0' COMMENT '拼团成功数',
  `coupon_total` int(11) NOT NULL DEFAULT '0' COMMENT '券售出数',
  `coupon_expired` int(11) NOT NULL DEFAULT '0' COMMENT '券过期数',
  `coupon_used` int(11) NOT NULL DEFAULT '0' COMMENT '券使用数',
  `refund` int(11) NOT NULL DEFAULT '0' COMMENT '退款金额',
  `distribute_bonus` int(11) NOT NULL DEFAULT '0' COMMENT '分销佣金',
  `revenue` int(11) NOT NULL DEFAULT '0' COMMENT '收益',
  `date_at` date NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=140 DEFAULT CHARSET=utf8;

CREATE TABLE `wechat_pay_coupons` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '弃用',
  `appid` varchar(32) NOT NULL,
  `shop_code` varchar(32) NOT NULL,
  `openid` varchar(32) NOT NULL,
  `wechat_pay_id` int(11) NOT NULL,
  `transaction_id` varchar(32) NOT NULL,
  `coupon_id` int(11) NOT NULL,
  `coupon_name` varchar(32) NOT NULL,
  `coupon_total` int(11) NOT NULL,
  `save` int(11) NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `wechat_payed_share_gets` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '商户支付裂变券活动的用户领取券列表',
  `mch_id` int(11) NOT NULL COMMENT '微信支付商户号',
  `share_key` varchar(16) NOT NULL COMMENT '分享活动的标识key',
  `openid` varchar(32) NOT NULL COMMENT '领取用户openid',
  `nickname` varchar(32) NOT NULL COMMENT '领取用户的昵称',
  `headimgurl` varchar(255) NOT NULL COMMENT '头像',
  `coupon_stock_id` int(11) NOT NULL COMMENT '弃用',
  `coupon_id` int(11) NOT NULL COMMENT '微信支付已领取券id',
  `coupon_name` varchar(32) NOT NULL COMMENT '券名称',
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `key` (`share_key`)
) ENGINE=InnoDB AUTO_INCREMENT=80 DEFAULT CHARSET=utf8;

CREATE TABLE `wechat_payed_share_list` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '商户支付裂变券活动会员分享表',
  `mch_id` int(11) NOT NULL COMMENT '微信支付商户号',
  `campaign_id` int(11) NOT NULL COMMENT '活动的campaign_id',
  `share_key` varchar(16) NOT NULL COMMENT '单次分享的标识key',
  `openid` varchar(32) NOT NULL COMMENT '分享者openid',
  `out_trade_no` varchar(32) NOT NULL COMMENT '分享者当笔交易商户订单号',
  `coupon_total` int(2) NOT NULL COMMENT '分享券的数量',
  `get` int(2) NOT NULL COMMENT '已领取数量',
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `key` (`share_key`)
) ENGINE=InnoDB AUTO_INCREMENT=48 DEFAULT CHARSET=utf8;

CREATE TABLE `wechat_pays` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '商户收款订单表',
  `appid` varchar(32) NOT NULL COMMENT '会员发起交易的小程序appid',
  `mch_id` int(11) NOT NULL COMMENT '微信支付商户号',
  `shop_id` int(11) NOT NULL DEFAULT '0' COMMENT '门店编号',
  `pay_type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1微信2支付宝',
  `pay_from` enum('general','recharge','waimai','mall') NOT NULL DEFAULT 'general' COMMENT 'general普通订单，recharge储值订单，waimai外卖订单，mall商城订单',
  `openid` varchar(32) NOT NULL COMMENT '会员在微信公众号下的openid',
  `sub_openid` varchar(32) NOT NULL COMMENT '会员在小程序下的openid',
  `out_trade_no` varchar(32) NOT NULL COMMENT '商户订单号',
  `transaction_id` varchar(50) NOT NULL COMMENT '微信支付订单号',
  `prepay_id` varchar(50) NOT NULL COMMENT '微信支付prepay_id',
  `bank` varchar(32) NOT NULL COMMENT '银行',
  `trade` float(6,2) DEFAULT NULL COMMENT '账单金额',
  `save` float(6,2) NOT NULL DEFAULT '0.00' COMMENT '优惠金额',
  `total_fee` int(11) NOT NULL COMMENT '订单金额-分',
  `cash_fee` int(11) NOT NULL COMMENT '订单实付金额-分',
  `coupon_fee` int(11) NOT NULL DEFAULT '0' COMMENT '微信免充值代金券金额',
  `settlement_total_fee` int(11) NOT NULL DEFAULT '0' COMMENT '应结订单金额',
  `use_coupon_id` int(11) NOT NULL COMMENT '使用优惠券ID',
  `use_coupon_name` varchar(32) NOT NULL COMMENT '使用优惠券名称',
  `use_coupon_total` int(11) NOT NULL DEFAULT '0' COMMENT '使用券数量',
  `use_coupon_amount` float(4,2) NOT NULL DEFAULT '0.00' COMMENT '券抵扣金额',
  `use_point` int(11) NOT NULL DEFAULT '0' COMMENT '使用积分数量',
  `point_amount` int(11) NOT NULL DEFAULT '0' COMMENT '积分抵扣金额',
  `get_point` int(11) NOT NULL DEFAULT '0' COMMENT '返积分数量',
  `use_recharge` float(6,2) NOT NULL DEFAULT '0.00' COMMENT '使用储值余额',
  `use_reduce` float(6,2) NOT NULL DEFAULT '0.00' COMMENT '参加立减活动',
  `use_discount` float(6,2) NOT NULL DEFAULT '0.00' COMMENT '商户折扣活动',
  `member_discount` float(6,2) NOT NULL DEFAULT '0.00' COMMENT '会员等级折扣',
  `service_fee` int(11) NOT NULL DEFAULT '0' COMMENT '微信支付手续费',
  `refund_fee` int(11) NOT NULL DEFAULT '0' COMMENT '退款金额',
  `detail` varchar(255) NOT NULL COMMENT '消费详情',
  `created_by_uid` varchar(32) NOT NULL COMMENT '操作店员的userid',
  `created_by_uname` varchar(32) NOT NULL COMMENT '收款员工',
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tid` (`transaction_id`) USING BTREE,
  UNIQUE KEY `otn` (`out_trade_no`),
  KEY `openid` (`openid`),
  KEY `soid` (`sub_openid`)
) ENGINE=InnoDB AUTO_INCREMENT=25057 DEFAULT CHARSET=utf8;

CREATE TABLE `wechat_pays_today` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '商户每日营销报表',
  `mch_id` int(11) NOT NULL COMMENT '微信支付商户号',
  `shop_id` int(11) NOT NULL DEFAULT '0' COMMENT '门店编号',
  `members` int(11) NOT NULL DEFAULT '0' COMMENT '新增会员数',
  `trade_total` int(11) NOT NULL DEFAULT '0' COMMENT '交易订单笔数',
  `member_trade_total` int(11) NOT NULL DEFAULT '0' COMMENT '会员交易订单笔数',
  `trade_amount` float(9,2) NOT NULL DEFAULT '0.00' COMMENT '交易总金额-元',
  `consumes` float(11,2) NOT NULL DEFAULT '0.00' COMMENT '交易总金额-元',
  `member_consumes` float(11,2) NOT NULL DEFAULT '0.00' COMMENT '会员交易额-元',
  `recharges_total` int(9) NOT NULL DEFAULT '0' COMMENT '储值笔数',
  `recharges` float(8,2) NOT NULL DEFAULT '0.00' COMMENT '储值金额-元',
  `use_coupon_total` int(11) NOT NULL DEFAULT '0' COMMENT '核销优惠券张数',
  `use_coupon_amount` float(9,2) NOT NULL DEFAULT '0.00' COMMENT '核销优惠券金额-元',
  `use_point` int(11) NOT NULL DEFAULT '0' COMMENT '抵扣积分',
  `get_point` int(11) NOT NULL DEFAULT '0' COMMENT '返积分',
  `save` float(9,2) NOT NULL DEFAULT '0.00' COMMENT '优惠金额',
  `use_recharge` float(9,2) NOT NULL DEFAULT '0.00' COMMENT '使用储值余额支付金额-元',
  `discount` float(9,2) NOT NULL DEFAULT '0.00' COMMENT '折扣金额-元',
  `member_discount` float(9,2) NOT NULL DEFAULT '0.00' COMMENT '会员折扣金额-元',
  `point_amount` float(9,2) NOT NULL DEFAULT '0.00' COMMENT '积分抵扣金额-元',
  `reduce` float(9,2) NOT NULL DEFAULT '0.00' COMMENT '会员立减金额-元',
  `service_fee` int(11) NOT NULL DEFAULT '0' COMMENT '支付手续费',
  `service_fee_wechat` int(11) NOT NULL DEFAULT '0' COMMENT '微信支付手续费',
  `service_fee_alipay` int(11) NOT NULL DEFAULT '0' COMMENT '支付宝手续费',
  `revenue_fee_wechat` int(11) NOT NULL DEFAULT '0' COMMENT '微信支付返佣',
  `revenue_fee_alipay` int(11) NOT NULL COMMENT '支付宝返佣',
  `coupon_fee` int(11) NOT NULL DEFAULT '0' COMMENT '微信免充值代金券金额',
  `settlement_total_fee` int(11) NOT NULL DEFAULT '0' COMMENT '应结订单金额',
  `refund_fee_wechat` int(11) NOT NULL DEFAULT '0' COMMENT '微信退款',
  `consumes_wechat` float(11,2) NOT NULL DEFAULT '0.00' COMMENT '微信支付订单金额-元',
  `consumes_alipay` float(9,2) NOT NULL DEFAULT '0.00' COMMENT '支付宝订单支付金额-元',
  `consumes_other` float(9,2) NOT NULL DEFAULT '0.00' COMMENT '其它实收',
  `waimai_revenue` float(9,2) NOT NULL DEFAULT '0.00' COMMENT '外卖收入-元',
  `mall_revenue` float(9,2) NOT NULL DEFAULT '0.00' COMMENT '商城收入-元',
  `groupon_revenue` float(9,2) NOT NULL DEFAULT '0.00' COMMENT '团购收入-元',
  `vipcard_revenue` float(9,2) NOT NULL DEFAULT '0.00' COMMENT '付费卡收入',
  `date_at` date NOT NULL COMMENT '日期',
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1901 DEFAULT CHARSET=utf8;

CREATE TABLE `wechat_refunds` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '商户退款订单表',
  `appid` varchar(32) NOT NULL COMMENT '会员支付时发起订单的小程序appid',
  `mch_id` int(11) NOT NULL COMMENT '微信支付商户号',
  `openid` varchar(32) NOT NULL COMMENT '会员在小程序中的openid',
  `refund_type` enum('trade','groupon') DEFAULT 'trade' COMMENT '退款类型，trade普通订单退款，groupon团购退款',
  `out_trade_no` varchar(32) NOT NULL COMMENT '发起支付的商户订单号',
  `out_refund_no` varchar(32) NOT NULL COMMENT '退款交易的商户订单号',
  `refund_id` varchar(32) NOT NULL COMMENT '微信退款单号',
  `total_fee` int(11) NOT NULL COMMENT '退款金额-分',
  `refund_fee` int(11) NOT NULL COMMENT '申请退款金额',
  `cash_refund_fee` int(11) NOT NULL COMMENT '实际退款金额',
  `coupon_refund_fee` int(11) NOT NULL DEFAULT '0' COMMENT '代金券退款金额',
  `refund_desc` varchar(255) NOT NULL,
  `created_by_uid` int(11) NOT NULL DEFAULT '0' COMMENT '操作店员userid',
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=96 DEFAULT CHARSET=utf8;

CREATE TABLE `wechat_vipcard_pays` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '商户付费卡售卖订单表',
  `appid` varchar(32) NOT NULL COMMENT '下单小程序appid',
  `mch_id` int(11) NOT NULL COMMENT '微信支付商户号',
  `vipcard_grade` int(11) NOT NULL COMMENT '付费卡会员等级',
  `vipcard_title` varchar(32) NOT NULL COMMENT '付费卡等级名称',
  `openid` varchar(32) NOT NULL COMMENT '会员在微信公众号下的openid',
  `sub_openid` varchar(32) NOT NULL COMMENT '会员在下单小程序中的openid',
  `out_trade_no` varchar(32) NOT NULL COMMENT '商户订单号',
  `transaction_id` varchar(50) NOT NULL COMMENT '微信支付订单号',
  `prepay_id` varchar(50) NOT NULL COMMENT '微信支付订单prepay_id',
  `bank` varchar(32) NOT NULL COMMENT '银行',
  `trade` float(9,2) NOT NULL DEFAULT '0.00' COMMENT '订单金额-元',
  `cash_fee` int(11) NOT NULL COMMENT '订单金额-分',
  `service_fee` int(11) NOT NULL DEFAULT '0' COMMENT '微信支付手续费',
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tid` (`transaction_id`) USING BTREE,
  KEY `openid` (`openid`)
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8;

SET FOREIGN_KEY_CHECKS = 1;

