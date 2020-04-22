// pages/buy/detail.js
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
      url: host + 'groupon.php?action=get_detail',
      data: {
        id: id
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        that.setData({
          grouponData:res.data,
          coupon_unit:'groupon' == res.data.coupon_type ? '张' : '次'
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
  stop: function (e) {
    var that = this
    if ('admin' != wx.getStorageSync('user_role')) {
      wx.showModal({
        title: '温馨提示',
        content: '体验者或非管理员身份不可执行此操作',
        showCancel: false
      })
      return
    }
    wx.showModal({
      title: '确认要终止活动吗？',
      content: '活动终止后顾客已购券仍可正常使用',
      success(res){
        if (res.cancel){
          return
        } else {
          wx.request({
            url: host + 'groupon.php?action=stop',
            data: {
              id: that.data.grouponData.id
            },
            header: {
              'content-type': 'application/json'
            },
            success: function (res) {
              wx.showModal({
                title: "活动已被终止",
                content: "",
                showCancel: false,
                confirmText: "确定",
                success: function () {
                  wx.navigateBack({
                    delta: 1
                  })
                }
              })
            }
          })
        }
      }
    })
  },
  open_sold_list:function(){
    var that = this
    wx.navigateTo({
      url: 'sold_list?groupon_id='+that.data.grouponData.id,
    })
  },
  open_consumed_list: function () {
    var that = this
    wx.navigateTo({
      url: 'coupon_list?coupon_id=' + that.data.grouponData.coupon_id + '&type=consumed',
    })
  },
  open_expired_list: function () {
    var that = this
    wx.navigateTo({
      url: 'coupon_list?coupon_id=' + that.data.grouponData.coupon_id + '&type=expired',
    })
  },
  copydata:function(){
    var that = this
    wx.setClipboardData({
      data: 'pages/groupon/detail?id='+that.data.grouponData.id,
      success(res) {
        wx.getClipboardData({
          success(res) {
          }
        })
      }
    })
  },
  getqrcode:function(){
    var that = this
    if (this.data.grouponData.qrcode_url) {
      this.previewGrouponQrCode(this.data.grouponData.qrcode_url)
      return
    }
    wx.request({
      url: host + 'groupon.php?action=get_qrcode',
      data: {
        id: that.data.grouponData.id
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        that.previewGrouponQrCode(res.data.qrcode_url)
      }
    })
  },
  previewGrouponQrCode:function(url){
    wx.previewImage({
      current: url,
      urls: [url]
    })
  },
  back: function () {
    wx.navigateBack({
      delta: 1
    })
  }
})