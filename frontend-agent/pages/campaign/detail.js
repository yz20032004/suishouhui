// pages/campaign/detail.js
const host = require('../../config').host
Page({
  data: {},
  onLoad: function (options) {
    // 页面初始化 options为页面跳转所带来的参数
    var campaign_id = options.id
    var that = this
    wx.request({
      url: host + 'ssh_marketing.php?action=get_detail',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
        id: campaign_id
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        that.setData({
          campaignData: res.data
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
  stop: function (e) {
    var campaign_id = e.target.dataset.id
    var that = this
    wx.request({
      url: host + 'ssh_marketing.php?action=stop',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
        id: campaign_id
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        wx.showModal({
          title: "活动已被终止",
          content: "",
          showCancel: false,
          confirmText: "确定",
          success: function () {
            wx.redirectTo({
              url: 'detail?id=' + campaign_id,
            })
          }
        })
      }
    })
  },
  back: function () {
    wx.navigateBack({
      delta: 1
    })
  }
})
