// pages/member/adjust_point.js
const host = require('../../config').host
Page({

  /**
   * 页面的初始数据
   */
  data: {
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
  },

  /**
   * 生命周期函数--监听页面初次渲染完成
   */
  onReady: function () {

  },

  /**
   * 生命周期函数--监听页面显示
   */
  onShow: function () {

  },

  /**
   * 生命周期函数--监听页面隐藏
   */
  onHide: function () {

  },

  /**
   * 生命周期函数--监听页面卸载
   */
  onUnload: function () {

  },
  submit: function (e) {
    var that = this
    var mch_id = e.detail.value.mch_id
    if (!mch_id) {
      wx.showModal({
        title: "请填写微信支付商户号",
        content: "",
        showCancel: false,
        confirmText: "确定"
      })
      return false
    }
    if (mch_id.length != 10) {
      wx.showModal({
        title: "请填写正确的微信支付商户号",
        content: "",
        showCancel: false,
        confirmText: "确定"
      })
      return false
    }
    wx.request({
      url: host + 'ssh_mch.php?action=update_mchid',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
        new_mch_id: mch_id,
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        wx.showToast({
          title: "操作成功",
          icon: 'success',
          duration: 2000,
          success: function (res) {
            wx.setStorageSync('mch', res.data)
            wx.setStorageSync('mch_id', mch_id)
            wx.navigateBack({
              delta: 1
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