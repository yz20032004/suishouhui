// pages/setting/selfpay.js
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
      url: host + 'mch.php?action=get_counter_list',
      data: {
        mch_id: wx.getStorageSync('mch_id')
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        that.setData({
          counterData: res.data
        })
      }
    })
  },
  submit:function(e){
    if ('admin' != wx.getStorageSync('user_role')) {
      wx.showModal({
        title: '温馨提示',
        content: '体验者或非管理员身份不可执行此操作',
        showCancel: false
      })
      return
    }
    var name = e.detail.value.name
    if (!name) {
      wx.showModal({
        title: '错误',
        content: '请填写收款码牌的名称',
        showCancel:false
      })
      return;
    }
    wx.request({
      url: host + 'mch.php?action=add_counter',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
        merchant_name:wx.getStorageSync('merchant_name'),
        name: name
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        wx.navigateTo({
          url: 'selfpay_detail?id='+res.data.id,
        })
      }
    })
  },
  openCounter:function(e){
    var id = e.currentTarget.dataset.id
    wx.navigateTo({
      url: 'selfpay_detail?id='+id,
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
  back: function () {
    wx.navigateBack({
      delta: 1
    })
  }
})