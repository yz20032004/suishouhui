//app.js
const host = require('config').host
App({
  onLaunch: function () {
    var that = this
    wx.checkSession({
      success: function () {
        //session 未过期，并且在本生命周期一直有效
        var openid = wx.getStorageSync('openid')
        var uid = wx.getStorageSync('uid')
      },
      fail: function () {
        wx.login({
          success: function (data) {
            wx.request({
              url: host + 'tt_user.php?action=login',
              data: {
                js_code: data.code
              },
              success: function (res) {
                var openid = res.data.openid
                wx.setStorageSync('openid', openid)
                var session_key = res.data.session_key
                wx.setStorageSync('session_key', session_key)
              },
              fail: function (res) {
                console.log('拉取用户openid失败，将无法正常使用开放接口等服务', res)
                wx.showModal({
                  title: "拒绝授权将无法使用本系统",
                  content: "",
                  showCancel: false,
                  confirmText: "确定"
                })
              }
            })
          },
          fail: function (err) {
            console.log('wx.login 接口调用失败，将无法正常使用开放接口等服务', err)
          }
        })
      },
      complete: function () {
        wx.hideLoading()
      }
    })
  },
  globalData: {
    userInfo: null
  }
})