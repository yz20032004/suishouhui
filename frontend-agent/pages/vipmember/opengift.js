// pages/vipmember/opencard.js
const host = require('../../config').host
var myDate = new Date()
Page({
  data: {
    giftData: [],
    counts: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
    countIndex: 0
  },
  onLoad: function (options) {
    // 页面初始化 options为页面跳转所带来的参数
    var that = this
    wx.request({
      url: host + 'ssh_coupon.php?action=get_list',
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
        ret['enable'][ret['enable'].length] = { id: 0, name: '请选择' }
        that.setData({
          coupons: ret['enable'],
          couponIndex: ret['enable'].length - 1
        })
      }
    })
  },
  onReady: function () {
    // 页面渲染完成
  },
  onShow: function () {
    if (wx.getStorageSync('vipmember_opengifts')) {
      this.setData({
        giftData: wx.getStorageSync('vipmember_opengifts')
      })
    }
  },
  onHide: function () {
    // 页面隐藏
  },
  onUnload: function () {
    // 页面关闭
  },
  submit: function (e) {
    var that = this
    var coupon_id = e.detail.value.coupon_id
    if ('0' == coupon_id) {
      wx.showModal({
        title: "请选择优惠券",
        content: "",
        showCancel: false,
        confirmText: "确定"
      })
      return false
    }
    var giftData = this.data.giftData
    for (var i = 0; i < giftData.length; i++) {
      if (giftData[i].coupon_id == coupon_id) {
        wx.showModal({
          title: '已经有此优惠券了',
          content: '',
          showCancel: false
        })
        return
      }
    }
    var newGift = {
      coupon_id: coupon_id,
      coupon_name: this.data.coupons[this.data.couponIndex].name,
      coupon_total: parseInt(e.detail.value.count) + parseInt(1)
    }
    giftData[giftData.length] = newGift
    console.log(giftData)
    this.setData({
      giftData: giftData,
      couponIndex: that.data.coupons.length - 1
    })
    wx.setStorageSync('vipmember_opengifts', giftData)
  },
  del: function (e) {
    var id = e.currentTarget.dataset.id
    var giftData = this.data.giftData
    giftData.splice(id, 1)
    this.setData({
      giftData: giftData
    })
    wx.setStorageSync('vipmember_opengifts', giftData)
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
  back: function (e) {
    wx.navigateBack({
      delta: 1
    })
  }
})
