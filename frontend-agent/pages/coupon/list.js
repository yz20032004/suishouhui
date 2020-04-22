// pages/coupon/list.js
var sliderWidth = 96; // 需要设置slider的宽度，用于计算中间位置
const host = require('../../config').host + 'ssh_'
Page({
  data: {
    tabs: ["可用券", "未生效", "已过期", "无库存"],
    coupon_types: ["代金券", "礼品券", "折扣券"],
    activeIndex: 0,
    sliderOffset: 0,
    sliderLeft: 0,
    coupon_typeIndex: 0,
  },
  onLoad: function () {
    var that = this;
    var coupon_types = this.data.coupon_types
    var mch  = wx.getStorageSync('mch')
    if ('1' == mch.is_groupon) {
      coupon_types.push('团购券')
    }
    if ('1' == mch.is_timing) {
      coupon_types.push('次卡券')
    }
    wx.getSystemInfo({
      success: function (res) {
        that.setData({
          sliderLeft: (res.windowWidth / that.data.tabs.length - sliderWidth) / 2,
          sliderOffset: res.windowWidth / that.data.tabs.length * that.data.activeIndex,
          user: wx.getStorageSync('user'),
          coupon_types:coupon_types
        });
      }
    });
  },
  onShow: function () {
    // 页面显示
    var that = this
    wx.request({
      url: host + 'coupon.php?action=get_list',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
        get_type:'all'
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        that.setData({
          couponData: res.data
        })
      }
    })
  },
  tabClick: function (e) {
    this.setData({
      sliderOffset: e.currentTarget.offsetLeft,
      activeIndex: e.currentTarget.id
    });
  },
  bindCouponTypeChange: function (e) {
    if ('0' == e.detail.value) {
      var type = 'cash'
    } else if ('1' == e.detail.value) {
      var type = 'gift'
    } else if ('2' == e.detail.value) {
      var type = 'discount'
    } else if ('3' == e.detail.value) {
      if ('团购券' == this.data.coupon_types[3]) {
        var type = 'groupon'
      } else {
        var type = 'timing'
      }
    } else {
      var type = 'timing'
    }
    wx.navigateTo({
      url: 'create?type=' + type
    })
  }
});
