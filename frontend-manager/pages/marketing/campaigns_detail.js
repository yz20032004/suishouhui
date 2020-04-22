// pages/index/detail.js
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
    var id = options.id
    var that = this
    wx.request({
      url: host + 'campaigns.php?action=get_detail',
      data: {
        id: id,
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        that.setData({
          row: res.data
        })
      }
    })
  },
  preview: function (e) {
    var current = e.target.dataset.src
    wx.previewImage({
      current: current,
      urls: [current]
    })
  },
  /**
   * 
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

  /**
   * 页面相关事件处理函数--监听用户下拉动作
   */
  onPullDownRefresh: function () {

  },

  /**
   * 页面上拉触底事件的处理函数
   */
  onReachBottom: function () {

  },

  /**
   * 用户点击右上角分享
   */
  onShareAppMessage: function () {

  }
})