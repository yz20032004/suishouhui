// pages/vip/qrcode.js
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
  onLoad: function (options) {
    var member = wx.getStorageSync('member')
    var width = wx.getSystemInfoSync().windowWidth
    var margin_left = parseInt((width - (width * 500 / 750)) / 2)
    this.setData({
      member:member,
      shop:wx.getStorageSync('shop'),
      margin_left:margin_left
    })
    this.writeOff(member.mobile)
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
  writeOff(code) {
    qrcode('qrcode', code, 500, 500);
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

  }
})