// pages/index/recharge_detail.js
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
      url: host + 'recharge.php?action=get_detail',
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
  onShow: function() {},
  recharge: function(e) {
    var id = this.data.detail.id
    var user = wx.getStorageSync('user')
    var member = wx.getStorageSync('current_search_member')
    var that = this
    wx.showModal({
      title: '请确认',
      content: '您确认给该会员充值' + that.data.detail.touch + '元吗？',
      success(res) {
        if (res.confirm) {
          wx.request({
            url: host + 'pay.php?action=recharge',
            data: {
              openid: member.openid,
              sub_openid: member.sub_openid,
              mch_id: wx.getStorageSync('mch_id'),
              recharge_id: id,
              created_uid:user.id,
              created_username:user.name,
              shop_id:wx.getStorageSync('current_shop_id'),
              shop_name: wx.getStorageSync('current_shop_name')
            },
            header: {
              'content-type': 'application/json'
            },
            success: function (res) {
              wx.showModal({
                title: '充值成功',
                content:'',
                showCancel: false,
                confirmText: "确定",
                success: function () {
                  wx.switchTab({
                    url: 'index',
                  })
                }
              })
            }
          })
        }
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

  backtoindex: function() {
    wx.switchTab({
      url: 'index',
    })
  }
})