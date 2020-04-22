// pages/vipmember/detail.js
const host = require('../../config').host
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
    var id = options.id
    var that = this
    wx.request({
      url: host + 'vipcard.php?action=get_detail',
      data: {
        id: id
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        that.setData({
          vipcard: res.data,
          grade:res.data.grade_info,
          opengifts:res.data.opengifts
        })
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
  start: function (e) {
    var that = this
    if ('admin' != wx.getStorageSync('user_role')) {
      wx.showModal({
        title: '温馨提示',
        content: '体验者或非管理员身份不可执行此操作',
        showCancel: false
      })
      return
    }
    wx.request({
      url: host + 'vipcard.php?action=start',
      data: {
        id: that.data.vipcard.id
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        wx.showModal({
          title: "活动已重新上线",
          content: "",
          showCancel: false,
          confirmText: "确定",
          success: function () {
            wx.navigateBack({
              delta: 1
            })
          }
        })
      }
    })
  },
  stop: function (e) {
    var that = this
    if ('admin' != wx.getStorageSync('user_role')) {
      wx.showModal({
        title: '温馨提示',
        content: '体验者或非管理员身份不可执行此操作',
        showCancel: false
      })
      return
    }
    wx.showModal({
      title: '确认要终止活动吗？',
      content: '活动终止后该付费卡等级仍可正常使用',
      success(res) {
        if (res.cancel) {
          return
        } else {
          wx.request({
            url: host + 'vipcard.php?action=stop',
            data: {
              id: that.data.vipcard.id
            },
            header: {
              'content-type': 'application/json'
            },
            success: function (res) {
              wx.showModal({
                title: "活动已被终止",
                content: "",
                showCancel: false,
                confirmText: "确定",
                success: function () {
                  wx.navigateBack({
                    delta: 1
                  })
                }
              })
            }
          })
        }
      }
    })
  },
  getqrcode: function () {
    var that = this
    if (this.data.vipcard.qrcode_url) {
      this.previewQrCode(this.data.vipcard.qrcode_url)
      return
    }
    wx.request({
      url: host + 'vipcard.php?action=get_qrcode',
      data: {
        id: that.data.vipcard.id
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        that.previewQrCode(res.data.qrcode_url)
      }
    })
  },
  previewQrCode: function (url) {
    wx.previewImage({
      current: url,
      urls: [url]
    })
  },
  back: function () {
    wx.navigateBack({
      delta: 1
    })
  }
})