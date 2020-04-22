// pages/shop/add.js
const host = require('../../config').host
Page({

  /**
   * 页面的初始数据
   */
  data: {
    region: ['', '', '请选择'],
    postcode: '',
    customItem: '全部',
    address: '',
    imageList: [],
    display: '',
    store_entrance_media: ''
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
    var branch_name = e.detail.value.name
    var province = this.data.region[0]
    var city = this.data.region[1]
    var district = this.data.region[2]
    var address = this.data.address
    var latitude = this.data.latitude
    var longitude = this.data.longitude
    var store_entrance_media = this.data.store_entrance_media
    if (!branch_name) {
      wx.showModal({
        title: '请填写商户分店名称',
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
    if (!store_entrance_media) {
      wx.showModal({
        title: '请上传门头照',
        content: '',
        showCancel: false
      })
      return
    }
    wx.request({
      url: host + 'tt_shop.php?action=add',
      data: {
        store_entrance_media: store_entrance_media,
        branch_name: branch_name,
        province: province,
        city: city,
        district: district,
        address: address,
        latitude: latitude,
        longitude: longitude,
        mch_id:wx.getStorageSync('mch_id')
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
            title: '增加分店成功',
            content: '',
            showCancel: false,
            success(res) {
              wx.navigateBack({
                delta: -1
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
  chooseStoreEntranceImage: function () {
    var that = this
    wx.chooseImage({
      sourceType: ['camera', 'album'],
      sizeType: ['original'],
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
  back: function () {
    wx.navigateBack({
      delta: -1
    })
  }
})