// pages/member/trade_list.js
const host = require('../../config').host
Page({
  data: {},
  onLoad: function (options) {
    var that = this
    var openid = options.openid
    wx.request({
      url: host + 'member.php?action=trade_list',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
        openid: openid
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        that.setData({
          tradeList: res.data,
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
  }
})
