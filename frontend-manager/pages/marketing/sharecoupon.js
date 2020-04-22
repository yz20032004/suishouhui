// pages/marketing/paygift.js
const host = require('../../config').host
var myDate = new Date()
Page({

  /**
   * 页面的初始数据
   */
  data: {
    counts: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 15, 20,30],
    countIndex: 9,
    percents: [10, 20, 30, 40, 50, 60, 70, 80, 90, 100],
    couponIndex: 0,
    percentIndex: 4,
    percentAll:0,
    giftData:[],
    btn_disabled:false
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function(options) {
    var that = this
    wx.request({
      url: host + 'coupon.php?action=get_list',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
      },
      header: {
        'content-type': 'application/json'
      },
      success: function(res) {
        var ret = res.data
        for (var i = 0; i < ret['unenable'].length; i++) {
          ret['enable'][ret['enable'].length + i] = ret['unenable'][i]
        }
        ret['enable'][ret['enable'].length] = {
          id: 0,
          name: '请选择'
        }
        that.setData({
          coupons: ret['enable'],
          couponIndex: ret['enable'].length - 1,
        })
      }
    })
  },

  /**
   * 生命周期函数--监听页面初次渲染完成
   */
  onReady: function() {

  },

  /**
   * 生命周期函数--监听页面显示
   */
  onShow: function() {
    // 页面显示
  },
  submit: function(e) {
    if ('admin' != wx.getStorageSync('user_role')) {
      wx.showModal({
        title: '温馨提示',
        content: '体验者或非管理员身份不可执行此操作',
        showCancel: false
      })
      return
    }
    var that = this
    var count = e.detail.value.count
    var giftData = this.data.giftData
    if (giftData.length == 0) {
      wx.showToast({
        title: '请添加优惠券',
        icon: 'none'
      })
      return
    }
    if (this.data.percentAll != 100) {
      wx.showToast({
        title: '所有券的抽中机率之和应该为100',
        icon: 'none'
      })
      return
    }
    var coupon_string = encodeURIComponent(JSON.stringify(giftData))
    var mch = wx.getStorageSync('mch')
    wx.request({
      url: host + 'marketing.php?action=create_sharecoupon',
      data: {
        mch_id: mch.mch_id,
        appid:mch.appid,
        openid:wx.getStorageSync('openid'),
        count: count,
        coupons: coupon_string,
      },
      header: {
        'content-type': 'application/json'
      },
      success: function(res) {
        that.setData({
          btn_disabled:true
        })
        that.previewQrCode(res.data)
      }
    })
  },
  /**
   * 生命周期函数--监听页面隐藏
   */
  onHide: function() {

  },

  /**
   * 生命周期函数--监听页面卸载
   */
  onUnload: function() {

  },

  /**
   * 页面相关事件处理函数--监听用户下拉动作
   */
  onPullDownRefresh: function() {

  },

  /**
   * 页面上拉触底事件的处理函数
   */
  onReachBottom: function() {

  },
  add_coupon:function(){
    var that = this
    var couponIndex = this.data.couponIndex
    var coupon_id = this.data.coupons[couponIndex].id
    if ('0' == coupon_id) {
      wx.showModal({
        title: "请选择优惠券",
        content: "",
        showCancel: false,
        confirmText: "确定"
      })
      return false
    }
    var giftData = this.data.giftData
    var percentAll = this.data.percents[this.data.percentIndex]
    for (var i = 0; i < giftData.length; i++) {
      percentAll += giftData[i].percent
      if (giftData[i].id == coupon_id) {
        wx.showModal({
          title: '已经有此优惠券了',
          content: '',
          showCancel: false
        })
        return
      }
    }
    if (percentAll  >  100) {
      wx.showModal({
        title: '所有券的抽中机率之和应该为100%',
        content: '',
        showCancel: false
      })
      return
    }
    var newGift = {
      id: coupon_id,
      name: this.data.coupons[this.data.couponIndex].name,
      percent: this.data.percents[this.data.percentIndex]
    }
    giftData[giftData.length] = newGift
    this.setData({
      giftData: giftData,
      couponIndex: that.data.coupons.length - 1,
      percentIndex:4,
      percentAll:percentAll
    })
  },
  del: function (e) {
    var id = e.currentTarget.dataset.id
    var giftData = this.data.giftData
    giftData.splice(id, 1)
    this.setData({
      giftData: giftData
    })
  },
  previewImage: function (e) {
    var current = 'http://keyoucrmcard.oss-cn-hangzhou.aliyuncs.com/images/20191110/1573392157.jpeg'
    wx.previewImage({
      current: current,
      urls: [current]
    })
  },
  bindCouponChange: function(e) {
    this.setData({
      couponIndex: e.detail.value
    })
  },
  bindCountChange: function(e) {
    this.setData({
      countIndex: e.detail.value
    })
  },
  bindPercentChange: function(e) {
    this.setData({
      percentIndex: e.detail.value
    })
  },
  previewQrCode:function(url){
    wx.previewImage({
      current: url,
      urls: [url]
    })
  },
  back: function() {
    wx.navigateBack({
      delta: 1
    })
  }
})