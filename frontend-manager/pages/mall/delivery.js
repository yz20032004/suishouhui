// pages/member/adjust_point.js
const host = require('../../config').host
Page({

  /**
   * 页面的初始数据
   */
  data: {
    express_names: ["请选择快递", "圆通", "申通", "韵达", "中通", "顺丰", "EMS", "天天", "邮政包裹", "百世"],
    expressIndex: 0,
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    var out_trade_no = options.out_trade_no
    this.setData({
      out_trade_no:out_trade_no
    })
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
  bindExpressChange: function (e) {
    this.setData({
      expressIndex: e.detail.value
    })
  },
  setExpress: function (e) {
    var that = this
    var express = e.detail.value.express
    var express_no = e.detail.value.express_no
    if (!this.data.expressIndex) {
      wx.showModal({
        title: "请选择快递公司",
        content: "",
        showCancel: false,
        confirmText: "确定"
      })
      return false
    }
    if (!express_no) {
      wx.showModal({
        title: "请填写快递单号",
        content: "",
        showCancel: false,
        confirmText: "确定"
      })
      return false
    }
    wx.request({
      url: host + 'mall.php?action=update_express',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
        out_trade_no: that.data.out_trade_no,
        express: express,
        express_no: express_no,
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
            wx.navigateBack({
              delta: 2
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