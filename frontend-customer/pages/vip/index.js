//index.js
//获取应用实例
const app = getApp()
const host = require('../../config').host
Page({
  data: {
    showGroupTip: false,
    is_selfpay: true,
    is_load: false
  },
  //事件处理函数
  onReady: function () {
    wx.hideLoading()
  },
  onLoad: function (options) {
    wx.showLoading({
      title: '加载中',
    })
    var that = this
    this.data.interval = setInterval(
      function () {
        if (wx.getStorageSync('openid')) {
          clearInterval(that.data.interval)
          if (!wx.getStorageSync('is_member')) {
            wx.redirectTo({
              url: '../index/get_membercard?mch_id='+wx.getStorageSync('mch_id'),
            })
          } else {
            that.initIndex()
          }
        }
      }, 200);
  },
  onHide: function (e) {
    this.setData({
      showGroupTip: false,
      inited: false
    })
  },
  initIndex() {
    this.getMemberDetail()
    var merchant = wx.getStorageSync('merchant')
    wx.setNavigationBarTitle({
      title: merchant.merchant_name
    })
    this.setData({
      is_load: true
    })
  },
  onShow: function () {
    if (this.data.is_load) {
      this.getMemberDetail()
    }
  },
  getMemberDetail: function () {
    var that = this
    wx.request({
      url: host + 'huipay/user.php?action=get_mch_detail',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
        openid: wx.getStorageSync('openid'),
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        wx.setStorageSync('member', res.data)
        that.setData({
          member: res.data,
        })
      }
    })
  },
  openPoint: function () {
    wx.navigateTo({
      url: '../point/list',
    })
  },
  openBalanceList: function () {
    wx.navigateTo({
      url: '../recharge/list',
    })
  },
  openCouponList: function () {
    wx.navigateTo({
      url: '../coupon/list',
    })
  },
  open_grades: function () {
    wx.navigateTo({
      url: '../vip/grade',
    })
  },
  openvip: function () {
    wx.switchTab({
      url: '../vip/index',
    })
  },
  open_card: function () {
    var mch_id = wx.getStorageSync('mch_id')
    var member = wx.getStorageSync('member')
    wx.navigateTo({
      url: 'get_membercard?key=key&get_point=0&mch_id=' + mch_id + '&grade=' + member.grade
    })
  },
  openPoint: function () {
    wx.navigateTo({
      url: '../vip/point_history',
    })
  },
  openBill: function () {
    wx.switchTab({
      url: '../index/bill',
    })
  },
  openCouponList: function () {
    wx.navigateTo({
      url: '../coupon/list',
    })
  },
  open_grades: function () {
    wx.navigateTo({
      url: '../vip/grade',
    })
  },
  opencard: function () {
    wx.navigateTo({
      url: '../vip/qrcode',
    })
  },
  getmembercard: function () {
    wx.navigateTo({
      url: 'get_membercard?mch_id=' + wx.getStorageSync('mch_id'),
    })
  },
  onShareAppMessage: function (res) {
    var that = this
    var shop = wx.getStorageSync('shop')
    return {
      title: '我邀请您加入' + shop.business_name + '会员，尊享开卡礼和会员特权',
      imageUrl: shop.logo_url,
      path: '/pages/index/get_membercard?mch_id=' + shop.mch_id
    }
  }
})