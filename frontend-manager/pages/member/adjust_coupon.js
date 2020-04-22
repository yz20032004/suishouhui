// pages/member/adjust_coupon.js
const host = require('../../config').host
var app = getApp()
var date = new Date()
Page({
  data: {
    coupons: null,
    counts: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
    countIndex: 0,
    coupon_total: 1,
  },
  onLoad: function(options) {
    // 页面初始化 options为页面跳转所带来的参数
    var openid = options.openid
    var name = options.name
    var that = this
    this.setData({
      openid:openid
    })
    wx.request({
      url: host + 'coupon.php?action=get_list',
      data: {
        mch_id: wx.getStorageSync('mch_id')
      },
      header: {
        'content-type': 'application/json'
      },
      success: function(res) {
        var ret = res.data
        for (var i = 0; i < ret['unenable'].length; i++) {
          ret['enable'][ret['enable'].length + i] = ret['unenable'][i]
        }
        ret['enable'][ret['enable'].length] = {
          id: 0,
          name: '请选择'
        }
        that.setData({
          coupons: ret['enable'],
          couponIndex: ret['enable'].length - 1,
          name: name
        })
      }
    })
  },
  onReady: function() {
    // 页面渲染完成
  },
  onShow: function() {
    // 页面显示
  },
  onHide: function() {
    // 页面隐藏
  },
  onUnload: function() {
    // 页面关闭
  },
  submit: function(e) {
    var that = this
    if ('admin' != wx.getStorageSync('user_role')) {
      wx.showModal({
        title: '温馨提示',
        content: '体验者或非管理员身份不可执行此操作',
        showCancel: false
      })
      return
    }
    var coupon_id = e.detail.value.coupon_id
    var count = this.data.counts[e.detail.value.count]
    if ('0' == coupon_id) {
      wx.showModal({
        title: "请选择赠送优惠券",
        content: "",
        showCancel: false,
        confirmText: "确定"
      })
      return false
    }
    var that = this
    wx.request({
      url: host + 'member.php?action=send_coupon',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
        openid: that.data.openid,
        coupon_id: coupon_id,
        count: count,
      },
      header: {
        'content-type': 'application/json'
      },
      success: function(res) {
        wx.showModal({
          title: '操作成功',
          content: '',
          showCancel:false,
          success:function(){
            wx.navigateBack({
              delta: 1
            })
          }
        })
      }
    })
  },
  bindCouponChange: function(e) {
    this.setData({
      couponIndex: e.detail.value
    })
  },
  bindCountChange: function(e) {
    var that = this
    this.setData({
      countIndex: e.detail.value,
    })
  },
  back: function() {
    wx.navigateBack({
      delta: 1
    })
  }
})