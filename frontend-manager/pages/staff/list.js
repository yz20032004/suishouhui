// pages/setting/staff.js
const host = require('../../config').host
Page({
  data: {
    staffData:'',
    shopData:'',
    disabled:false
  },
  onLoad: function (options) {
    this.setData({
      disabled: 'admin' != wx.getStorageSync('user_role') ? true : false
    })
  },
  onReady: function () {
    // 页面渲染完成
  },
  onShow: function () {
    // 页面显示
    var that = this
    wx.request({
      url: host + 'mch.php?action=get_staff_list',
      data: {
        mch_id: wx.getStorageSync('mch_id')
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        that.setData({
          staffData: res.data,
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
  onShareAppMessage(res) {
    if (res.from === 'button') {
      // 来自页面内转发按钮
    }
    var user = wx.getStorageSync('user')
    var shopname = user.merchant_name
    return {
      title: '邀请您注册成为'+shopname+'店员',
      //imageUrl: '/images/bill.png',
      path: '/pages/index/bind?mch_id=' + wx.getStorageSync('mch_id') + '&merchant_name='+shopname
    }
  }
})
