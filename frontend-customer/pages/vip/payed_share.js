// pages/vip/payed_share.js
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
    this.setData({
      shareData:options
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
    var that = this
    var shop = wx.getStorageSync('shop')
    if (!shop) {
      wx.request({
        url: host + 'shop.php?action=get_detail',
        data: {
          mch_id: wx.getStorageSync('mch_id')
        },
        header: {
          'content-type': 'application/json'
        },
        success: function (res) {
          wx.setNavigationBarTitle({
            title: res.data.business_name
          })
          wx.setStorageSync('shop', res.data)
          that.setData({
            shop: res.data,
          })
        }
      })
    } else {
      that.setData({
        shop: wx.getStorageSync('shop'),
      })
    }
  },

  /**
   * 生命周期函数--监听页面隐藏
   */
  onHide: function () {
    wx.switchTab({
      url: '../index/index',
    })
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
  create_share: function () {
    var that = this
    that.setData({
      showShareBox: false
    })
    wx.request({
      url: host + 'huipay/payed_share.php?action=create',
      data: {
        mch_id:that.data.shareData.mch_id,
        key: that.data.shareData.key,
        coupon_total: that.data.shareData.coupon_total,
        openid: wx.getStorageSync('openid')
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) { }
    })
  },
  /**
   * 用户点击右上角分享
   */
  onShareAppMessage: function () {
    var that = this
    return {
      title: '这家店我来过了，真不错，发一张优惠券给你',
      imageUrl: that.data.shop.logo_url,
      path: '/pages/index/payed_share_cash?key=' + that.data.shareData.key + '&payed_share='+that.data.shareData.coupon_total
    }
  }
})