// pages/marketing/grade.js
const host = require('../../config').host
Page({
  onLoad: function (options) {
  },
  onShow: function () {
    // 页面显示
    var that = this
    wx.request({
      url: host + 'mch.php?action=get_grades',
      data: {
        mch_id: wx.getStorageSync('mch_id')
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        that.setData({
          grades: res.data,
        })
      }
    })
  },
  add: function () {
    wx.navigateTo({ url: 'grade_add' })
  },

  back: function () {
    wx.navigateBack({
      delta: 1
    })
  }
})
