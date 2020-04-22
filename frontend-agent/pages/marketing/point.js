// pages/setting/point.js
const host = require('../../config').host + 'ssh_'
var app = getApp()
Page({
  data: {
    counts: [1, 2, 5, 10, 15, 20, 25, 30, 45, 50],
    point_speed_range: [
      { speed: '0', title: "不返积分" },
      { speed: '1', title: "返1倍积分" },
      { speed: '1.1', title: "返1.1倍积分" },
      { speed: '1.2', title: "返1.2倍积分" },
      { speed: '1.5', title: "返1.5倍积分" },
      { speed: '2', title: "返2倍积分" },
      { speed: '2.5', title: "返2.5倍积分" },
      { speed: '3', title: "返3倍积分" },
      { speed: '3.5', title: "返3.5倍积分" },
      { speed: '4', title: "返4倍积分" },
      { speed: '4.5', title: "返4.5倍积分" },
      { speed: '5', title: "返5倍积分" },
      { speed: '6', title: "返6倍积分" },
      { speed: '7', title: "返7倍积分" },
      { speed: '8', title: "返8倍积分" },
      { speed: '9', title: "返9倍积分" },
      { speed: '10', title: "返10倍积分" }
    ],
    rechargePointIndex:0,
    rebate_total:0,
    use_total:0,
    rebatePointIndex: 0,
    usePointIndex:0,
    exchange_display: 'none'
  },
  onLoad: function (options) {
    // 页面初始化 options为页面跳转所带来的参数
    var that = this
    wx.request({
      url: host + 'mch.php?action=get_point_rule',
      data: {
        mch_id: wx.getStorageSync('mch_id')
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        for(var i=0;i<that.data.counts.length;i++){
          if (res.data.award_need_consume == that.data.counts[i]) {
            var rebatePointIndex = i
          }
          if (res.data.exchange_need_points == that.data.counts[i]) {
            var usePointIndex = i
          }
        }
        var recharge_point_speed = parseFloat(res.data.recharge_point_speed)
        for (var i = 0; i < that.data.point_speed_range.length; i++) {
          if (recharge_point_speed == that.data.point_speed_range[i].speed) {
            var rechargePointIndex = i
          }
        }
        var merchant = wx.getStorageSync('mch')
        that.setData({
          marketing_type: merchant.marketing_type,
          rule: res.data,
          point_open: res.data.id ? 'checked' : '',
          exchange_display: res.data.can_used_for_money == '1' ? '' : 'none',
          exchange_need_points: res.data.exchange_need_points,
          can_cash: res.data.can_used_for_money == '1' ? true : false,
          rebatePointIndex:rebatePointIndex,
          usePointIndex:usePointIndex,
          rechargePointIndex:rechargePointIndex
        })
      }
    })
  },
  exchangePointSwitch: function (e) {
    var isChecked = e.detail.value
    if (isChecked) {
      this.setData({
        exchange_display: ''
      })
    } else {
      this.setData({
        exchange_display: 'none'
      })
    }
  },
  submit: function (e) { 
    var that = this
    var can_cash = e.detail.value.can_cash
    var award_need_consume = this.data.counts[e.detail.value.award_need_consume]
    var exchange_need_points = this.data.counts[e.detail.value.exchange_need_points]
    var recharge_point_speed = 'pay' != this.data.marketing_type ? e.detail.value.recharge_point_speed : 0
    wx.request({
      url: host + 'mch.php?action=update_point_rule',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
        can_cash: can_cash ? 1 : 0,
        award_need_consume:award_need_consume,
        exchange_need_points:exchange_need_points,
        recharge_point_speed: recharge_point_speed
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        wx.showToast({
          title: '操作成功',
          icon: 'success',
          duration: 2000
        })
      }
    })
  }, 
  bindRebatePointChange: function (e) {
    var that = this
    this.setData({
      rebatePointIndex: e.detail.value,
      rebate_point: that.data.counts[e.detail.value]
    })
  },
  bindUsePointChange: function (e) {
    var that = this
    this.setData({
      usePointIndex: e.detail.value,
      use_point: that.data.counts[e.detail.value]
    })
  },
  bindRechargePointChange: function (e) {
    var that = this
    this.setData({
      rechargePointIndex: e.detail.value
    })
  },
  back: function () {
    wx.navigateBack({
      delta: 1
    })
  },
  onReady: function () {
    // 页面渲染完成
  },
  onShow: function () {
    // 页面显示
  },
  onHide: function () {
    // 页面隐藏
  },
  onUnload: function () {
    // 页面关闭
  }
})
