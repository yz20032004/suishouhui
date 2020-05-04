# 随手惠会员营销小程序

#### 介绍
为线下中小微实体店铺提供轻松简便的会员营销系统，功能包括会员等级和权益设计，会员开卡礼，消费返积分，积分兑换，优惠券的创建/投放/核销和裂变分发，储值，群发优惠券和群发短信等会员营销功能，以及团购、拼团、次卡、商城、付费卡、外卖、点餐等线上交易功能。包括顾客端小程序、商户端小程序和代理商小程序。

<img src="https://images.gitee.com/uploads/images/2020/0421/115035_73a08d00_1351473.jpeg">

<img src="https://images.gitee.com/uploads/images/2020/0421/114543_fca92fcd_1351473.jpeg" width="800px">

<img src="https://images.gitee.com/uploads/images/2020/0421/121459_c84d8b36_1351473.jpeg">

#### 微信扫码支付1分钱，体验支付后加会员(顾客端小程序）
<img src="https://images.gitee.com/uploads/images/2020/0421/114544_9c10c8f2_1351473.jpeg" width="500px">


#### 运行环境
PHP+MYSQL+REDIS

#### 存储
阿里云OSS

#### 短信平台
阿里云短信

#### 外卖、点餐、顾客调查表单依赖
表单大师 http://www.jsform.com

#### 小票订单打印
佳博云打印 https://cp.poscom.cn

#### 收款语音播报
广州飞鱼科技 FY100语音播报 http://www.gzfyit.com/

#### 扫码收款POS机
商睿S1手持扫码POS机 http://www.senraise.com/ 使用腾讯云支付对接


#### 程序架构
suishouhui
##### ├── frontend-customer --顾客端小程序
##### ├── frontend-manager  --商户端小程序
##### ├── frontend-agent    --代理商端小程序
##### ├── frontend-customer-template  --顾客端小程序模板（供微信开放平台使用，生成商户主体的独立小程序）
##### ├── backend           --服务端脚本，供小程序调用
##### ├── backend-websocket --新浪云websocket程序
##### ├── backend-open      --微信开放平台事件回调
##### ├── backend-template  --供微信开放平台生成的小程序调用的后台服务器程序
##### ├── crontab-and-jobs  --服务端定时任务和守护进程
##### ├── sql-data          --数据结构和测试数据

#### 安装教程

1.  搭建小程序运行环境和服务端LAMP+REDIS运行环境
2.  配置微信公众号参数、小程序参数和服务端参数
3.  创建数据库
4.  导入测试数据
5.  在小程序IDE中运行小程序


#### 参与贡献

1.  Fork 本仓库
2.  新建 Feat_xxx 分支
3.  提交代码
4.  新建 Pull Request

#### 微信 mikeyang
