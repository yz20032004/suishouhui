// pages/index/bind.js
const host = require('../../config').host
Page({

  /**
   * 页面的初始数据
   */
  data: {
    avatarUrl: '',
    getCodeStatus: '获取验证码'
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    var appid = options.appid
    this.setData({
      display: 'none',
      auth_display: '',
      appid: appid
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
  submit: function (e) {
    var that = this
    var name = e.detail.value.name
    var mobile = e.detail.value.mobile
    var shop_code = e.detail.value.shop_code
    if (!name) {
      wx.showModal({
        title: "请填写您的姓名",
        content: "",
        showCancel: false,
        confirmText: "确定"
      })
      return false
    }
    if (!mobile || mobile.length != '11') {
      wx.showModal({
        title: "请正确输入您的手机号",
        content: "",
        showCancel: false,
        confirmText: "确定"
      })
      return false
    }
    wx.request({
      url: host + 'user.php?action=bind',
      data: {
        appid: that.data.appid,
        openid: wx.getStorageSync('openid'),
        name: name,
        mobile: mobile,
        avatarUrl: that.data.avatarUrl,
        shop_code: shop_code
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        wx.setStorageSync('appid', that.data.appid)
        wx.setStorageSync('shop_code', shop_code)
        wx.showToast({
          title: '绑定成功,现在您可以微信收款了',
          icon: 'success',
          duration: 2000,
          success: function () {
            wx.switchTab({ url: 'index' })
          }
        })
      }
    })
  },
  onGotUserInfo: function (e) {
    var avatarUrl = e.detail.userInfo.avatarUrl
    this.setData({
      display: '',
      auth_display: 'none',
      avatarUrl: avatarUrl
    })
  },
  bindMobileInput: function (e) {
    this.mobileValue = e.detail.value
  },
  getCode: function (e) {
    if ('获取验证码' != this.data.getCodeStatus) {
      return false
    }
    if (!this.mobileValue) {
      wx.showModal({
        title: "请填写手机号码",
        content: "",
        showCancel: false,
        confirmText: "确定"
      })
      return false
    }
    if ('11' != this.mobileValue.length) {
      wx.showModal({
        title: "请正确填写手机号码",
        content: "",
        showCancel: false,
        confirmText: "确定"
      })
      return false
    }

    countdown(this)
    wx.request({
      url: host + 'user.php?action=get_validate_code',
      data: {
        mobile: this.mobileValue,
      },
      header: {
        'content-type': 'application/json'
      },
    })
  }
})

function countdown(that) {
  var second = that.data.second
  if (second == 0) {
    // console.log("Time Out...");
    that.setData({
      second: 60,
      getCodeStatus: "获取验证码"
    });
    return;
  }
  var time = setTimeout(function () {
    that.setData({
      getCodeStatus: (second - 1) + '秒',
      second: second - 1
    });
    countdown(that);
  }
    , 1000)
}
