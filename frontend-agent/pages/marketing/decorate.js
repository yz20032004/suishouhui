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
    var length = 0
    wx.chooseImage({
      sourceType: ['camera', 'album'],
      sizeType: ['compressed'],
      count: 9,
      success: function (res) {
        that.setData({
          imageList: res.tempFilePaths
        })
        for(var i=0;i<res.tempFilePaths.length;i++){
          wx.uploadFile({
            url: host + 'coupon.php?action=upload_photo',
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
    if ('admin' != wx.getStorageSync('user_role')) {
      wx.showModal({
        title: '温馨提示',
        content: '体验者或非管理员身份不可执行此操作',
        showCancel: false
      })
      return
    }
    wx.showLoading({
      title: '加载中',
    })
    var image_url_str = ''
    var length = this.data.image_url.length
    if (length > 0) {
      image_url_str = this.data.image_url.toString()
    } else {
      wx.showModal({
        title: '请至少上传一张图片',
        content: '',
        showCancel: false
      })
      return
    }
    wx.request({
      url: host + 'mch.php?action=update_top_background_img',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
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
              delta: 1
            })
          }
        })
      }
    })
  }
})
