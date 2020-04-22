// pages/setting/point.js
const host = require('../../config').host + 'ssh_'
Page({
  data: {
    waimai_display: 'none'
  },
  onLoad: function (options) {
    // 页面初始化 options为页面跳转所带来的参数
    var that = this
    wx.request({
      url: host + 'mch.php?action=get_waimai_config',
      data: {
        mch_id: wx.getStorageSync('mch_id')
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        that.setData({
          waimai_config:res.data,
          jiabo_device_no:res.data.jiabo_device_no,
          time_start:res.data.delivery_time_start,
          time_end:res.data.delivery_time_end,
          waimai_display:'1' == res.data.is_open ? '' : 'none' 
        })
      }
    })
  },
  exchangeOpenSwitch: function (e) {
    var isChecked = e.detail.value
    if (isChecked) {
      this.setData({
        waimai_display: ''
      })
    } else {
      this.setData({
        waimai_display: 'none'
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
    var delivery_distance = e.detail.value.delivery_distance
    var delivery_time = e.detail.value.delivery_time
    var cost_atleast = e.detail.value.cost_atleast
    var delivery_cost = e.detail.value.delivery_cost
    var delivery_free_atleast = e.detail.value.delivery_free_atleast
    var package_cost = e.detail.value.package_cost
    var can_recharge = e.detail.value.can_recharge ? 1 : 0
    var can_self = e.detail.value.can_self ? 1 : 0
    var jiabo_device_no = e.detail.value.jiabo_device_no

    wx.request({
      url: host + 'mch.php?action=update_waimai_config',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
        is_open: is_open ? 1 : 0,
        delivery_distance:delivery_distance,
        delivery_time:delivery_time,
        cost_atleast:cost_atleast,
        delivery_cost: delivery_cost,
        delivery_free_atleast:delivery_free_atleast,
        package_cost:package_cost,
        can_recharge:can_recharge,
        can_self:can_self,
        time_start:that.data.time_start,
        time_end:that.data.time_end,
        jiabo_device_no:jiabo_device_no
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
  scanCode: function() {
    var that = this
    wx.scanCode({
      success: function(res) {
        var code = res.result
        that.setData({
          jiabo_device_no:code
        })
      },
      fail: function(res) {}
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
