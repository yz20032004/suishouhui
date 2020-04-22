// pages/point/list.js
const host = require('../../config').host
Page({

  /**
   * 页面的初始数据
   */
  data: {},

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function(options) {
    wx.showLoading({
      title: '加载中',
    })
    var that = this
    var give_mchid = options.hasOwnProperty('mch_id')
    this.data.interval = setInterval(
      function() {
        if (wx.getStorageSync('is_load_member')) {
          clearInterval(that.data.interval)
          if (wx.getStorageSync('mch_id')) {
            that.get_list()
          } else if (give_mchid) {
            wx.navigateTo({
              url: '../index/get_membercard?mch_id=' + options.mch_id,
            })
          } else {
            wx.redirectTo({
              url: '../index/no_shop',
            })
          }
        }
      }, 200);
  },

  /**
   * 生命周期函数--监听页面初次渲染完成
   */
  onReady: function() {
    wx.hideLoading()
  },

  /**
   * 生命周期函数--监听页面显示
   */
  get_list: function() {
    var that = this
    wx.request({
      url: host + 'huipay/point.php?action=list',
      data: {
        mch_id: wx.getStorageSync('mch_id')
      },
      header: {
        'content-type': 'application/json'
      },
      success: function(res) {
        that.setData({
          list: res.data
        })
      }
    })
  },

  /**
   * 生命周期函数--监听页面隐藏
   */
  onHide: function() {

  },

  /**
   * 生命周期函数--监听页面卸载
   */
  onUnload: function() {

  },

  /**
   * 页面相关事件处理函数--监听用户下拉动作
   */
  onPullDownRefresh: function() {

  },

  /**
   * 页面上拉触底事件的处理函数
   */
  onReachBottom: function() {

  },

  /**
   * 用户点击右上角分享
   */
  onShareAppMessage: function() {

  },
  exchange: function(e) {
    var id = e.currentTarget.dataset.id
    wx.navigateTo({
      url: 'detail?id=' + id,
    })
  },
  backtoindex: function() {
    wx.switchTab({
      url: '../index/index',
    })
  }
})