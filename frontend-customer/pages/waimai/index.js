// pages/waimai/index.js
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
    wx.showLoading({
      title: '加载中',
    })
    var that = this
    this.data.interval = setInterval(
      function () {
        if (wx.getStorageSync('is_load_member')) {
          clearInterval(that.data.interval)
          if (options.hasOwnProperty('mch_id')) {
            var mch_id = options.mch_id
            wx.setStorageSync('mch_id', mch_id)
          } else {
            var mch_id = wx.getStorageSync('mch_id')
          }
          var openid = wx.getStorageSync('openid')
          that.getOrderUrl(mch_id, openid)
        }
      }, 200)
  },

  /**
   * 生命周期函数--监听页面初次渲染完成
   */
  onReady: function () {
    wx.hideLoading()
    var that = this
    var shop = wx.getStorageSync('shop')
    if (!shop) {
      wx.request({
        url: host + 'ssh_mch.php?action=get_detail',
        data: {
          mch_id: wx.getStorageSync('mch_id')
        },
        header: {
          'content-type': 'application/json'
        },
        success: function(res) {
          that.setData({
            merchant_name:res.data.merchant_name
          })
        }
      })
    } else {
      that.setData({
        merchant_name:shop.business_name
      })
    }
  },
  getOrderUrl: function (mch_id, openid) {
    var that = this
    wx.request({
      url: host + 'huipay/waimai.php?action=get_order_url',
      data: {
        mch_id:mch_id,
        openid:openid
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        if ('fail' == res.data) {
          wx.showModal({
            title: '商户当前已停止配送外卖',
            showCancel:false,
            success:function(){
              wx.switchTab({
                url: '../index/index',
              })
            }
          })
        } else if ('close' == res.data) {
          wx.showModal({
            title: '商户今天已停止配送外卖',
            content:'请明天再来',
            showCancel:false,
            success:function(){
              wx.switchTab({
                url: '../index/index',
              })
            }
          })
        } else {
          that.setData({
            order_url:encodeURI(res.data)
          })
        }
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
  onShareAppMessage: function(res) {
    var merchant_name = this.data.merchant_name
    return {
      title:merchant_name+'外送下单',
      path: '/pages/waimai/index?mch_id=' + wx.getStorageSync('mch_id')
    }
  }
})