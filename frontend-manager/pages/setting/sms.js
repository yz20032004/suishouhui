// pages/setting/sms.js
const host = require('../../config').host
const paymentUrl = ''

var app = getApp()
Page({
  data: {},
  onLoad: function (options) {
    var user = wx.getStorageSync('user')
    this.setData({
      sms_total: user.sms_total
    })
  },
  onReady: function () {
    // 页面渲染完成
  },
  onShow: function () {
  },
  onHide: function () {
    // 页面隐藏
  },
  onUnload: function () {
    // 页面关闭
  },
  requestPayment: function (e) {
    var sms_total = e.currentTarget.dataset.total
    var trade = e.currentTarget.dataset.trade
    var that = this
    wx.request({
      url: host + 'pay.php?action=getSmsPrepay',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
        openid: wx.getStorageSync('openid'),
        sms_total:sms_total,
        trade: trade,
      },
      success: function (res) {
        var payargs = res.data
        wx.requestPayment({
          'timeStamp': payargs.timeStamp,
          'nonceStr': payargs.nonceStr,
          'package': payargs.package,
          'signType': payargs.signType,
          'paySign': payargs.paySign,
          'success': function (res) {
            var user = wx.getStorageSync('user')
            user.sms_total = parseInt(user.sms_total) + parseInt(sms_total)
            wx.setStorageSync('user', user)
            that.setData({
              sms_total:user.sms_total
            })
            wx.showToast({
              title: '充值成功',
              duration:2000
            })
          }
        })
      }
    })
  }
})
