// pages/index/cashpay.js
const host = require('../../config').host
Page({

  /**
   * 页面的初始数据
   */
  data: {
    is_bind: false,
    disabled: true
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function(options) {

    this.setData({
      is_bind: true,
      merchant: wx.getStorageSync('mch')
    })
  },
  /**
   * 生命周期函数--监听页面显示
   */
  onShow: function() {
    this.setData({
      branch_name:wx.getStorageSync('current_shop_name')
    })
  },
  monitor: function(e) {
    if (!this.data.is_bind) {
      return
    }
    var trade = e.detail.value
    if (trade > 0) {
      this.setData({
        disabled: false
      })
    } else {
      this.setData({
        disabled: true
      })
    }
    var tmp = trade.split('.')
    if (tmp.length > 1) {
      if (tmp[1].length > 2) {
        return trade.substr(0, e.detail.cursor - 1)
      }
    }
  },
  submit: function(e) {
    var trade = e.detail.value.trade
    if (!trade) {
      wx.showToast({
        title: '请输入收款金额',
        image: '/images/close.png',
        duration: 2000
      })
      return;
    }
    var that = this
    var member = wx.getStorageSync('current_search_member')
    var user   = wx.getStorageSync('user')
    wx.request({
      url: host + 'pay.php?action=cashpay',
      data: {
        trade: trade,
        sub_mch_id: wx.getStorageSync('mch_id'),
        openid: member.openid,
        uid: user.id,
        username: user.name,
        shop_id: wx.getStorageSync('current_shop_id'),
        shop_name:wx.getStorageSync('current_shop_name')
      },
      header: {
        'content-type': 'application/json'
      },
      success: function(res) {
        var key = res.data.key
        var get_point = res.data.get_point
        var member = res.data.member
        var url = 'action=cashpay&key=' + key
        wx.navigateTo({
          url: 'member_consume?q=' + encodeURIComponent(url),
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
   * 生命周期函数--监听页面隐藏
   */
  onHide: function() {

  },

  /**
   * 生命周期函数--监听页面卸载
   */
  onUnload: function() {

  },
})