// pages/setting/point.js
const host = require('../../config').host + 'ssh_'
Page({
  data: {
  },
  onLoad: function (options) {
    // 页面初始化 options为页面跳转所带来的参数
    var that = this
    wx.request({
      url: host + 'mall.php?action=get_config',
      data: {
        mch_id: wx.getStorageSync('mch_id')
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        that.setData({
          mall_config:res.data,
          jiabo_device_no:res.data.jiabo_device_no,
        })
      }
    })
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
    var delivery_cost = e.detail.value.delivery_cost
    var delivery_free_atleast = e.detail.value.delivery_free_atleast
    var can_recharge = e.detail.value.can_recharge ? 1 : 0
    var delivery_tip = e.detail.value.delivery_tip
    var jiabo_device_no = e.detail.value.jiabo_device_no
    if ('' == delivery_cost) {
      wx.showModal({
        title: '请设置统一运费金额',
        content:'无运费则填写0',
        showCancel:false
      })
      return
    }
    wx.request({
      url: host + 'mall.php?action=update_config',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
        delivery_cost: delivery_cost,
        delivery_free_atleast:delivery_free_atleast,
        can_recharge:can_recharge,
        delivery_tip:delivery_tip,
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
