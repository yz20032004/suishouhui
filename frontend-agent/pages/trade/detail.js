// pages/trade/detail.js
const host = require('../../config').host
var app = getApp()
Page({
  data: {
    pay_image:'wechatpay'
  },
  onLoad: function (options) {
    var out_trade_no = options.out_trade_no
    this.get_detail(out_trade_no)
  },
  get_detail: function (out_trade_no) {
    var that = this
    wx.request({
      url: host + 'ssh_trade.php?action=get_detail',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
        out_trade_no: out_trade_no
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        var point_amount = '0'
        if ('1' == res.data.pay_type) {
          var pay_image = 'wechatpay'
        } else if ('2' == res.data.pay_type) {
          var pay_image = 'alipay'
        } else if ('3' == res.data.pay_type) {
          var pay_image = 'recharge'
        } else {
          var pay_image = 'cash'
        }
        that.setData({
          tradeData: res.data,
          pay_image:pay_image
        })
      }
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
  },
  back: function () {
    wx.navigateBack({
      delta: 1
    })
  }
})
