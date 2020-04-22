// pages/qywork/getCurExternalContact.js
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
  onLoad: function(options) {},

  /**
   * 生命周期函数--监听页面初次渲染完成
   */
  onReady: function() {

  },

  /**
   * 生命周期函数--监听页面显示
   */
  onShow: function() {
    wx.qy.getCurExternalContact({
      success: function(res) {
        var userId = res.userId //返回当前外部联系人userId
        wx.request({
          url: host + 'qywork.php?action=get_external_openid',
          data: {
            mch_id: wx.getStorageSync('mch_id'),
            userid: userId
          },
          header: {
            'content-type': 'application/json'
          },
          success: function(res) {
            if (res.data) {
              wx.redirectTo({
                url: '../member/detail?openid=' + res.data.sub_openid
              })
            } else {
              wx.showModal({
                title: '无会员记录',
                content: '该顾客未注册会员',
                showCancel: false,
                success: function() {
                  wx.reLaunch({
                    url: '../index/index',
                  })
                }
              })
            }
          }
        })
      }
    })
  },

  /**
   * 生命周期函数--监听页面隐藏
   */
  onHide: function() {

  },

  /**
   * 生命周期函数--监听页面卸载
   */
  onUnload: function() {

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

  /**
   * 用户点击右上角分享
   */
  onShareAppMessage: function() {

  }
})