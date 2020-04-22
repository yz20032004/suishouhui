//app.js
const host = require('config').host
App({
  onLaunch: function () {
    var that = this
    var systemInfo = wx.getSystemInfoSync()
    if (systemInfo.hasOwnProperty('environment') && 'wxwork' == systemInfo.environment) {
      this.qylogin()
    } else {
      this.login()
    }
  },
  login:function() {
    var that = this
    wx.checkSession({
      success: function () {
        //session 未过期，并且在本生命周期一直有效
        that.getMember()
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
                var openid = res.data.openid
                wx.setStorageSync('openid', openid)
                var session_key = res.data.session_key
                wx.setStorageSync('session_key', session_key)
                that.getMember()
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
  qylogin: function () {
    var that = this
    wx.qy.checkSession({
      success: function () {
        //session 未过期，并且在本生命周期一直有效
        that.getQyMember()
      },
      fail: function () {
        wx.qy.login({
          success: function (data) {
            wx.request({
              url: host + 'user.php?action=qylogin',
              data: {
                js_code: data.code
              },
              success: function (res) {
                  wx.setStorageSync('userid', res.data.userid)
                  wx.setStorageSync('mch_id', res.data.mch_id)
                  wx.setStorageSync('session_key', res.data.session_key)
                  if (res.data.mch_id) {
                    that.getQyMember()
                  } else {
                    wx.setStorageSync('mch_id', res.data.user.mch_id)
                    wx.setStorageSync('openid', res.data.user.openid)
                    wx.setStorageSync('user', res.data.user)
                    wx.setStorageSync('user_role', res.data.user.role)
                    that.getMch(res.data.user.mch_id)
                  }
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
  getMember: function () {
    var that = this
    wx.request({
      url: host + 'user.php?action=get_detail',
      data: {
        openid: wx.getStorageSync('openid')
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        wx.setStorageSync('user', res.data)
        wx.setStorageSync('mch_id', res.data.mch_id)
        wx.setStorageSync('user_role', res.data.role)
        that.getMch(res.data.mch_id)
      }
    })
  },
  getQyMember: function () {
    var that = this
    wx.request({
      url: host + 'user.php?action=get_qy_detail',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
        userid: wx.getStorageSync('userid')
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        wx.setStorageSync('openid', res.data.openid)
        wx.setStorageSync('user', res.data)
        wx.setStorageSync('user_role', res.data.role)
        that.getMch(res.data.mch_id)
      }
    })
  },
  getMch: function (mch_id) {
    var that = this
    wx.request({
      url: host + 'mch.php?action=get_detail',
      data: {
        mch_id: mch_id
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        wx.setStorageSync('mch', res.data)
      }
    })
  },
  loadMerchantPointRules: function () {
    wx.request({
      url: host + 'mch.php?action=get_point_rule',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        wx.setStorageSync('merchant_point_rule', res.data)
      }
    })
  },
  loadMerchantRechargeRules: function () {
    wx.request({
      url: host + 'marketing.php?action=get_recharges',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        wx.setStorageSync('merchant_recharge_rule', res.data)
      }
    })
  },
  loadMerchantGrades: function () {
    wx.request({
      url: host + 'mch.php?action=get_grades',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        wx.setStorageSync('merchant_grades', res.data)
      }
    })
  },
  loadRebateCampaign: function () {
    wx.request({
      url: host + 'mch.php?action=get_rebate_campaign',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        wx.setStorageSync('merchant_rebate_campaign', res.data)
      }
    })
  },
  loadMerchantExchangeRules: function () {
    wx.request({
      url: host + 'marketing.php?action=get_point_exchange_rules',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        wx.setStorageSync('merchant_exchange_rules', res.data)
      }
    })
  },
  globalData: {
    userInfo: null
  }
})