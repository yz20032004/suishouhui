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
      url: host + 'huipay/user.php?action=get_order_detail',
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
        if (res.data.order.is_self && '0000-00-00 00:00:00' == res.data.order.closed_at) {
          var url = 'https://coupons.keyouxinxi.com/scan_trade.php?no='+res.data.order.out_trade_no
          qrcode('qrcode', url, 500, 500);
          var width = wx.getSystemInfoSync().windowWidth
          var margin_left = parseInt((width - (width * 500 / 750)) / 2)
          that.setData({
            margin_left:margin_left,
         })
        }
      }
    })
  },
  copy_address:function(){
    var that = this
    var info = this.data.order.contact_name + this.data.order.contact_mobile + this.data.order.contact_address
    wx.setClipboardData({
      data: info,
      success(res) {
        wx.getClipboardData({
          success(res) {
          }
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
