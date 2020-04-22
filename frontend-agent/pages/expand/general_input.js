// pages/expand/general_input.js
const host = require('../../config').host
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
    store_entrance_media:''
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
    var formId = e.detail.formId
    var name = e.detail.value.name
    var category = e.detail.value.category
    var province = this.data.region[0]
    var city = this.data.region[1]
    var district = this.data.region[2]
    var address = this.data.address
    var contact = e.detail.value.contact
    var contact_phone = e.detail.value.contact_phone
    var latitude = this.data.latitude
    var longitude = this.data.longitude
    var logo_photo_media = this.data.logo_photo_media
    var store_entrance_media = this.data.store_entrance_media
    var postcode = this.data.postcode
    if (!name) {
      wx.showModal({
        title: '请填写商户名称',
        content: '',
        showCancel: false
      })
      return
    }
    if ('请选择' == category) {
      wx.showModal({
        title: '请选择商户经营类目',
        content: '',
        showCancel: false
      })
      return
    }
    if ('请选择' == district) {
      wx.showModal({
        title: '请选择商户所在地区',
        content: '',
        showCancel: false
      })
      return
    }
    if (!address) {
      wx.showModal({
        title: '请选择门店地址',
        content: '',
        showCancel: false
      })
      return
    }
    if (!contact) {
      wx.showModal({
        title: '请填写联系人姓名',
        content: '',
        showCancel: false
      })
      return
    }
    if (!contact_phone) {
      wx.showModal({
        title: '请填写联系电话',
        content: '',
        showCancel: false
      })
      return
    }
    if (!store_entrance_media) {
      wx.showModal({
        title: '请上传门头照',
        content: '',
        showCancel: false
      })
      return
    }
    wx.request({
      url: host + 'tt_mch.php?action=general_apply',
      data: {
        uid: wx.getStorageSync('uid'),
        formId: formId,
        contact: contact,
        mobile: contact_phone,
        logo_photo_media: logo_photo_media,
        store_entrance_media: store_entrance_media,
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
            content: res.data.msg,
            showCancel: false
          })
          return
        } else {
          wx.showModal({
            title: '提交成功',
            content: '请等待大约5分钟，系统将完成商户配置初始化',
            showCancel: false,
            success(res) {
              wx.switchTab({
                url: '../index/index',
              })
            }
          })
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
      sizeType: ['compressed'],
      count: 1,
      success: function (res) {
        that.setData({
          logoImageList: res.tempFilePaths
        })
        wx.uploadFile({
          url: host + 'tt_shop.php?action=upload_photo',
          filePath: res.tempFilePaths[0],
          name: 'file',
          formData: {
            'uid': wx.getStorageSync('uid'),
            'name': 'logo',
            'type': 'file',
          },
          header: {
            'content-type': 'application/json'
          },
          success: function (res) {
            var result = JSON.parse(res.data)
            if ('OK' == result.return_msg) {
              that.setData({
                logo_photo_media: result.media_id
              })
            }
            //do something
          }
        })
      }
    })
  },
  chooseStoreEntranceImage: function () {
    var that = this
    wx.chooseImage({
      sourceType: ['camera', 'album'],
      sizeType: ['compressed'],
      count: 1,
      success: function (res) {
        that.setData({
          storeEntranceImageList: res.tempFilePaths
        })
        wx.uploadFile({
          url: host + 'tt_shop.php?action=upload_photo',
          filePath: res.tempFilePaths[0],
          name: 'file',
          formData: {
            'uid': wx.getStorageSync('uid'),
            'name': 'logo',
            'type': 'file',
          },
          header: {
            'content-type': 'application/json'
          },
          success: function (res) {
            var result = JSON.parse(res.data)
            if ('OK' == result.return_msg) {
              that.setData({
                store_entrance_media: result.media_id
              })
            }
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
  previewStoreEntranceImage: function (e) {
    var current = e.target.dataset.src
    wx.previewImage({
      current: current,
      urls: this.data.imageList
    })
  },
  bindRegionChange: function (e) {
    var code = e.detail.code
    this.setData({
      region: e.detail.value,
      postcode: code[1]
    })
  },
  bindCategoryChange: function (e) {
    this.setData({
      categoryIndex: e.detail.value,
    })
  },
  back: function () {
    wx.navigateBack({
      delta: -1
    })
  }
})