// pages/expand/xiaowei_upload_idcard.js
const host = require('../../config').host
Page({

  /**
   * 页面的初始数据
   */
  data: {
    accountbanks: [
      '请选择',
      '工商银行',
      '交通银行',
      '招商银行',
      '民生银行',
      '中信银行',
      '浦发银行',
      '兴业银行',
      '光大银行',
      '广发银行',
      '平安银行',
      '北京银行',
      '华夏银行',
      '农业银行',
      '建设银行',
      '邮政储蓄银行',
      '中国银行',
      '宁波银行'
    ],
    bankIndex: 0,
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    this.setData({
      shopData:options
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
  chooseHeadImage: function () {
    var that = this
    wx.chooseImage({
      sourceType: ['camera', 'album'],
      sizeType: ['compressed'],
      count: 1,
      success: function (res) {
        that.setData({
          headImageList: res.tempFilePaths
        })
        wx.uploadFile({
          url: host + 'tt_shop.php?action=upload_id_photo',
          filePath: res.tempFilePaths[0],
          name: 'file',
          formData: {
            'uid': wx.getStorageSync('uid'),
            'type': 'file',
            'side': 'face'
          },
          header: {
            'content-type': 'application/json'
          },
          success: function (res) {
            var result = JSON.parse(res.data)
            if ('200' != result.http_code) {
              wx.showModal({
                title: "请上传正确的身份证人像面照片",
                content: "",
                showCancel: false,
                confirmText: "确定"
              })
              return false
            } else {
              that.setData({
                head_photo_media: result.media_id
              })
            }
            //do something
          }
        })
      }
    })
  },
  chooseCountryImage: function () {
    var that = this
    wx.chooseImage({
      sourceType: ['camera', 'album'],
      sizeType: ['compressed'],
      count: 1,
      success: function (res) {
        that.setData({
          countryImageList: res.tempFilePaths
        })
        wx.uploadFile({
          url: host + 'tt_shop.php?action=upload_id_photo',
          filePath: res.tempFilePaths[0],
          name: 'file',
          formData: {
            'uid': wx.getStorageSync('uid'),
            'type': 'file',
            'side': 'back'
          },
          success: function (res) {
            var result = JSON.parse(res.data)
            if ('200' != result.http_code) {
              wx.showModal({
                title: "请上传正确的身份证国徽面照片",
                content: "",
                showCancel: false,
                confirmText: "确定"
              })
              return false
            } else {
              that.setData({
                country_photo_media: result.media_id
              })
            }
          }
        })
      }
    })
  },
  previewHeadImage: function (e) {
    var current = e.target.dataset.src

    wx.previewImage({
      current: current,
      urls: this.data.imageList
    })
  },
  previewCountryImage: function (e) {
    var current = e.target.dataset.src

    wx.previewImage({
      current: current,
      urls: this.data.imageList
    })
  },
  submit: function (e) {
    var formId = e.detail.formId
    var account_bank = e.detail.value.account_bank
    var head_photo_media = e.detail.value.head_photo_media
    var country_photo_media = e.detail.value.country_photo_media
    var account_number = e.detail.value.account_number
    if (!head_photo_media) {
      wx.showModal({
        title: "请上传身份证人像面照片",
        content: "",
        showCancel: false,
        confirmText: "确定"
      })
      return false
    }
    if (!country_photo_media) {
      wx.showModal({
        title: "请上传身份证国徽面照片",
        content: "",
        showCancel: false,
        confirmText: "确定"
      })
      return false
    }
    if (this.data.bankIndex == '0') {
      wx.showModal({
        title: "请选择收款银行",
        content: "",
        showCancel: false,
        confirmText: "确定"
      })
      return false
    }
    if (!account_number) {
      wx.showModal({
        title: "请填写银行卡号",
        content: "",
        showCancel: false,
        confirmText: "确定"
      })
      return false
    }
    var shopData = this.data.shopData
    wx.request({
      url: host + 'tt_mch.php?action=apply',
      data: {
        uid: wx.getStorageSync('uid'),
        formId: formId,
        contact:shopData.contact,
        mobile: shopData.contact_phone,
        logo_photo_media: shopData.logo_photo_media,
        inside_photo_media: shopData.inside_photo_media,
        name:shopData.name,
        category: shopData.category,
        province: shopData.province,
        city: shopData.city,
        district: shopData.district,
        postcode: shopData.postcode,
        address: shopData.address,
        contact: shopData.contact,
        contact_phone: shopData.contact_phone,
        latitude: shopData.latitude,
        longitude: shopData.longitude,
        head_photo_media: head_photo_media,
        country_photo_media: country_photo_media,
        account_bank:account_bank,
        account_number: account_number,
        mch_type: 'xiaowei'
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        if ('success' != res.data) {
          wx.showModal({
            title: '提交信息有误',
            content: res.data.msg,
            showCancel: false
          })
          return
        } else {
          wx.showModal({
            title: '提交成功',
            content: '请等待微信审核',
            showCancel:false,
            success(res){
              wx.switchTab({
                url: '../index/index',
              })
            }
          })
        }
      }
    })
  },
  bindAccountbankChange: function (e) {
    this.setData({
      bankIndex: e.detail.value
    })
  },
  back: function () {
    wx.navigateBack({
      delta: -1
    })
  }
})
