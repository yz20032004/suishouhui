// pages/setting/selfpay_detail.js
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
      url: host + 'mch.php?action=get_scan_counter',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        that.setData({
          counter:res.data
        })
      }
    })
  },
  previewQrcode: function (e) {
    var current = e.currentTarget.dataset.src
    wx.previewImage({
      current: current,
      urls: [current]
    })
  },
  del:function(e){
    if ('admin' != wx.getStorageSync('user_role')) {
      wx.showModal({
        title: '温馨提示',
        content: '体验者或非管理员身份不可执行此操作',
        showCancel: false
      })
      return
    }
    var id = e.currentTarget.dataset.id
    wx.showModal({
      title: '温馨提示',
      content: '删除后已经张贴该二维码的物料将不可再扫码付款了',
      success(res) {
        if (res.confirm) {
          wx.request({
            url: host + 'mch.php?action=delete_counter',
            data: {
              mch_id: wx.getStorageSync('mch_id'),
              id: id
            },
            header: {
              'content-type': 'application/json'
            },
            success: function (res) {
              wx.navigateBack({
                delta: 1
              })
            }
          })
        } else if (res.cancel) {
          return
        }
      }
    })
  },
  download:function(){
    var that = this
    wx.downloadFile({
      url: that.data.counter.wxcode_url, 
      success(res) {
        if (res.statusCode === 200) {
          wx.saveImageToPhotosAlbum({
            filePath:res.tempFilePath
          })
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
  back: function () {
    wx.navigateBack({
      delta: 1
    })
  }
})
