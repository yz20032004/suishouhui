// pages/index/payed_share_cash.js
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
    var key = options.key
    var payed_share = options.payed_share
    var that = this
    this.setData({
      key:key,
      payed_share:payed_share,
      is_member : wx.getStorageSync('member') ? true : false
    })
    this.data.interval = setInterval(
      function () {
        if (wx.getStorageSync('openid')) {
          clearInterval(that.data.interval)
          that.get_list()
        }
      }, 200);
  },
  get_list:function(){
    var that = this
    var openid = wx.getStorageSync('openid')
    wx.request({
      url: host + 'huipay/payed_share.php?action=get_list',
      data: {
        key: that.data.key,
        openid: openid
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        var member_get = false
        for(var i=0;i<res.data.list.length;i++){
          if (openid == res.data.list[i].openid) {
            member_get = true
          }
        }
        that.setData({
          member_get:member_get,
          memberList:res.data.list,
          mch_id:res.data.mch_id,
          merchant_name:res.data.business_name,
          merchant_logo:res.data.logo_url
        })
      }
    })
  },
  getUser: function (e) {
    var that = this
    var user = e.detail.userInfo
    var encryptedData = e.detail.encryptedData
    var iv = e.detail.iv
    wx.request({
      url: host + 'huipay/user.php?action=update_user_info',
      data: {
        openid: wx.getStorageSync('openid'),
        mch_id: that.data.mch_id,
        encryptedData: encryptedData,
        iv: iv,
        session_key: wx.getStorageSync('session_key')
      },
      success: function (res) {
        if ('success' == res.data) {
          that.submit()
        }
      }
    })
  },
  submit:function(e){
    var that = this
    wx.request({
      url: host + 'huipay/payed_share.php?action=grab_wechatcard',
      data: {
        key: that.data.key,
        mch_id:that.data.mch_id,
        openid: wx.getStorageSync('openid')
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        if ('fail' == res.data.result) {
          wx.showModal({
            title: '领取失败',
            content: res.data.message
          })
        } else {
          wx.redirectTo({
            url: '../coupon/get?coupon_id=' + res.data.coupon_id + '&total=1',
          })
        }
      }
    })
  },
  use_coupon:function(){
    wx.reLaunch({
      url: 'index',
    })
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

  /**
   * 生命周期函数--监听页面隐藏
   */
  onHide: function () {

  },

  /**
   * 生命周期函数--监听页面卸载
   */
  onUnload: function () {

  }
})