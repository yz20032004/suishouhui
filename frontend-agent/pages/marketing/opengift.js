// pages/marketing/opencard.js
const host = require('../../config').host
var myDate = new Date()
Page({
  data: {
    date: "2016-09-01",
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
    var that = this
    wx.request({
      url: host + 'ssh_marketing.php?action=get_opengifts',
      data: {
        mch_id: wx.getStorageSync('mch_id')
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        that.setData({
          giftData: res.data
        })
      }
    })
  },
  onHide: function () {
    // 页面隐藏
  },
  onUnload: function () {
    // 页面关闭
  },
  submit: function (e) {
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
    wx.request({
      url: host + 'ssh_marketing.php?action=opengift',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
        coupon_count: parseInt(e.detail.value.count) + parseInt(1),
        coupon_id: coupon_id
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        if ('fail' == res.data) {
          wx.showModal({
            title: "开卡礼中已有该优惠券",
            content: "",
            showCancel: false,
            confirmText: "确定"
          })
          return false
        } else {
          wx.showToast({
            title: '操作成功',
            icon: 'success',
            duration: 2000,
            success: function () {
              wx.navigateTo({ url: 'opengift' })
            }
          })
        }
      }
    })
  },
  del:function(e){
    var id = e.currentTarget.dataset.id
    wx.showModal({
      title: '确定要删除该开卡礼吗？',
      success(res) {
        if (res.confirm) {
          wx.request({
            url: host + 'ssh_marketing.php?action=delete_opengift',
            data: {
              mch_id: wx.getStorageSync('mch_id'),
              id: id
            },
            header: {
              'content-type': 'application/json'
            },
            success: function (res) {
              wx.navigateTo({ url: 'opengift' })
            }
          })
        }
      }
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
  back: function (e) {
    wx.navigateBack({
      delta: 1
    })
  }
})
