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
      url: host + 'huipay/user.php?action=get_groupon_list',
      data: {
        mch_id:wx.getStorageSync('mch_id'),
        openid:wx.getStorageSync('openid')
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        that.setData({
          list:res.data
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

  },

  /**
   * 页面上拉触底事件的处理函数
   */
  onReachBottom: function () {

  },
  open_detail:function(e){
    var out_trade_no = e.currentTarget.dataset.tradeno
    wx.navigateTo({
      url: 'groupon_detail?out_trade_no='+out_trade_no,
    })

  }
})