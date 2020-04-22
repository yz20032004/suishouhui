// pages/index/bill.js
const { barcode, qrcode } = require('../../utils/index.js')
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
    var that = this
    var mch_id = options.hasOwnProperty('mch_id') ? options.mch_id : wx.getStorageSync('mch_id')
    var out_trade_no = options.out_trade_no
    wx.request({
      url: host + 'huipay/user.php?action=get_ordering_detail',
      data: {
        mch_id: mch_id,
        out_trade_no:out_trade_no
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        that.setData({
           order:res.data.order,
           dishes:res.data.dishes,
           pay:res.data.pay
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

  /**
   * 页面相关事件处理函数--监听用户下拉动作
   */
  onPullDownRefresh: function () {

  }
})