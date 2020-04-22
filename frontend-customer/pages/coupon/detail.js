// pages/coupon/detail.js
const host = require('../../config').host
const { barcode, qrcode } = require('../../utils/index.js')
Page({

  /**
   * 页面的初始数据
   */
  data: {

  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function(options) {
    var id = options.id
    var that = this
    wx.request({
      url: host + 'card.php?action=get_detail',
      data: {
        id: id
      },
      header: {
        'content-type': 'application/json'
      },
      success: function(res) {
        var code = res.data.code
        that.writeOff(code)
        var code_display = ''
        for (var i = 0; i < code.length; i++) {
          if (i % 4 == 0) {
            code_display = code_display + ' ' + code[i]
          } else {
            code_display = code_display + code[i]
          }
        }
        var channel = 'coupon_' + code
        that.connect_websocket(channel)
        that.setData({
          detail: res.data,
          code_display: code_display
        })
        if ('0' == res.data.status) {
          wx.showModal({
            title: '该券已使用',
            content: '使用时间' + res.data.updated_at,
            showCancel: false,
            success: function () {
              wx.reLaunch({
                url: '../index/index',
              })
            }
          })
        }
      }
    })
  },
  writeOff(code) {
    qrcode('qrcode', code, 500, 500);
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
    var width = wx.getSystemInfoSync().windowWidth
    var code_height = Math.round(width * 500 / 750)
    var margin_left = parseInt((width - (width * 500 / 750)) / 2)
    var page_margin = parseInt(width * 0.05)
    this.setData({
      code_height:code_height,
      margin_left: margin_left - page_margin
    })
  },

  /**
   * 生命周期函数--监听页面隐藏
   */
  onHide: function() {
    wx.closeSocket()
  },

  /**
   * 生命周期函数--监听页面卸载
   */
  onUnload: function() {
    wx.closeSocket()
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
  connect_websocket(channel) {
    var that = this;
    wx.request({
      url: host + 'ssh_websocket.php?action=create_channel',
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
          success: function() {
            wx.onSocketOpen(function(res) {})
            wx.onSocketMessage(function(res) {
              var message = res.data
              var result = JSON.parse(message);
              if (result.consume == 'completed') {
                wx.hideLoading()
                wx.vibrateLong()
                wx.showModal({
                  title: '券核销成功',
                  content: '',
                  showCancel: false,
                  success: function() {
                    wx.redirectTo({
                      url: 'list',
                    })
                  }
                })
              }
            })
            wx.onSocketClose(function(res) {})
            wx.onSocketError(function(res) {
              console.log("websocket 错误", res)
            })
          }
        })
      }
    })
  }
})
