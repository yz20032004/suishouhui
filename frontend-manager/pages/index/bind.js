// pages/index/bind.js
const host = require('../../config').host
var app = getApp()
Page({

  /**
   * 页面的初始数据
   */
  data: {
    avatarUrl:'',
    shopIndex: 0,
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function(options) {
    var that = this
    this.data.interval = setInterval(
      function () {
        var user = wx.getStorageSync('user')
        if (user) {
          clearInterval(that.data.interval)
          if (!user.is_demo) {
            wx.reLaunch({
              url: '../index/index',
            })
          }
          var mch_id = options.mch_id
          var merchant_name = options.merchant_name
          that.setData({
            display: 'none',
            auth_display: '',
            mch_id: mch_id,
            merchant_name: merchant_name
          })
          that.loadShops(mch_id)
        }
      }, 200);
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
  submit:function(e){
    var that = this
    var shop_id = e.detail.value.shop_id
    var name = e.detail.value.name
    var mobile = e.detail.value.mobile
    if (that.data.shops.length > 2 && !shop_id) {
      wx.showModal({
        title: "请填写您所在的门店",
        content: "",
        showCancel: false,
        confirmText: "确定"
      })
      return false
    }
    if (!name) {
      wx.showModal({
        title: "请填写您的姓名",
        content: "",
        showCancel: false,
        confirmText: "确定"
      })
      return false
    }
    if (!mobile || mobile.length != '11') {
      wx.showModal({
        title: "请正确输入您的手机号",
        content: "",
        showCancel: false,
        confirmText: "确定"
      })
      return false
    }
    var branch_name = that.data.shops[that.data.shopIndex].id != 0 ? that.data.shops[that.data.shopIndex].branch_name : ''
    wx.request({
      url: host + 'user.php?action=bind',
      data: {
        mch_id:that.data.mch_id,
        shop_id:shop_id,
        shop_name:branch_name,
        openid: wx.getStorageSync('openid'),
        name: name,
        mobile: mobile
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        wx.setStorageSync('mch_id', res.data.mch_id)
        wx.setStorageSync('user', res.data)
        wx.setStorageSync('user_role', res.data.role)
        app.getMch(res.data.mch_id)
        wx.showToast({
          title: '绑定成功',
          icon: 'success',
          duration: 2000,
          success: function () {
            wx.reLaunch({ url: 'index' })
          }
        })
      }
    })
  },
  onGotUserInfo:function(e){
    var that = this
    var user = e.detail.userInfo
    wx.request({
      url: host + 'user.php?action=update_user_info',
      data: {
        openid: wx.getStorageSync('openid'),
        mch_id: that.data.mch_id,
        avatarUrl: user.avatarUrl,
        province: user.province,
        city: user.city,
        nickname: user.nickName,
        gender: user.gender
      },
      success: function (res) {
        that.setData({
          display: '',
          auth_display: 'none'
        })
      }
    })
  },
  loadShops:function(mch_id){
    var that = this
    wx.request({
      url: host + 'shop.php?action=get_list',
      data: {
        mch_id: mch_id,
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        var length = res.data.length
        res.data[length] = { id: 0, branch_name: '请选择您所在门店' }
        that.setData({
          shops: res.data,
          shopIndex: res.data.length - 1
        })
      }
    })
  },
  bindShopChange: function (e) {
    var shopIndex = e.detail.value
    this.setData({
      shopIndex: shopIndex
    })
  }
})
