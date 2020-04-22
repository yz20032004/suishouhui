// pages/setting/point.js
const host = require('../../config').host
Page({
  data: {
    ordering_display: 'none'
  },
  onLoad: function (options) {
    // 页面初始化 options为页面跳转所带来的参数
    var that = this
    wx.request({
      url: host + 'mch.php?action=get_ordering_config',
      data: {
        mch_id: wx.getStorageSync('mch_id')
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        that.setData({
          ordering_config:res.data,
          ordering_display:'1' == res.data.is_open ? '' : 'none' ,
          time_start:res.data.order_time_start,
          time_end:res.data.order_time_end
        })
      }
    })
  },
  exchangeOpenSwitch: function (e) {
    var isChecked = e.detail.value
    if (isChecked) {
      this.setData({
        ordering_display: ''
      })
    } else {
      this.setData({
        ordering_display: 'none'
      })
    }
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
    var that = this
    var is_open = e.detail.value.is_open
    var pay_first= e.detail.value.pay_first
    if (!this.data.time_start) {
      wx.showModal({
        title: '温馨提示',
        content: '请填写可点餐时间段',
        showCancel: false
      })
      return
    }
    if (!this.data.time_end) {
      wx.showModal({
        title: '温馨提示',
        content: '请填写可点餐时间段',
        showCancel: false
      })
      return
    }
    wx.request({
      url: host + 'mch.php?action=update_ordering_config',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
        is_open: is_open ? 1 : 0,
        pay_first:pay_first,
        time_start:that.data.time_start,
        time_end:that.data.time_end
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        wx.showToast({
          title: '操作成功',
          icon: 'success',
          duration: 2000
        })
      }
    })
  }, 
  bindTimeStartChange(e) {
    this.setData({
      time_start: e.detail.value
    })
  },
  bindTimeEndChange(e) {
    this.setData({
      time_end: e.detail.value
    })
  },
  back: function () {
    wx.navigateBack({
      delta: -1
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
