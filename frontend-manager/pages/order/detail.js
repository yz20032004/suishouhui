// pages/order/detail.js
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
    var table_id = options.table_id
    wx.request({
      url: host + 'tables.php?action=get_order_detail',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
        table_id:table_id
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        that.setData({
          order:res.data,
          dishes:res.data.dishes,
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
})