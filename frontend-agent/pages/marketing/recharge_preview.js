// pages/setting/recharge_preview.js
const host = require('../../config').host
Page({
  data: {},
  onLoad: function (options) {
    // 页面初始化 options为页面跳转所带来的参数
  },
  onReady: function () {
    // 页面渲染完成
  },
  onShow: function () {
    // 页面显示
    var that = this
    wx.request({
      url: host + 'ssh_marketing.php?action=get_recharges',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        that.setData({
          recharges: res.data,
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
  add: function () {
    wx.navigateTo({
      url: 'recharge',
    })
  },
  edit: function (e) {
    var id = e.currentTarget.dataset.id
    wx.navigateTo({
      url: 'recharge_edit?id=' + id,
    })
  },
  back: function () {
    wx.navigateBack({
      delta: 1
    })
  }
})
