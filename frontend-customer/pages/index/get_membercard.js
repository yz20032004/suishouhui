// pages/index/get_membercard.js
const host = require('../../config').host
var app = getApp()
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
    var grade   = options.hasOwnProperty('grade') && '0' != options.grade ? options.grade: 1
    var that = this
    this.setData({
      mch_id:mch_id,
      shop_id:shop_id,
      grade:grade
    })
    var that = this
    this.data.interval = setInterval(
      function () {
        if (wx.getStorageSync('openid')) {
          clearInterval(that.data.interval)
          that.getMemberDetail(mch_id)
        }
      }, 200);
  },
  getMemberDetail: function (mch_id) {
    var that = this
    var openid = wx.getStorageSync('openid')
    wx.request({
      url: host + 'huipay/user.php?action=get_mch_detail',
      data: {
        openid: openid,
        mch_id: mch_id
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        if (!res.data || !res.data.cardnum) {
          that.openCardInfo()
        } else {
          wx.reLaunch({
            url: '../index/index',
          })
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
  openCardInfo: function (options) {
    var that = this
    wx.request({
      url: host + 'shop.php?action=get_membercard_openinfo',
      data: {
        mch_id: that.data.mch_id,
        grade:that.data.grade
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        var member = wx.getStorageSync('member')
        that.setData({
          card: res.data,
          mch_id: that.data.mch_id,
          is_follow: member && member.mch_id == that.data.mch_id ? true : false
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
  getUser:function(e){
    var that = this
    var user = e.detail.userInfo
    var encryptedData = e.detail.encryptedData
    var iv = e.detail.iv
    var openid = wx.getStorageSync('openid')
    wx.request({
      url: host + 'huipay/user.php?action=update_user_info',
      data: {
        openid:openid,
        mch_id: that.data.mch_id,
        shop_id:that.data.shop_id,
        encryptedData: encryptedData,
        iv:iv,
        session_key:wx.getStorageSync('session_key')
      },
      success: function (res) {
        if ('success' == res.data) {
          wx.setStorageSync('mch_id', that.data.mch_id)
          var member_multiple_cards = parseInt(wx.getStorageSync('member_multiple_cards')) + 1
          wx.setStorageSync('member_multiple_cards', member_multiple_cards)
          that.opencard()
        } else {
          wx.showModal({
            title: '网络错误',
            content: '请退出重试',
            showCancel:false
          })
          return
        }
      }
    })
  },
  opencard: function () {
    var that = this
    wx.request({
      url: host + 'card.php?action=get_membercard_extradata',
      data: {
        mch_id: that.data.mch_id
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        var encrypt_card_id = decodeURIComponent(res.data.encrypt_card_id)
        var biz = decodeURIComponent(res.data.biz)
        var extraData = {
          encrypt_card_id: encrypt_card_id,
          out_str: 'mini',
          biz: biz
        }
        wx.navigateToMiniProgram({
          appId: 'wxeb490c6f9b154ef9', //固定为此 appid，不可改动
          extraData: extraData,
          success: function (res) { 
            wx.reLaunch({
              url: '../index/index',
            })
          },
          fail: function (res) {
            console.log('fal', res)
          },
          complete: function () {
          }
        })
      }
    })
  }
})
