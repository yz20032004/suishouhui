// pages/index/trade_qrcode.js
const host = require('../../config').host
Page({

  /**
   * 页面的初始数据
   */
  data: {
    connected: false,
    path: '',
    times:0
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function(options) {
    var trade = options.trade
    var path = options.path
    var key = options.key
    this.setData({
      trade:trade,
      key: key,
      path: path
    })
  },

  /**
   * 生命周期函数--监听页面初次渲染完成
   */
  onReady: function() {

  },

  /**
   * 生命周期函数--监听页面显示
   */
  onShow: function() {
    if (!this.data.connected) {
      this.connect_websocket(this.data.key)
      this.setData({
        connected:true
      })      
    }
  },

  /**
   * 生命周期函数--监听页面隐藏
   */
  onHide: function() {},

  /**
   * 生命周期函数--监听页面卸载
   */
  onUnload: function() {
    var that = this;
    wx.closeSocket()
    wx.onSocketClose(function(res) {
      that.setData({
        status: "websocket服务器已经断开"
      })
    })
  },

  /**
   * 页面相关事件处理函数--监听用户下拉动作
   */
  onPullDownRefresh: function() {

  },

  /**
   * 页面上拉触底事件的处理函数
   */
  onReachBottom: function() {

  },
  back: function() {
    wx.navigateBack({
      delta: 1
    })
  },
  socket_open:function(){
  },
  connect_websocket(channel) {
    var that = this;
    wx.request({
      url: host + 'websocket.php?action=create_channel',
      data: {
        channel: channel
      },
      header: {
        'content-type': 'application/json'
      },
      success: function(res) {
        var wssurl = res.data.data
        wx.connectSocket({
          url: wssurl,
          success:function(){
            wx.onSocketOpen(function (res) {
             // console.log("websocket连接服务器成功")
            })
            wx.onSocketMessage(function (res) {
              var message = res.data
              var result = JSON.parse(message);
              if (result.pay == 'paying') {
              } else if (result.pay == 'complete') {
                wx.hideLoading()
                wx.vibrateLong()
                wx.redirectTo({
                  url: 'pay_result?key=' + that.data.key,
                })
              } else if (result.pay == 'verify') {
                wx.hideLoading()
                wx.vibrateLong()
                var coupon_id = result.coupon_id
                wx.navigateTo({
                  url: 'pay_verify_coupon?message=' + message
                })
              }
            })
            wx.onSocketClose(function (res) {
              wx.navigateBack({
                delta: 1
              })
            })
            wx.onSocketError(function (res) {
            })
          }
        })
      }
    })
  },
  scan:function(){
    var that = this
    wx.scanCode({
      success: function (res) {
        wx.showToast({
          title: "正在支付",
          icon: "loading",
          duration: 10000,
          success: function (e) {
            var code = res.result
            if (code.length < 16 || code.length > 24) {
              wx.hideToast()
              wx.showModal({
                title: "不正确的付款码",
                content: "请确定顾客展示的是微信的付款码",
                showCancel: false,
                confirmText: "确定"
              })
              return false
            }
            var user = wx.getStorageSync('user')
            wx.request({
              url: host + 'trade.php?action=micropay',
              data: {
                key:that.data.key,
                mch_id: wx.getStorageSync('mch_id'),
                username: user.name,
                trade: that.data.trade,
                code: code
              },
              header: {
                'content-type': 'application/json'
              },
              success: function (res) {
                wx.hideToast()
                if ('SUCCESS' == res.data.result_code) {
                  wx.redirectTo({
                    url: 'pay_result?key=' + that.data.key,
                  })
                } else if ('USERPAYING' == res.data.err_code) {
                  wx.showLoading({
                    title: '顾客输入密码中...',
                  })
                  var out_trade_no = res.data.out_trade_no
                  setTimeout(function () { that.query(out_trade_no) }, 5000)
                } else if (undefined != res.data.err_code_des) {
                  if (typeof (res.data.err_code_des) == 'string') {
                    var des = res.data.err_code_des
                  } else {
                    var des = res.data.err_code_des.msg
                  }
                  wx.showModal({
                    title: "收款失败",
                    content: des,
                    showCancel: false,
                    confirmText: "确定"
                  })
                  return false
                } else {
                  wx.showModal({
                    title: "收款失败",
                    content: res.data.return_msg,
                    showCancel: false,
                    confirmText: "确定"
                  })
                  return false
                }
              },
            })
          }
        })
      },
      fail: function (res) {
      }
    })
  }, 
  query: function (out_trade_no) {
    var that = this
    that.data.times = that.data.times + 1
    if (that.data.times > 3) {
      wx.hideLoading()
      wx.showModal({
        title: "收款失败",
        content: '顾客支付超时',
        showCancel: false,
        confirmText: "确定",
        success(res){
          wx.switchTab({
            url: 'index',
          })
        }
      })
    }
    wx.request({
      url: host + 'trade.php?action=micropay_query',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
        trade:that.data.trade,
        key:that.data.key,
        out_trade_no: out_trade_no
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        if ('SUCCESS' == res.data.return_code) {
          if ('SUCCESS' == res.data.trade_state) {
            wx.hideLoading()
            wx.redirectTo({
              url: 'pay_result?key=' + that.data.key,
            })
          } else if ('USERPAYING' == res.data.trade_state) {
            var out_trade_no = res.data.out_trade_no
            setTimeout(function () { that.query(out_trade_no) }, 5000)
          } else  {
            wx.hideLoading()
            wx.showModal({
              title: '收款失败',
              content: '顾客支付失败',
              success(res){
                wx.switchTab({
                  url: 'index',
                })
              }
            })
          }
        } else {
          wx.hideLoading()
          var des = res.data.err_code_des
          wx.showModal({
            title: "收款失败",
            content: des,
            showCancel: false,
            confirmText: "确定",
            success(res){
              wx.switchTab({
                url: 'index',
              })
            }
          })
        }
      }
    })
  }
})
