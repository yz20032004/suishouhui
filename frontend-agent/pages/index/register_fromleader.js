
const host = require('../../config').host
Page({
  data: {
    leader_id:0,
    second: 60,
    userInfo: {},
    getCodeStatus: '获取验证码'
  },
  onLoad: function (options) {
    var that = this
    var leader_openid = options.leader_openid
    this.data.interval = setInterval(
      function () {
        var openid = wx.getStorageSync('openid')
        if (openid) {
          clearInterval(that.data.interval)
          if (leader_openid == openid) {
            wx.switchTab({
              url: '../setting/index',
            })
          } else {
            wx.request({
              url: host + 'tt_user.php?action=get_detail',
              data: {
                openid: openid
              },
              header: {
                'content-type': 'application/json'
              },
              success: function (res) {
                if (res.data) {
                  wx.switchTab({
                    url: '../setting/index',
                  })
                }
              }
            })
          }
        }
      },200)
    wx.request({
      url: host + 'tt_user.php?action=get_detail',
      data: {
        openid:leader_openid
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        that.setData({
          leader_id: res.data.id,
          leader:res.data
        })
      }
    })
    //调用应用实例的方法获取全局数据
  },
  onShow: function () {
  },
  loadProfile: function (e) {
  },
  reg: function (e) {
    var that = this
    var name = this.nameValue
    var mobile = this.mobileValue
    var code = this.codeValue
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
    if (!code) {
      wx.showModal({
        title: "请输入4位短信验证码数字",
        content: "",
        showCancel: false,
        confirmText: "确定"
      })
      return false
    }
    wx.request({
      url: host + 'tt_user.php?action=register_fromleader',
      data: {
        leader_id: that.data.leader_id,
        openid: wx.getStorageSync('openid'),
        name: name,
        mobile: mobile,
        code: code
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        if (res.data == 'fail_code') {
          wx.showModal({
            title: "短信验证码不正确",
            content: "",
            showCancel: false,
            confirmText: "确定"
          })
          return false
        } else {
          wx.setStorageSync('uid', res.data.id)
          wx.setStorageSync('user', res.data)
          wx.switchTab({
            url: 'index',
          })
        }
      }
    })
  },
  getUser: function (e) {
    var that = this
    var user = e.detail.userInfo
    var encryptedData = e.detail.encryptedData
    var iv = e.detail.iv
    wx.request({
      url: host + 'tt_user.php?action=update_user_info',
      data: {
        openid: wx.getStorageSync('openid'),
        encryptedData: encryptedData,
        iv: iv,
        session_key: wx.getStorageSync('session_key')
      },
      success: function (res) {
        if ('success' == res.data) {
          that.reg()
        } else {
          wx.showToast({
            title: '网络错误',
            icon: 'none'
          })
        }
      }
    })
  },
  bindNameInput: function (e) {
    this.nameValue = e.detail.value
  },
  bindMobileInput: function (e) {
    this.mobileValue = e.detail.value
  },
  bindCodeInput: function (e) {
    this.codeValue = e.detail.value
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
      url: host + 'tt_user.php?action=get_validate_code',
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