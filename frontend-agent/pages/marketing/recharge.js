// pages/setting/recharge.js
const host = require('../../config').host + 'ssh_'
var app = getApp()
Page({
  data: {
    btn_disabled: false,
    rules: [
      { type: 'money_constant', title: "返固定金额" },
      { type: 'money_percent', title: "返充值比例金额" },
      { type: 'coupon', title: "返优惠券" }
    ],
    ruleIndex: 0,
    counts: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
    countIndex: 0,
    money_constant_display: '',
    money_percent_display: 'none',
    coupon_display: 'none',
    couponData: new Array()
  },
  onLoad: function (options) {
    // 页面初始化 options为页面跳转所带来的参数
  },
  onReady: function () {
    // 页面渲染完成
  },
  onShow: function () {
    // 页面显示
    var mch = wx.getStorageSync('mch')
    this.setData({
      btn_disabled: '0' == mch.is_recharge ? true : false
    })
  },
  onHide: function () {
    // 页面隐藏
  },
  onUnload: function () {
    // 页面关闭
  },
  submit: function (e) {
    var touch = e.detail.value.touch
    var award_type = e.detail.value.award_type
    var amount = e.detail.value.amount
    var percent = e.detail.value.percent
    var coupon_id = e.detail.value.coupon_id
    var coupon_name = 'coupon'==award_type?this.data.coupons[this.data.couponIndex].name:''
    var card_id = 'coupon'==award_type?this.data.coupons[this.data.couponIndex].wechat_cardid:''
    var count = 'coupon'==award_type?parseInt(e.detail.value.count_id) + parseInt(1):0
    var remark = e.detail.value.remark
    if (!touch || parseInt(touch) <= 0) {
      wx.showModal({
        title: "请填写储值金额",
        content: "",
        showCancel: false,
        confirmText: "确定"
      })
      return false
    }
    var merchant = wx.getStorageSync('mch')
    if ('xiaowei'==merchant.mch_type) {
      if (touch > 500) {
        wx.showModal({
          title: "小微商户储值金额最高不得超过500元",
          content: "",
          showCancel: false,
          confirmText: "确定"
        })
        return false
      }
    } else {
      if (touch > 5000) {
        wx.showModal({
          title: "储值金额最高不得超过5000元",
          content: "",
          showCancel: false,
          confirmText: "确定"
        })
        return false
      }
    }
    if ('money_constant' == award_type) {
      if (!amount) {
        wx.showModal({
          title: "请填写奖励金额",
          content: "",
          showCancel: false,
          confirmText: "确定"
        })
        return false
      }
    } else if ('money_percent' == award_type) {
      if (!percent) {
        wx.showModal({
          title: "请填写奖励百分比",
          content: "",
          showCancel: false,
          confirmText: "确定"
        })
        return false
      }
    } else {
      if (!coupon_id) {
        wx.showModal({
          title: "请选择奖励优惠券",
          content: "",
          showCancel: false,
          confirmText: "确定"
        })
        return false
      }
    }
    wx.request({
      url: host + 'marketing.php?action=add_recharge',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
        touch: touch,
        award_type: award_type,
        amount: amount,
        percent: percent,
        coupon_id: coupon_id,
        card_id:card_id,
        coupon_name:coupon_name,
        count: count,
        remark:remark
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        if ('fail' == res.data) {
          wx.showModal({
            title: "已经有这一档的储值规则了",
            content: "请重新填写其它储值金额",
            showCancel: false,
            confirmText: "确定"
          })
          return false
        } else {
          wx.showToast({
            title: "操作成功",
            content: "",
            icon: 'success',
            duration: 2000,
            success: function (res) {
              wx.navigateTo({ url: 'recharge_preview' })
            }
          })
        }
      }
    })
  },
  bindRuleChange: function (e) {
    var that = this
    var money_constant_display = 'none'
    var money_percent_display = 'none'
    var coupon_display = 'none'
    if ('0' == e.detail.value) {
      money_constant_display = ''
    } else if ('1' == e.detail.value) {
      money_percent_display = ''
    } else {
      coupon_display = ''
      wx.request({
        url: host + 'coupon.php?action=get_list',
        data: {
          mch_id: wx.getStorageSync('mch_id')
        },
        header: {
          'content-type': 'application/json'
        },
        success: function (res) {
          var ret = res.data
          for (var i = 0; i < ret['unenable'].length; i++) {
            ret['enable'][ret['enable'].length + i] = ret['unenable'][i]
          }
          that.setData({
            coupons: ret['enable'],
            couponIndex: 0
          })
        }
      })
    }
    this.setData({
      ruleIndex: e.detail.value,
      money_constant_display: money_constant_display,
      money_percent_display: money_percent_display,
      coupon_display: coupon_display
    })
  },
  bindCouponChange: function (e) {
    this.setData({
      couponIndex: e.detail.value
    })
  },
  bindCountChange: function (e) {
    this.setData({
      countIndex: e.detail.value
    })
  },
  back: function () {
    wx.navigateBack({
      delta: 1
    })
  }
})
