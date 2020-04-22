//app.js
const host = require('config').host
App({
  onLaunch: function () {
    var that = this
    wx.showLoading({
      title: '加载中',
    })
    wx.checkSession({
      success: function () {
        //session 未过期，并且在本生命周期一直有效
        if (!wx.getStorageSync('openid')) {
          that.login()
        } else {
          that.refreshMember()
        }
      },
      fail: function () {
        that.login()
      },
      complete: function () {
        wx.hideLoading()
      }
    })
  },
  login:function(){
    var that = this
    wx.login({
      success: function (data) {
        wx.request({
          url: host + 'huipay/user.php?action=login',
          data: {
            js_code: data.code
          },
          success: function (res) {
            var openid = res.data.openid
            var session_key = res.data.session_key
            wx.setStorageSync('session_key', session_key)
            wx.setStorageSync('openid', openid)
            that.refreshMember()
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
  refreshMember:function(){
    wx.request({
      url: host + 'huipay/user.php?action=get_detail',
      data: {
        openid: wx.getStorageSync('openid')
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        wx.setStorageSync('is_load_member', true)
        if (res.data.id) {
          wx.setStorageSync('member', res.data)
          wx.setStorageSync('mch_id', res.data.mch_id)
          wx.setStorageSync('member_multiple_cards', res.data.multiple_cards)
          wx.setStorageSync('is_member', res.data.cardnum != '' ? true : false)
        } else {
          wx.setStorageSync('is_member', false)
          wx.setStorageSync('member_multiple_cards', 0)
        }
      }
    })
  },
  onShow:function(e){
    if (e.query.card_id) {
      //console.log('card_id', e.query.card_id)
    }
  },
  globalData: {
    userInfo: null
  }
})