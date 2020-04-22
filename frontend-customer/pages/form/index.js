// pages/waimai/index.js
const host = require('../../config').host
Page({

  /**
   * 页面的初始数据
   */
  data: {
    enter_form:false
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
          var id = options.id
          var openid = wx.getStorageSync('openid')
          that.getFormUrl(mch_id, openid, id)
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
  getFormUrl: function (mch_id, openid, id) {
    var that = this
    wx.request({
      url: host + 'huipay/form.php?action=get_form_url',
      data: {
        mch_id:mch_id,
        openid:openid,
        id:id
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        if ('fail' == res.data.errcode) { 
          wx.showModal({
            title: '活动已结束',
            showCancel:false,
            success(res){
              wx.reLaunch({
                url: '../index/index',
              })
            }
          })        
        } else {
          if ('point' == res.data.award_type && !wx.getStorageSync('is_member')) {
            wx.showModal({
              title: '您还不是会员，将无法获得积分奖励',
              content:'现在去加入会员',
              success(res){
                if (res.confirm) {
                  wx.navigateTo({
                    url: '../index/get_membercard?mch_id='+mch_id,
                  })
                }
              }
            })
          }
          wx.setStorageSync('form_id', res.data.form_id)
          that.setData({
            enter_form:true,
            form_url:encodeURI(res.data.url)
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
  }
})