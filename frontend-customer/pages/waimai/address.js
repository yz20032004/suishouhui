const host = require('../../config').host
Page({

  /**
   * 页面的初始数据
   */
  data: {
    name:'',
    mobile:'',
    address: '',
    address_no:'',
    display: ''
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {

    var that = this
    wx.request({
      url: host + 'huipay/user.php?action=get_waimai_address',
      data: {
        mch_id:wx.getStorageSync('mch_id'),
        openid:wx.getStorageSync('openid')
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        if (res.data) {
          that.setData({
            name:res.data.name,
            mobile:res.data.mobile,
            address:res.data.address,
            address_no:res.data.address_no,
            latitude:res.data.latitude,
            longitude:res.data.longitude
          })
        } else {
          var member = wx.getStorageSync('member')
          that.setData({
            name:member.name,
            mobile:member.mobile
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
  submit: function (e) {
    var that = this
    var address = this.data.address
    var contact_name   = e.detail.value.contact_name
    var contact_mobile = e.detail.value.contact_mobile
    var address_no     = e.detail.value.address_no
    var latitude = this.data.latitude
    var longitude = this.data.longitude

    if (!contact_name) {
      wx.showModal({
        title: '请填写收货人姓名',
        content: '',
        showCancel: false
      })
      return
    }
    if (!contact_mobile) {
      wx.showModal({
        title: '请填写收货人手机号',
        content: '',
        showCancel: false
      })
      return
    }
    if (!address) {
      wx.showModal({
        title: '请选择收货人地址',
        content: '',
        showCancel: false
      })
      return
    }
    if (!address_no) {
      wx.showModal({
        title: '请填写收货人详细门牌号',
        content: '',
        showCancel: false
      })
      return
    }
    wx.request({
      url: host + 'huipay/user.php?action=update_waimai_address',
      data: {
        mch_id:wx.getStorageSync('mch_id'),
        openid:wx.getStorageSync('openid'),
        name: contact_name,
        mobile: contact_mobile,
        address: address,
        address_no:address_no,
        latitude: latitude,
        longitude: longitude,
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
            title: '保存成功',
            content: '',
            showCancel: false,
            success(res) {
              that.back()
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
        if (!res.authSetting.hasOwnProperty('scope.userLocation')) {
          wx.authorize({
            scope: 'scope.userLocation',
            success() {
              that.chooseLocation()
            }
          })
        } else {
          if (!res.authSetting['scope.userLocation']) {
            wx.showModal({
              title: '请打开地理位置授权',
              showCancel:false,
              success(res){
                wx.openSetting({
                  success (res) {
                  }
                })
              }
            })
          } else {
            that.chooseLocation()
          }
        }
      }
    })
  },
  chooseLocation: function () {
    var that = this
    wx.chooseLocation({
      success: function (res) {
        that.setData({
          address: res.address+res.name,
          latitude: res.latitude,
          longitude: res.longitude
        })
      }
    })
  },
  bindRegionChange: function (e) {
    var code = e.detail.code
    this.setData({
      region: e.detail.value,
    })
  },
  back: function () {
    wx.navigateBack({
      delta: -1
    })
  }
})