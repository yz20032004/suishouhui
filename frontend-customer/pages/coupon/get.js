// pages/coupon/get.js
const host = require('../../config').host
Page({

  /**
   * 页面的初始数据
   */
  data: {
    is_member:true
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    wx.showLoading({
      title: '加载中',
    })
    var that = this
    var coupon_id = options.coupon_id
    var total = options.total
    this.data.interval = setInterval(
      function () {
        if (wx.getStorageSync('is_load_member')) {
          clearInterval(that.data.interval)
          that.setData({
            coupon_id: coupon_id,
            total: total,
            is_member: wx.getStorageSync('member_multiple_cards') > 0 ? true : false
          })
          that.get_coupon_detail()
        }
      }, 200);
  },
  get_coupon_detail:function(){
    var that = this
    wx.request({
      url: host + 'card.php?action=get_coupon_detail',
      data: {
        id:that.data.coupon_id
      },
      success: function (res) {
        if (that.data.is_member) {
          that.get_coupon_ext(res.data.mch_id)
        } else {
          that.setData({
            detail:res.data,
            mch_id:res.data.mch_id,
            is_member:false
          })
        }
      }
    })
  },
  getUser: function (e) {
    var that = this
    var openid = wx.getStorageSync('openid')
    if (!openid) {
      return
    }
    var user = e.detail.userInfo
    var encryptedData = e.detail.encryptedData
    var iv = e.detail.iv
    var mch_id = that.data.mch_id
    wx.request({
      url: host + 'huipay/user.php?action=update_user_info',
      data: {
        key: 'placeholder',
        openid: openid,
        mch_id: mch_id,
        encryptedData: encryptedData,
        iv: iv,
        session_key: wx.getStorageSync('session_key')
      },
      success: function (res) {
        if ('success' == res.data) {
          if (wx.getStorageSync('mch_id') != mch_id) {
            wx.setStorageSync('mch_id', mch_id)
            var member_multiple_cards = parseInt(wx.getStorageSync('member_multiple_cards')) + 1
            wx.setStorageSync('member_multiple_cards', member_multiple_cards)
          }
          that.get_coupon_ext(mch_id)
        }
      }
    })
  },
  get_coupon_ext: function(mch_id){
    var that = this
    wx.request({
      url: host + 'card.php?action=get_coupon_ext',
      data: {
        coupon_id: that.data.coupon_id,
        total: that.data.total
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        wx.addCard({
          cardList: res.data,
          success(res) {
            if (wx.getStorageSync('mch_id') != mch_id) {
              wx.setStorageSync('mch_id', mch_id)
              var member_multiple_cards = parseInt(wx.getStorageSync('member_multiple_cards')) + 1
              wx.setStorageSync('member_multiple_cards', member_multiple_cards)
            }
            wx.switchTab({
              url: '../index/index',
            })
          }, fail(res) {
            wx.navigateBack({
              delta:-1
            })
          }
        })
      }
    })
  },
  get_coupon:function(e){
    var coupon_id = this.data.detail.id
    var mch_id    = this.data.detail.mch_id
    var that   = this
    wx.request({
      url: host + 'card.php?action=add_coupon',
      data: {
        openid: wx.getStorageSync('openid'),
        coupon_id: coupon_id,
        mch_id: that.data.detail.mch_id
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        if ('fail' == res.data.result) {
          wx.showModal({
            title: '领取失败',
            content: res.data.msg,
            showCancel:false,
            success:function(e){
              wx.switchTab({
                url: '../index/index',
              })
            }
          })
        } else {
          wx.redirectTo({
            url: 'detail?id='+res.data.id,
          })
        }
      }
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
