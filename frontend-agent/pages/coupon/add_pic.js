// pages/coupon/add_pic.js
const host = require('../../config').host
Page({
  data: {
    imageList: [],
    display: '',
    icon_url: '',
    image_url: new Array
  },
  onLoad: function (options) {
    // 页面初始化 options为页面跳转所带来的参数
    this.setData({
      coupon_id:options.id,
      coupon_name:options.name
    })
  },
  onReady: function () {
    // 页面渲染完成
  },
  onShow: function () {
    // 页面显示
  },
  onHide: function () {
    // 页面隐藏
  },
  onUnload: function () {
    // 页面关闭
  },
  chooseImage: function () {
    var that = this
    wx.chooseImage({
      sourceType: ['camera', 'album'],
      sizeType: ['compressed'],
      count: 1,
      success: function (res) {
        that.setData({
          imageList: res.tempFilePaths
        })
        wx.uploadFile({
          url: host + 'ssh_coupon.php?action=upload_photo',
          filePath: res.tempFilePaths[0],
          name: 'file',
          formData: {
            'coupon_id': that.data.coupon_id,
          },
          header: {
            'content-type': 'application/json'
          },
          success: function (res) {
            var result = JSON.parse(res.data)
            that.setData({
              'icon_url':result.pic_url
            })
          }
        })
      }
    })
  },
  chooseTextImage: function () {
    var that = this
    var length = 0
    wx.chooseImage({
      sourceType: ['camera', 'album'],
      sizeType: ['compressed'],
      count: 9,
      success: function (res) {
        that.setData({
          textImageList: res.tempFilePaths
        })
        for(var i=0;i<res.tempFilePaths.length;i++){
          wx.uploadFile({
            url: host + 'ssh_coupon.php?action=upload_photo',
            filePath: res.tempFilePaths[i],
            name: 'file',
            formData: {
              'coupon_id': that.data.coupon_id,
            },
            header: {
              'content-type': 'application/json'
            },
            success: function (res) {
              var result = JSON.parse(res.data)
              length = that.data.image_url.length
              that.data.image_url[length] = result.pic_url
            }
          })
        }
      }
    })
  },
  previewImage: function (e) {
    var current = e.target.dataset.src
    wx.previewImage({
      current: current,
      urls: this.data.imageList
    })
  },
  submit: function (e) {
    var that = this
    if (!this.data.icon_url) {
      wx.showModal({
        title: "请上传封面照片",
        content: "",
        showCancel: false,
        confirmText: "确定"
      })
      return false
    }
    wx.showLoading({
      title: '加载中',
    })
    var image_url_str = ''
    var length = this.data.image_url.length
    if (length > 0) {
      image_url_str = this.data.image_url.toString()
    }
    wx.request({
      url: host + 'ssh_coupon.php?action=update_abstract',
      data: {
        coupon_id: that.data.coupon_id,
        icon_url:that.data.icon_url,
        image_url:image_url_str
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        wx.hideLoading()
        wx.showModal({
          title: '设置成功',
          content: '',
          showCancel:false,
          success(res){
            wx.navigateBack({
              delta: 2
            })
          }
        })
      }
    })
  }
})
