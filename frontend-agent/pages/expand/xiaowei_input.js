// pages/expand/xiaowei_input.js
const host = require('../../config').host
Page({

  /**
   * 页面的初始数据
   */
  data: {
    region: ['', '', '请选择'],
    postcode:'',
    customItem: '全部',
    categories:[
      '请选择',
      '餐饮',
      '线下零售',
      '居民生活服务',
      '休闲娱乐',
      '交通出行',
      '其它'
    ],
    categoryIndex:0,
    address:'',
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
  submit:function(e){
    var name = e.detail.value.name
    var category = e.detail.value.category
    var province = this.data.region[0]
    var city     = this.data.region[1]
    var district = this.data.region[2]
    var address  = this.data.address
    var contact  = e.detail.value.contact
    var contact_phone = e.detail.value.contact_phone
    var latitude = this.data.latitude
    var longitude = this.data.longitude
    var logo_photo_media = this.data.logo_photo_media
    var inside_photo_media = this.data.inside_photo_media
    var postcode = this.data.postcode
    if (!name) {
      wx.showModal({
        title: '请填写商户名称',
        content: '',
        showCancel:false
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
    wx.navigateTo({
      url: 'xiaowei_upload_idcard?name=' + name + '&category=' + category + '&province=' + province + '&city=' + city + '&district=' + district + '&address=' + address + '&contact_phone='+contact_phone+'&contact='+contact+'&latitude='+latitude+'&longitude='+longitude+'&logo_photo_media=' + logo_photo_media + '&inside_photo_media=' + inside_photo_media + '&postcode='+postcode
    })
  },
  selectAddress:function(){
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
  chooseLocation:function(){
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
        var openid = wx.getStorageSync('openid')
        wx.uploadFile({
          url: host + 'tt_shop.php?action=upload_photo',
          filePath: res.tempFilePaths[0],
          name: 'file',
          formData: {
            'uid': wx.getStorageSync('uid'),
            'openid': wx.getStorageSync('openid'),
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
  chooseInsideImage: function () {
    var that = this
    wx.chooseImage({
      sourceType: ['camera', 'album'],
      sizeType: ['compressed'],
      count: 1,
      success: function (res) {
        that.setData({
          insideImageList: res.tempFilePaths
        })
        wx.uploadFile({
          url: host + 'tt_shop.php?action=upload_photo',
          filePath: res.tempFilePaths[0],
          name: 'file',
          formData: {
            'uid': wx.getStorageSync('uid'),
            'openid': wx.getStorageSync('openid'),
            'name': 'indoor',
            'type': 'file',
          },
          success: function (res) {
            var result = JSON.parse(res.data)
            if ('OK' == result.return_msg) {
              that.setData({
                inside_photo_media: result.media_id
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