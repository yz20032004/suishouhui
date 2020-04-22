// pages/setting/index.js
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
    var that = this
    wx.request({
      url: host + 'tt_stat.php?action=get_revenue_today',
      data: {
        uid: wx.getStorageSync('uid'),
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        that.setData({
          wait_cash_out: res.data.wait_cash_out,
          total_revenue: res.data.total_revenue,
          user: wx.getStorageSync('user')
        })
      }
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
  openMerchant: function () {
    wx.navigateTo({
      url: 'merchant',
    })
  },
  openCashout:function(){
    wx.navigateTo({
      url: 'cash_out',
    })
  },
  openGuanjia:function(){
    wx.navigateToMiniProgram({
      appId: 'wx18a4d4b1d74b229f',
      path: 'pages/index/index',
      success(res) {
        // 打开成功
      }
    })
  }
})