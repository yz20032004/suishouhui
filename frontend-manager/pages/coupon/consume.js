// pages/coupon/consume.js
const host = require('../../config').host
Page({

  /**
   * 页面的初始数据
   */
  data: {
    image_reduce: 'reduce_disable',
    image_add: 'add',
    consume_total:1
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    var disabled = false;
    var coupon = JSON.parse(options.params)
    var now = new Date()
    var date_start = new Date(coupon.date_start)
    var date_end = new Date(coupon.date_end)
    var dateTime = date_end.setDate(date_end.getDate() + 1);
    date_end = new Date(dateTime)
    if (now < date_start) {
      disabled = true
    } else if (now > date_end) {
      disabled = true
    }
    this.setData({
      coupon: coupon,
      disabled:disabled
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
    var code = e.detail.value.code
    var coupon_amount = e.detail.value.coupon_amount
    var user = wx.getStorageSync('user')
    if (!coupon_amount) {
      wx.showModal({
        title: "请填写券优惠金额",
        content: "",
        showCancel: false,
        confirmText: "确定"
      })
      return false;
    }
    wx.request({
      url: host + 'coupon.php?action=consume_code',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
        code: code,
        coupon_amount:coupon_amount,
        created: wx.getStorageSync('current_shop_name') + user.name,
        shop_id:wx.getStorageSync('current_shop_id'),
        consume_total:that.data.consume_total
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        if ('success' == res.data) {
          wx.showModal({
            title: "核销成功",
            content: "",
            showCancel: false,
            confirmText: "确定",
            success: function (res) {
              wx.navigateBack({
                delta: 1
              })
            }
          })
          return false
        } else {
          wx.showModal({
            title: '核销失败',
            content: '此券已经核销过了',
            showCancel:false
          })
        }
      }
    })
  },

  reduce_total: function () {
    var consume_total = this.data.consume_total - 1
    var image_reduce = 'reduce'
    if (consume_total == 1) {
      image_reduce = 'reduce_disable'
    } else if (consume_total < 1) {
      return
    }
    this.setData({
      consume_total: consume_total,
      image_add: 'add',
      image_reduce: image_reduce,
    })
  },
  add_total: function () {
    var consume_total = this.data.consume_total + 1
    var image_add = 'add'
    if (consume_total == this.data.coupon.total) {
      image_add = 'add_disable'
    } else if (consume_total > this.data.coupon.total) {
      wx.showToast({
        icon: 'none',
        title: '最多只能核销' + this.data.coupon.total + '张',
      })
      return;
    }
    this.setData({
      consume_total: consume_total,
      image_add: image_add,
      image_reduce: 'reduce',
    })
  },
  back: function () {
    wx.navigateBack({
      delta: 1
    })
  }
})
