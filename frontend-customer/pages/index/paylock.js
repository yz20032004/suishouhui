// pages/index/paylock.js
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
    var mch_id = options.mch_id
    var shop_id = options.hasOwnProperty('shop_id') ? options.shop_id : 0
    this.setData({
      mch_id:mch_id,
      shop_id:shop_id
    })
    this.getMerchant(mch_id)
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
  opencard:function(){
    wx.redirectTo({
      url: 'get_membercard?mch_id=' + this.data.mch_id + '&shop_id=' + this.data.shop_id,
    })
  },
  getcoupon:function(){

  },
  groupon:function(){
    wx.switchTab({
      url: '../campaign/index',
    })
  },
  recharge:function(){
    wx.redirectTo({
      url: '../recharge/list',
    })
  },
  toindex:function(){
    wx.switchTab({
      url: 'index',
    })
  },
  getMerchant: function (mch_id) {
    var that = this
    wx.request({
      url: host + 'ssh_mch.php?action=get_detail',
      data: {
        mch_id: mch_id
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        that.setData({
          merchant:res.data
        })
      }
    })
  },
  /**
   * 用户点击右上角分享
   */
  onShareAppMessage: function () {

  }
})