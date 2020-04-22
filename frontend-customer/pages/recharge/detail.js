// pages/recharge/detail.js
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
  onLoad: function(options) {
    var id = options.id
    var that = this
    wx.request({
      url: host + 'huipay/recharge.php?action=get_detail',
      data: {
        id: id
      },
      header: {
        'content-type': 'application/json'
      },
      success: function(res) {
        that.setData({
          detail: res.data
        })
      }
    })
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
  },
  pay: function(e) {
    var member = wx.getStorageSync('member')
    if (!member.cardnum) {
      wx.redirectTo({
        url: 'get_membercard?mch_id=' + mch_id,
      })
      return
    }
    var id = this.data.detail.id
    var that = this
    wx.request({
      url: host + 'pay.php?action=getRechargePrepay',
      data: {
        openid: wx.getStorageSync('openid'),
        mch_id: wx.getStorageSync('mch_id'),
        recharge_id: id
      },
      header: {
        'content-type': 'application/json'
      },
      success: function(res) {
        var payargs = res.data
        wx.requestPayment({
          'timeStamp': payargs.timeStamp,
          'nonceStr': payargs.nonceStr,
          'package': payargs.package,
          'signType': payargs.signType,
          'paySign': payargs.paySign,
          'success': function(res) {
            var content = ''
            if ('coupon' == that.data.detail.award_type) {
              content = '现在去领取赠送的优惠券'

              var coupon_ids = ''
              var totals = ''
              var coupons = that.data.detail.coupons
              for (var i = 0; i < coupons.length; i++) {
                coupon_ids += coupons[i].coupon_id + '#'
                totals += coupons[i].total + '#'
              }
            }
            wx.showModal({
              title: '充值成功',
              content: content,
              showCancel: false,
              confirmText: "确定",
              success: function() {
                if (coupon_ids) {
                  wx.navigateTo({
                    url: '../coupon/get?coupon_id=' + coupon_ids + '&total=' + totals,
                  })
                } else {
                  wx.switchTab({
                    url: '../index/index',
                  })
                }
              }
            })
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

  },
  backtoindex: function () {
    wx.switchTab({
      url: '../index/index',
    })
  }
})