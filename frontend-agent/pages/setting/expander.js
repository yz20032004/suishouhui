// pages/setting/expander.js
const host = require('../../config').host
Page({
  data: {
    staffData: '',
    shopData: '',
    disabled: false
  },
  onLoad: function (options) {

  },
  onReady: function () {
    // 页面渲染完成
  },
  onShow: function () {
    // 页面显示
    var that = this
    wx.request({
      url: host + 'tt_user.php?action=get_team_list',
      data: {
        uid:wx.getStorageSync('uid')
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        that.setData({
          teamData: res.data,
        })
      }
    })
  },
  onHide: function () {
    // 页面隐藏
  },
  onUnload: function () {
    // 页面关闭
  }
})
