// pages/setting/index.js
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
    this.setData({
      user: wx.getStorageSync('user'),
      merchant:wx.getStorageSync('mch')
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
  lookbinds:function(){
    wx.navigateTo({
      url: 'lookbinds',
    })
  },
  readme:function(){
    wx.navigateTo({
      url: '../mini/readme'
    })
  },
  getPhoneNumber(e) {
    var iv = e.detail.iv
    var encryptedData = e.detail.encryptedData
    var that = this
    if (undefined == iv) {
      return;
    }
    wx.checkSession({
      success: function () {
        var session_key = wx.getStorageSync('session_key')
        that.decryptPhoneNumber(session_key, iv, encryptedData)
      },
      fail: function () {
        wx.login({
          success: function (data) {
            wx.request({
              url: host + 'user.php?action=login',
              data: {
                js_code: data.code
              },
              success: function (res) {
                var session_key = res.data.session_key
                that.decryptPhoneNumber(session_key, iv, encryptedData)
              }
            })
          }
        })
      }
    })
  },
  decryptPhoneNumber: function (session_key, iv, encryptedData) {
    var that = this
    var openid = wx.getStorageSync('openid')
    wx.request({
      url: host + 'user.php?action=getphonenumber',
      data: {
        session_key: session_key,
        iv: iv,
        encryptedData: encryptedData,
        openid: openid
      },
      success: function (res) {
        var mobile = res.data
        wx.request({
          url: host + 'user.php?action=get_user',
          data: {
            mobile:mobile,
            openid:openid
          },
          success:function(res) {
            if (res.data == 'fail') {
              wx.setStorageSync('mobile', mobile)
              wx.navigateTo({
                url: '../mini/pay',
              })
            } else {
              res.data.is_demo = false
              wx.setStorageSync('user', res.data)
              wx.setStorageSync('mch_id', res.data.mch_id)
              wx.setStorageSync('user_role', 'admin')
              app.getMch(res.data.mch_id)
              wx.reLaunch({
                url: '../index/index',
              })
            }
          }
        })
      }
    })
  },
  tuitui:function(){
    wx.navigateToMiniProgram({
      appId: 'wxeb2a341fc39722e6',
      path: 'pages/index/index',
      success(res) {
        // 打开成功
      }
    })
  },
  campaign:function(){
    wx.navigateTo({
      url: '../marketing/campaigns',
    })
  },
  export:function(){
    if ('admin' != wx.getStorageSync('user_role')) {
      wx.showModal({
        title: '温馨提示',
        content: '体验者或非管理员身份不可执行此操作',
        showCancel: false
      })
      return
    }
    var mch_id = wx.getStorageSync('mch_id')
    wx.request({
      url: host + 'mch.php?action=export_members',
      data: {
        mch_id:mch_id
      },
      success: function (res) {
        if ('fail' == res.data) {
          wx.showModal({
            title:'您的商户暂无会员数据',
            showCancel:false
          })
          return
        } else {
          var download_url = res.data
          wx.setClipboardData({
            data: download_url,
            success (res) {
              wx.showModal({
                title:'会员数据文件下载链接已复制',
                content:'请粘贴到手机浏览器或微信聊天框里打开',
                showCancel:false
              })
            }
          })
        }
      }
    })
  },
  remind:function(){
    wx.navigateTo({
      url: 'remind',
    })
  },
  startmessage: function (e) {
  },
  completemessage: function (e) {
  }
})