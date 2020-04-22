// pages/vip/switch_shop.js
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
      url: host + 'huipay/user.php?action=get_shop_list',
      data: {
        openid: wx.getStorageSync('openid')
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        that.setData({
          list: res.data
        })
      }
    })
  },
  switch_shop:function(e){
    var mch_id = e.currentTarget.dataset.id
    var that = this
    var member = wx.getStorageSync('member')
    wx.request({
      url: host + 'huipay/user.php?action=get_mch_detail',
      data: {
        openid: wx.getStorageSync('openid'),
        mch_id:mch_id
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        wx.setStorageSync('member', res.data)
        wx.setStorageSync('is_member', res.data.cardnum != '' ? true : false)
        wx.setStorageSync('mch_id', res.data.mch_id)
        wx.reLaunch({
          url: '../index/index',
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

  /**
   * 页面相关事件处理函数--监听用户下拉动作
   */
  onPullDownRefresh: function () {

  },

  /**
   * 页面上拉触底事件的处理函数
   */
  onReachBottom: function () {

  }
})