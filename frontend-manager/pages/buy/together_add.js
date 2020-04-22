// pages/buy/add.js
const host = require('../../config').host
var myDate = new Date()
Page({

  /**
   * 页面的初始数据
   */
  data: {
    btn_disabled: false,
    display: 'none',
    amount_display: 'none',
    price: 0,
    together_total: '2人',
    counts: [2, 3, 4, 5, 6, 7, 8, 9, 10],
    totalIndex: 0,
    together_times: '24小时',
    times: [12, 24, 48, 72],
    timeIndex: 1,
    single_limit: '不限制',
    limits: ['不限制', 1, 2, 3, 4, 5, 6, 7, 8, 9, 10,15,20],
    limitIndex:0,
    select_begin_date: myDate.toLocaleDateString(),
    select_end_date: (myDate.getFullYear() + 1) + '-' + (myDate.getMonth() + 1) + '-' + myDate.getDate()
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    var that = this
    wx.request({
      url: host + 'together.php?action=get_enable_coupons',
      data: {
        mch_id: wx.getStorageSync('mch_id')
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        var length = res.data.length
        res.data[length] = { id: 0, name: '请选择' }
        that.setData({
          coupons: res.data,
          couponIndex: res.data.length - 1
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
    // 页面显示
    var mch = wx.getStorageSync('mch')
    var myDate = new Date();
    var next_date = new Date((myDate / 1000 + 86400) * 1000)
    var end_date = new Date((myDate / 1000 + 86400 * 30) * 1000)
    this.setData({
      date: '请选择',
      date_start: next_date.getFullYear() + '-' + (next_date.getMonth() + 1) + '-' + next_date.getDate(),
      date_end: end_date.getFullYear() + '-' + (end_date.getMonth() + 1) + '-' + end_date.getDate(),
      btn_disabled: '0' == mch.is_groupon ? true : false
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

  },
  submit: function (e) {
    if ('admin' != wx.getStorageSync('user_role')) {
      wx.showModal({
        title: '温馨提示',
        content: '体验者或非管理员身份不可执行此操作',
        showCancel: false
      })
      return
    }
    var that = this
    var coupon_id = e.detail.value.coupon_id
    var amount = e.detail.value.amount
    var price = e.detail.value.price
    var people = e.detail.value.people
    var price = e.detail.value.price
    var expire_times = e.detail.value.expire_times
    var is_limit = e.detail.value.is_limit
    var total_limit = e.detail.value.total_limit
    var single_limit = that.data.limitIndex != 0 ? e.detail.value.single_limit : 0
    var date_start = e.detail.value.date_start
    var date_end = e.detail.value.date_end
    if ('0' == coupon_id) {
      wx.showToast({
        title: '请选择一个团购券',
        icon: 'none',
        duration: 2000
      })
      return false
    }
    if (!price) {
      wx.showToast({
        title: '请填写拼团价格',
        icon: 'none',
        duration: 2000
      })
      return false
    }
    if (parseFloat(price) >= parseFloat(amount)) {
      wx.showToast({
        title: '拼团价值不能高于团购券原价',
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
    wx.request({
      url: host + 'together.php?action=create',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
        coupon_id: coupon_id,
        coupon_name:that.data.coupons[that.data.couponIndex].name,
        amount:amount,
        price: price,
        people: people,
        expire_times: expire_times,
        is_limit: is_limit ? 1 : 0,
        total_limit: total_limit,
        single_limit: single_limit,
        date_start: date_start,
        date_end: date_end
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        if ('fail' == res.data) {
          wx.showToast({
            title: '已经有此拼团活动了',
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
                delta:1
              })
            }
          })
        }
      }
    })
  },
  bindTotalChange: function (e) {
    var that = this
    this.setData({
      totalIndex: e.detail.value,
      together_total: that.data.counts[e.detail.value] + '人',
    })
  },
  bindTimeChange: function (e) {
    var that = this
    this.setData({
      timeIndex: e.detail.value,
      together_times: that.data.times[e.detail.value] + '小时'
    })
  },
  bindDateStartChange: function (e) {
    this.setData({
      date_start: e.detail.value
    })
  },
  bindDateEndChange: function (e) {
    this.setData({
      date_end: e.detail.value
    })
  },
  bindCouponChange: function (e) {
    var that = this
    this.setData({
      couponIndex: e.detail.value,
      amount: that.data.coupons[e.detail.value].amount,
      amount_display: ''
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
  bindLimitChange: function (e) {
    var that = this
    var single_limit = that.data.limits[e.detail.value] + '份'
    if (e.detail.value == 0) {
      var single_limit = '不限制'
    }
    this.setData({
      limitIndex: e.detail.value,
      single_limit: single_limit
    })
  },
  back: function () {
    wx.navigateBack({
      delta: 1
    })
  }
})