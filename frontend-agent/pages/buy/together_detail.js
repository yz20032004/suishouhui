// pages/buy/detail.js
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
      url: host + 'ssh_together.php?action=get_detail',
      data: {
        id: id
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        that.setData({
          togetherData: res.data
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
  stop: function (e) {
    var that = this
    wx.showModal({
      title: '确认要终止该拼团活动吗？',
      content: '活动终止后顾客已购券仍可正常使用',
      success(res) {
        if (res.cancel) {
          return
        } else {
          wx.request({
            url: host + 'ssh_together.php?action=stop',
            data: {
              id: that.data.togetherData.id
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
  open_sold_list: function () {
    var that = this
    wx.navigateTo({
      url: 'sold_list?groupon_id=' + that.data.togetherData.id,
    })
  },
  open_consumed_list: function () {
    var that = this
    wx.navigateTo({
      url: 'coupon_list?get_type=together_buy&coupon_id=' + that.data.togetherData.coupon_id + '&type=consumed',
    })
  },
  open_expired_list: function () {
    var that = this
    wx.navigateTo({
      url: 'coupon_list?coupon_id=' + that.data.togetherData.coupon_id + '&type=expired',
    })
  },
  copydata: function () {
    var that = this
    wx.setClipboardData({
      data: 'pages/together/detail?id=' + that.data.togetherData.id,
      success(res) {
        wx.getClipboardData({
          success(res) {
          }
        })
      }
    })
  },
  getqrcode: function () {
    var that = this
    if (this.data.togetherData.qrcode_url) {
      this.previewTogetherQrCode(this.data.togetherData.qrcode_url)
      return
    }
    wx.request({
      url: host + 'ssh_together.php?action=get_qrcode',
      data: {
        id: that.data.togetherData.id
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        that.previewTogetherQrCode(res.data.qrcode_url)
      }
    })
  },
  previewTogetherQrCode: function (url) {
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