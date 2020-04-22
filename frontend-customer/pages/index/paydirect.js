// pages/index/paydirect.js
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
    var key     = options.key
    var consume = options.consume
    var is_member = wx.getStorageSync('is_member')
    var mch_id = wx.getStorageSync('mch_id')
    wx.request({
      url: host + 'pay.php?action=getPayDirect',
      data: {
        openid: wx.getStorageSync('openid'),
        is_member:is_member ? 1 : 0,
        mch_id: mch_id,
        consume: consume
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        if (!res.data) {
          if (is_member) {
            wx.reLaunch({
              url: '../index/index',
            })
          } else {
            wx.redirectTo({
              url: 'get_membercard?mch_id=' + mch_id,
            })
          }
        } else {
          if ('rebate' == res.data.campaign_type) {
            wx.redirectTo({
              url: '../coupon/get?coupon_id=' + res.data.coupon_id + '&total=' + res.data.coupon_total,
            })
          } else if ('payed_share' == res.data.campaign_type) {
            wx.redirectTo({
              url: '../vip/payed_share?mch_id='+mch_id+'&key='+key+'&coupon_total='+res.data.coupon_total,
            })
          }
        }
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

  }
})