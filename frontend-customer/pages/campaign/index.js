// pages/campaign/index.js
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
    var give_mchid = options.hasOwnProperty('mch_id')
    this.data.interval = setInterval(
      function () {
        if (wx.getStorageSync('is_load_member')) {
          clearInterval(that.data.interval)
          if (wx.getStorageSync('mch_id')) {
            that.loadGroupon()
            that.loadTogether()
            that.loadVipcardList()
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
  onReady: function () {
    wx.hideLoading()
  },

  /**
   * 生命周期函数--监听页面显示
   */
  onShow: function () {

  },
  loadGroupon: function () {
    var that = this
    var mch_id = wx.getStorageSync('mch_id')
    if (!mch_id) {
      return
    }
    wx.request({
      url: host + 'huipay/groupon.php?action=get_top',
      data: {
        mch_id: wx.getStorageSync('mch_id')
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        that.setData({
          grouponData: res.data
        })
      }
    })
  },
  loadTogether: function () {
    var that = this
    var mch_id = wx.getStorageSync('mch_id')
    if (!mch_id) {
      return
    }
    wx.request({
      url: host + 'huipay/together.php?action=get_top',
      data: {
        mch_id: wx.getStorageSync('mch_id')
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        that.setData({
          togetherData: res.data
        })
      }
    })
  },
  buy_vipcard: function (e) {
    var id = e.currentTarget.dataset.id
    wx.navigateTo({
      url: '../vipcard/detail?id=' + id,
    })
  },
  buy: function (e) {
    var id = e.currentTarget.dataset.id
    wx.navigateTo({
      url: '../groupon/detail?id=' + id,
    })
  },
  together: function (e) {
    var id = e.currentTarget.dataset.id
    wx.navigateTo({
      url: '../together/detail?id=' + id,
    })
  },
  loadVipcardList: function () {
    var that = this
    var mch_id = wx.getStorageSync('mch_id')
    wx.request({
      url: host + 'huipay/vipcard.php?action=get_list',
      data: {
        mch_id: mch_id
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        that.setData({
          vipcardData: res.data
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
   * 用户点击右上角分享
   */
  onShareAppMessage: function () {

  }
})