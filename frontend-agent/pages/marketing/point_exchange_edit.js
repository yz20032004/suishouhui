// pages/marketing/point_exchange_edit.js
const host = require('../../config').host
var app = getApp()
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
    var id = options.id
    wx.request({
      url: host + 'ssh_marketing.php?action=get_point_exchange_detail',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
        id: id
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        that.setData({
          rule: res.data
        })
      }
    })
  },
  submit: function (e) {
    var id = e.detail.value.rule_id
    var point = e.detail.value.point
    var product = e.detail.value.product
    if (!point) {
      wx.showModal({
        title: "请填写积分",
        content: "",
        showCancel: false,
        confirmText: "确定"
      })
      return false
    }
    if (!product) {
      wx.showModal({
        title: "请填写兑换物品",
        content: "",
        showCancel: false,
        confirmText: "确定"
      })
      return false
    }
    wx.request({
      url: host + 'ssh_marketing.php?action=point_exchange_update',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
        id: id,
        point: point,
        product: product
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        if ('fail' == res.data) {
          wx.showModal({
            title: "积分兑换规则不能重复",
            content: "",
            showCancel: false,
            confirmText: "确定"
          })
          return false
        } else {
          wx.showToast({
            title: '操作成功',
            icon: 'success',
            duration: 2000,
            success: function () {
              wx.navigateBack({
                delta: 1
              })
            }
          })
        }
      }
    })
  },
  delete: function (e) {
    var id = e.currentTarget.dataset.id
    wx.showModal({
      title: '确定要删除此兑换规则吗？',
      content: '',
      success: function (res) {
        if (res.confirm) {
          wx.request({
            url: host + 'ssh_marketing.php?action=point_exchange_delete',
            data: {
              id: id
            },
            header: {
              'content-type': 'application/json'
            },
            success: function (res) {
              wx.showToast({
                title: "操作成功",
                content: "",
                icon: 'success',
                duration: 2000,
                success: function (res) {
                  wx.navigateBack({
                    delta: 1
                  })
                }
              })
            }
          })
        } else if (res.cancel) {

        }
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
    this.setData({
      user: wx.getStorageSync('user')
    })
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

  /**
   * 页面上拉触底事件的处理函数
   */
  onReachBottom: function () {

  },

  /**
   * 页面相关事件处理函数--监听用户下拉动作
   */
  onPullDownRefresh: function () {

  },
  back: function () {
    wx.navigateBack({
      delta: 1
    })
  }
})
