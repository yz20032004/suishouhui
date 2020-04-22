// pages/shop/edit.js
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
    this.getShopDetail(wx.getStorageSync('mch_id'))
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
  submit:function(e){
    var that = this
    var address = that.data.address
    var telephone = e.detail.value.telephone
    var open_time = e.detail.value.open_time
    wx.request({
      url: host + 'tt_shop.php?action=update_info',
      data: {
        mch_id: that.data.shop.mch_id ,
        address:address,
        latitude:that.data.latitude,
        longitude:that.data.longitude,
        telephone:telephone,
        open_time:open_time
      },
      header: {
        'content-type': 'application/json'
      },
      success: function(res) {
        wx.showToast({
          title: '保存成功',
          icon:'success',
          duration: 2000,
          success(res){
            wx.navigateBack({
              delta: 1
            })
          }
        })
      }
    })
  },
  getShopDetail: function(mch_id, shop_id) {
    var that = this
    wx.request({
      url: host + 'tt_shop.php?action=get_detail',
      data: {
        mch_id: mch_id,
      },
      header: {
        'content-type': 'application/json'
      },
      success: function(res) {
        that.setData({
          shop: res.data,
          address:res.data.address
        })
      }
    })
  },
  selectAddress: function () {
    var that = this
    wx.getSetting({
      success(res) {
        if (!res.authSetting['scope.userLocation']) {
          wx.authorize({
            scope: 'scope.userLocation',
            success() {
              that.chooseLocation()
            }
          })
        } else {
          that.chooseLocation()
        }
      }
    })
  },
  chooseLocation: function () {
    var that = this
    wx.chooseLocation({
      success: function (res) {
        that.setData({
          address: res.address,
          latitude: res.latitude,
          longitude: res.longitude
        })
      }
    })
  },
  back: function () {
    wx.navigateBack({
      delta: 1
    })
  }
})