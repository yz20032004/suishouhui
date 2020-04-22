// pages/vipmember/add.js
const host = require('../../config').host
var myDate = new Date()
Page({

  /**
   * 页面的初始数据
   */
  data: {
    btn_disabled: false,
    display: 'none',
    price_display: 'none',
    amount: 0,
    single_limit: '不限制',
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    var that = this
    wx.request({
      url: host + 'ssh_vipcard.php?action=get_vipgrades',
      data: {
        mch_id: wx.getStorageSync('mch_id')
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        var length = res.data.length
        res.data[length] = { id: 0, grade: 0, name: '请选择' }
        that.setData({
          grades: res.data,
          gradeIndex: res.data.length - 1
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
    this.setData({
      giftData: wx.getStorageSync('vipmember_opengifts')
    })
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
    wx.removeStorageSync('vipmember_opengifts')
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
  del: function (e) {
    var id = e.currentTarget.dataset.id
    var giftData = this.data.giftData
    giftData.splice(id, 1)
    this.setData({
      giftData: giftData
    })
    wx.setStorageSync('vipmember_opengifts', giftData)
  },
  submit: function (e) {
    var grade = e.detail.value.grade
    var price = e.detail.value.price
    var is_limit = e.detail.value.is_limit
    var total_limit = e.detail.value.total_limit
    var pic_url = this.data.pic_url
    if ('0' == grade) {
      wx.showToast({
        title: '请选择一个付费卡',
        icon: 'none',
        duration: 2000
      })
      return false
    }
    if (!pic_url) {
      wx.showToast({
        title: '请上传付费卡图片',
        icon: 'none',
        duration: 2000
      })
      return false
    }
    if (is_limit && !total_limit) {
      wx.showToast({
        title: '请填写限购总份数',
        icon: 'none',
        duration: 2000
      })
      return false
    }
    var coupon_ids = ''
    var coupon_names = ''
    var totals = ''
    var giftData = this.data.giftData
    for (var i = 0; i < giftData.length; i++) {
      coupon_ids += giftData[i].coupon_id + '#'
      coupon_names += giftData[i].coupon_name + '#'
      totals += giftData[i].coupon_total + '#'
    }
    var grade_name = this.data.grades[this.data.gradeIndex].name
    wx.request({
      url: host + 'ssh_vipcard.php?action=create',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
        grade: grade,
        grade_name: grade_name,
        price: price,
        is_limit: is_limit ? 1 : 0,
        total_limit: is_limit ? total_limit : 0,
        pic_url: pic_url,
        coupon_ids: coupon_ids,
        coupon_names: coupon_names,
        totals: totals
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        if ('fail' == res.data) {
          wx.showToast({
            title: '已经创建过该付费卡的售卖活动了',
            icon: 'none',
            duration: 2000
          })
          return false
        } else {
          wx.showToast({
            title: '创建成功',
            icon: 'success',
            duration: 2000,
            success(res) {
              wx.navigateBack({
                url: 'list'
              })
            }
          })
        }
      }
    })
  },
  chooseLogoImage: function () {
    var that = this
    wx.chooseImage({
      sourceType: ['camera', 'album'],
      sizeType: ['original'],
      count: 1,
      success: function (res) {
        that.setData({
          logoImageList: res.tempFilePaths
        })
        wx.uploadFile({
          url: host + 'ssh_vipcard.php?action=upload_logo',
          filePath: res.tempFilePaths[0],
          name: 'file',
          formData: {
            'openid': wx.getStorageSync('openid'),
            'mch_id': wx.getStorageSync('mch_id')
          },
          header: {
            'content-type': 'application/json'
          },
          success: function (res) {
            var result = JSON.parse(res.data)
            that.setData({
              pic_url: result.pic_url
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
  bindGradeChange: function (e) {
    var that = this
    this.setData({
      gradeIndex: e.detail.value,
      price_display: '',
      price: that.data.grades[e.detail.value].catch_value
    })
  },
  opengift: function (e) {
    wx.navigateTo({
      url: 'opengift',
    })
  },
  limitSwitch: function (e) {
    var isChecked = e.detail.value
    if (isChecked) {
      this.setData({
        display: ''
      })
    } else {
      this.setData({
        display: 'none',
      })
    }
  },
  back: function () {
    wx.navigateBack({
      delta: 1
    })
  }
})