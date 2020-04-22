// pages/expand/xiaowei_input.js
const host = require('../../config').host
const app = getApp()
Page({

  /**
   * 页面的初始数据
   */
  data: {
    region: ['', '', '请选择'],
    postcode: '',
    customItem: '全部',
    categories: [
      '请选择',
      '餐饮',
      '线下零售',
      '居民生活/商业服务',
      '休闲娱乐',
      '交通运输服务',
      '教育/医疗',
      '生活缴费',
      '交通出行/票务旅游',
      '其它'
    ],
    categoryIndex: 0,
    address: '',
    imageList: [],
    display: '',
    logo_photo_url: '',
    inside_photo_url: ''
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {

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
  submit: function (e) {
    var name = e.detail.value.name
    var category = e.detail.value.category
    var province = this.data.region[0]
    var city = this.data.region[1]
    var district = this.data.region[2]
    var address = this.data.address
    var contact = e.detail.value.contact
    var latitude = this.data.latitude
    var longitude = this.data.longitude
    var logo_photo_media = this.data.logo_photo_media
    var inside_photo_media = this.data.inside_photo_media
    var postcode = this.data.postcode
    var contact_phone = wx.getStorageSync('mobile')
    if (!name) {
      wx.showModal({
        title: '请填写您的门店名称',
        content: '',
        showCancel: false
      })
      return
    }
    if ('请选择' == category) {
      wx.showModal({
        title: '请选择您的经营类目',
        content: '',
        showCancel: false
      })
      return
    }
    if ('请选择' == district) {
      wx.showModal({
        title: '请选择您门店所在地区',
        content: '',
        showCancel: false
      })
      return
    }
    if (!address) {
      wx.showModal({
        title: '请选择您的门店地址',
        content: '',
        showCancel: false
      })
      return
    }
    if (!contact) {
      wx.showModal({
        title: '请填写您的姓名',
        content: '',
        showCancel: false
      })
      return
    }
    if (!logo_photo_media) {
      wx.showModal({
        title: '请上传门店门头照',
        content: '',
        showCancel: false
      })
      return
    }
    if (!inside_photo_media) {
      wx.showModal({
        title: '请上传店内照片',
        content: '',
        showCancel: false
      })
      return
    }
    wx.request({
      url: host + 'shop.php?action=selfsubmit',
      data: {
        openid:wx.getStorageSync('openid'),
        logo_photo_media: logo_photo_media,
        inside_photo_media: inside_photo_media,
        name: name,
        category: category,
        province: province,
        city: city,
        district: district,
        address: address,
        postcode: postcode,
        contact: contact,
        contact_phone: contact_phone,
        latitude: latitude,
        longitude: longitude,
        mch_type: 'general'
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        if ('success' != res.data) {
          wx.showModal({
            title: '提交信息有误',
            content: '',
            showCancel: false
          })
          return
        } else {
          wx.showLoading({
            title: '系统配置中',
          })
          wx.removeStorageSync('user')
          wx.removeStorageSync('mch')
          app.getMember()
          app.getMch()
          setTimeout(function () {
            wx.hideLoading()
            wx.showModal({
              title: '您的商户创建成功',
              content: '请先去配置会员权益吧',
              showCancel: false,
              success(res) {
                wx.reLaunch({
                  url: '../index/index',
                })
              }
            })
          }, 5000)
        }
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
  chooseShopLogoImage: function () {
    var that = this
    wx.chooseImage({
      sourceType: ['camera', 'album'],
      sizeType: ['original'],
      count: 1,
      success: function (res) {
        that.setData({
          logoImageList: res.tempFilePaths
        })
        var openid = wx.getStorageSync('openid')
        wx.uploadFile({
          url: host + 'shop.php?action=upload_photo',
          filePath: res.tempFilePaths[0],
          name: 'file',
          formData: {
            'openid': wx.getStorageSync('openid'),
            'name': 'logo',
            'type': 'file',
          },
          header: {
            'content-type': 'application/json'
          },
          success: function (res) {
            var result = JSON.parse(res.data)
            that.setData({
              logo_photo_media: result.media_id
            })
            //do something
          }
        })
      }
    })
  },
  chooseInsideImage: function () {
    var that = this
    wx.chooseImage({
      sourceType: ['camera', 'album'],
      sizeType: ['original'],
      count: 1,
      success: function (res) {
        that.setData({
          insideImageList: res.tempFilePaths
        })
        wx.uploadFile({
          url: host + 'shop.php?action=upload_photo',
          filePath: res.tempFilePaths[0],
          name: 'file',
          formData: {
            'openid': wx.getStorageSync('openid'),
            'name': 'indoor',
            'type': 'file',
          },
          success: function (res) {
            var result = JSON.parse(res.data)
            that.setData({
              inside_photo_media: result.media_id
            })
          }
        })
      }
    })
  },
  previewLogoImage: function (e) {
    var current = e.target.dataset.src

    wx.previewImage({
      current: current,
      urls: this.data.imageList
    })
  },
  previewInsideImage: function (e) {
    var current = e.target.dataset.src

    wx.previewImage({
      current: current,
      urls: this.data.imageList
    })
  },
  bindRegionChange: function (e) {
    var code = e.detail.code
    if ('110000' == code[0] || '120000' == code[0] || '310000' == code[0] || '500000' == code[0]) {
      var postcode = code[0]
    } else {
      var postcode = code[1]
    }
    this.setData({
      region: e.detail.value,
      postcode:postcode
    })
  },
  bindCategoryChange: function (e) {
    this.setData({
      categoryIndex: e.detail.value
    })
  },
  back: function () {
    wx.navigateBack({
      delta: -1
    })
  }
})