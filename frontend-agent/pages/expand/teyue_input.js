// pages/expand/teyue_input.js
const host = require('../../config').host
Page({

  /**
   * 页面的初始数据
   */
  data: {
    region: ['', '', '请选择'],
    postcode:'',
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
    marketings:[
      {type:'',title:'请选择'},
      {type:'marketing',title:'专业版'},
      {type:'waimai',title:'外卖版'},
      {type:'groupon',title:'团购版'}],
    marketingIndex:0,
    address: '',
    imageList: [],
    display: '',
    permit_display: 'none',
    license_photo_url: '',
    permit_photo_url: '',
    permit_photo_media:''
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
    var marketing_type = e.detail.value.marketing_type
    var province = this.data.region[0]
    var city = this.data.region[1]
    var district = this.data.region[2]
    var address = this.data.address
    var contact = e.detail.value.contact
    var contact_phone = e.detail.value.contact_phone
    var latitude = this.data.latitude
    var longitude = this.data.longitude
    var logo_photo_media    = this.data.logo_photo_media
    var license_photo_media = this.data.license_photo_media
    var permit_photo_media = this.data.permit_photo_media
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
    if ('' == marketing_type) {
      wx.showModal({
        title: '请选择营销版本',
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
        title: '请上传门头照',
        content: '',
        showCancel: false
      })
      return
    }
    if (!license_photo_media) {
      wx.showModal({
        title: '请上传个体工商户营业执照',
        content: '',
        showCancel: false
      })
      return
    }
    if (this.data.categoryIndex == '1' && !permit_photo_media) {
      wx.showModal({
        title: '请上传餐饮经营许可证',
        content: '',
        showCancel: false
      })
      return
    }
    wx.navigateTo({
      url: 'teyue_upload_idcard?name=' + name + '&category=' + category + '&marketing_type='+marketing_type+'&province=' + province + '&city=' + city + '&district=' + district + '&address=' + address + '&contact_phone=' + contact_phone + '&contact=' + contact + '&latitude=' + latitude + '&longitude=' + longitude + '&license_photo_media=' + license_photo_media + '&permit_photo_media=' + permit_photo_media + '&postcode='+postcode + '&logo_photo_media=' + logo_photo_media
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
          url: host + 'ssh_shop.php?action=upload_photo',
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
  chooseLicenseImage: function () {
    var that = this
    wx.chooseImage({
      sourceType: ['camera', 'album'],
      sizeType: ['compressed'],
      count: 1,
      success: function (res) {
        that.setData({
          licenseImageList: res.tempFilePaths
        })
        wx.uploadFile({
          url: host + 'ssh_shop.php?action=upload_photo',
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
              license_photo_media: result.media_id
            })
            //do something
          }
        })
      }
    })
  },
  choosePermitImage: function () {
    var that = this
    wx.chooseImage({
      sourceType: ['camera', 'album'],
      sizeType: ['compressed'],
      count: 1,
      success: function (res) {
        that.setData({
          permitImageList: res.tempFilePaths
        })
        wx.uploadFile({
          url: host + 'ssh_shop.php?action=upload_photo',
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
              permit_photo_media: result.media_id
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
  previewLicenseImage: function (e) {
    var current = e.target.dataset.src

    wx.previewImage({
      current: current,
      urls: this.data.imageList
    })
  },
  previewPermitImage: function (e) {
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
      postcode:code[1]
    })
  },
  bindCategoryChange: function (e) {
    var permit_display = 'none'
    if ('1' == e.detail.value) {
      permit_display = ''
    }
    this.setData({
      categoryIndex: e.detail.value,
      permit_display:permit_display
    })
  },
  bindMarketingTypeChange: function (e) {
    this.setData({
      marketingIndex: e.detail.value,
    })
  },
  back: function () {
    wx.navigateBack({
      delta: -1
    })
  }
})