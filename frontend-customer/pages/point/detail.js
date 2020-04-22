// pages/point/detail.js
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
    var id = options.id
    var that = this
    wx.request({
      url: host + 'huipay/point.php?action=get_detail',
      data: {
        id: id
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        that.setData({
          detail: res.data
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
    var shop = wx.getStorageSync('shop')
    if (!shop) {
      this.get_shop
    } else {
      this.setData({
        shop:shop
      })
    }
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

  /**
   * 用户点击右上角分享
   */
  onShareAppMessage: function () {

  },
  submit:function(e){
    var formId = e.detail.formId
    var detail = this.data.detail
    var member = wx.getStorageSync('member')
    if ('0' == member.point || (parseInt(member.point) < parseInt(detail.point))) {
      wx.showModal({
        title: '您的积分余额不足',
        content: "",
        showCancel: false,
        confirmText: "确定"
      })
      return false
    }
    var that = this
    wx.showModal({
      title: '温馨提示',
      content: '您确定要使用'+that.data.detail.point+'积分兑换'+that.data.detail.coupon_name+'吗？',
      success(res) {
        if (res.confirm) {
          wx.request({
            url: host + 'huipay/point.php?action=exchange',
            data: {
              id: that.data.detail.id,
              openid: wx.getStorageSync('openid'),
              coupon_id: that.data.detail.coupon_id,
              point: that.data.detail.point,
              single_limit: that.data.detail.single_limit,
              mch_id: that.data.detail.mch_id,
              formId: formId
            },
            header: {
              'content-type': 'application/json'
            },
            success: function (res) {
              if ('success' == res.data) {
                var app = getApp()
                app.refreshMember()
                wx.showModal({
                  title: '兑换成功，现在去领券',
                  content: "",
                  showCancel: false,
                  confirmText: "确定",
                  success: function () {
                    wx.navigateTo({
                      url: '../coupon/get?coupon_id=' + that.data.detail.coupon_id + '&total=1',
                    })
                  }
                })
              } else {
                wx.showModal({
                  title: res.data,
                  content: "",
                  showCancel: false,
                  confirmText: "确定"
                })
                return false
              }
            }
          })
        }
      }
    })
  },
  get_shop: function () {
    var that = this
    wx.request({
      url: host + 'shop.php?action=get_detail',
      data: {
        mch_id: mch_id
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        that.setData({
          shop: res.data,
        })
      }
    })
  },
  backtoindex: function () {
    wx.switchTab({
      url: '../index/index',
    })
  }
})