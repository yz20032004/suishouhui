// pages/vip/groupon_history.js
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
    wx.request({
      url: host + 'huipay/user.php?action=get_groupon_detail',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
        out_trade_no:options.out_trade_no
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        that.setData({
          groupon: res.data
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
  onShareAppMessage: function (res) {
    var that = this
    var groupon_id = that.data.groupon.groupon_id
    var imageUrl = that.data.groupon.image_url
    var title = that.data.groupon.title
    var together_no = that.data.groupon.together_no
    var shop = wx.getStorageSync('shop')
    return {
      title: '【'+shop.business_name+'】'+title,
      imageUrl: imageUrl,
      path: '/pages/together/detail?id='+groupon_id+'&together_no=' + together_no
    }
  }
})