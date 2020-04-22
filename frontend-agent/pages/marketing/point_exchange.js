// pages/marketing/point_exchange.js
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
    var that = this
    wx.request({
      url: host + 'ssh_marketing.php?action=get_point_exchange_rules',
      data: {
        mch_id: wx.getStorageSync('mch_id')
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        that.setData({
          exchangeData: res.data
        })
      }
    })
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

  },

  /**
   * 页面上拉触底事件的处理函数
   */
  onReachBottom: function () {

  },

  /**
   * 页面相关事件处理函数--监听用户下拉动作
   */
  onPullDownRefresh: function () {

  },
  add_exchange: function () {
    wx.navigateTo({ url: 'point_exchange_add' })
  },
  del: function (e) {
    var id = e.currentTarget.dataset.id
    wx.showModal({
      title: '确定要删除该积分兑换吗？',
      success(res) {
        if (res.confirm) {
          wx.request({
            url: host + 'ssh_marketing.php?action=delete_point_exchange',
            data: {
              mch_id: wx.getStorageSync('mch_id'),
              id: id
            },
            header: {
              'content-type': 'application/json'
            },
            success: function (res) {
              wx.navigateTo({ url: 'point_exchange' })
            }
          })
        }
      }
    })
  },
  back: function () {
    wx.navigateBack({
      delta: 1
    })
  }
})
