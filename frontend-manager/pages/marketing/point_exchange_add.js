// pages/marketing/point_exchange_add.js
const host = require('../../config').host
var app = getApp()
Page({

  /**
   * 页面的初始数据
   */
  data: {
    display:'none',
    single_limit:'不限制',
    counts: ['不限制', 1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
    countIndex: 0,
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    var that = this
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
        ret['enable'][ret['enable'].length] = { id: 0, name: '请选择' }
        that.setData({
          coupons: ret['enable'],
          couponIndex: ret['enable'].length - 1
        })
      }
    })
  },

  /**
   * 生命周期函数--监听页面初次渲染完成
   */
  onReady: function () {

  },

  /**
   * 生命周期函数--监听页面显示
   */
  onShow: function () {

  },

  /**
   * 生命周期函数--监听页面隐藏
   */
  onHide: function () {

  },

  /**
   * 生命周期函数--监听页面卸载
   */
  onUnload: function () {

  },

  /**
   * 页面相关事件处理函数--监听用户下拉动作
   */
  onPullDownRefresh: function () {

  },

  /**
   * 页面上拉触底事件的处理函数
   */
  onReachBottom: function () {

  },

  /**
   * 页面上拉触底事件的处理函数
   */
  onReachBottom: function () {

  },

  /**
   * 页面相关事件处理函数--监听用户下拉动作
   */
  onPullDownRefresh: function () {

  },
  submit: function (e) {
    if ('admin' != wx.getStorageSync('user_role')) {
      wx.showModal({
        title: '温馨提示',
        content: '体验者或非管理员身份不可执行此操作',
        showCancel: false
      })
      return
    }
    var point = e.detail.value.point
    var coupon_id = e.detail.value.coupon_id
    var is_limit = e.detail.value.is_limit
    var exchange_limit = e.detail.value.exchange_limit
    var single_limit = this.data.countIndex != 0 ? e.detail.value.single_limit : 0
    if ('0' == coupon_id) {
      wx.showModal({
        title: "请选择优惠券",
        content: "",
        showCancel: false,
        confirmText: "确定"
      })
      return false
    }
    if (!point) {
      wx.showModal({
        title: "请填写积分",
        content: "",
        showCancel: false,
        confirmText: "确定"
      })
      return false
    }
    if (is_limit && !exchange_limit) {
      wx.showModal({
        title: "请填写总兑换数量",
        content: "",
        showCancel: false,
        confirmText: "确定"
      })
      return false
    }
    wx.request({
      url: host + 'marketing.php?action=point_exchange_add',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
        point: point,
        coupon_id: coupon_id,
        is_limit:is_limit?1:0,
        exchange_limit:exchange_limit,
        single_limit:single_limit
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        if ('fail' == res.data) {
          wx.showModal({
            title: "积分兑换规则不能重复",
            content: "",
            showCancel: false,
            confirmText: "确定"
          })
          return false
        } else {
          wx.removeStorageSync('merchant_exchange_rule')
          app.loadMerchantExchangeRules()
          wx.showToast({
            title: '操作成功',
            icon: 'success',
            duration: 2000,
            success: function () {
              wx.navigateBack({ url: '../marketing/point_exchange' })
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
    var that = this
    var single_limit = that.data.counts[e.detail.value] + '张'
    if (e.detail.value == 0) {
      var single_limit = '不限制'
    }
    this.setData({
      countIndex: e.detail.value,
      single_limit: single_limit
    })
  },
  limitSwitch: function (e) {
    var isChecked = e.detail.value
    if (isChecked) {
      this.setData({
        display: ''
      })
    } else {
      this.setData({
        display: 'none',
      })
    }
  },
  back: function () {
    wx.navigateBack({
      delta: 1
    })
  }
})
