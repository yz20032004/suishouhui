// pages/marketing/discount.js
const host = require('../../config').host
var myDate = new Date()
Page({
  data: {
    btn_disabled:false
  },
  onLoad: function (options) {
    // 页面初始化 options为页面跳转所带来的参数

  },
  onReady: function () {
    // 页面渲染完成
  },
  onShow: function () {
    // 页面显示
    var mch = wx.getStorageSync('mch')
    var myDate = new Date();
    var next_date = new Date((myDate / 1000 + 86400) * 1000)
    var end_date = new Date((myDate / 1000 + 86400 * 30) * 1000)
    this.setData({
      date_start: '请选择',
      date_end: '请选择',
      select_begin_date: next_date.getFullYear() + '-' + (next_date.getMonth() + 1) + '-' + next_date.getDate(),
      select_end_date: end_date.getFullYear() + '-' + (end_date.getMonth() + 1) + '-' + end_date.getDate(),
      btn_disabled: mch.mch_type == 'xiaowei' ? true : false
    })
  },
  onHide: function () {
    // 页面隐藏
  },
  onUnload: function () {
    // 页面关闭
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
    var discount = e.detail.value.discount
    var reduce_max = e.detail.value.reduce_max
    var date_start = e.detail.value.date_start
    var date_end = e.detail.value.date_end
    if (!discount) {
      wx.showModal({
        title: "请填写活动折扣",
        content: "",
        showCancel: false,
        confirmText: "确定"
      })
      return false
    }
    if (discount > 9.9 || discount < 1) {
      wx.showModal({
        title: "活动折扣只能在1~9.9之间",
        content: "",
        showCancel: false,
        confirmText: "确定"
      })
      return false
    }
    if ('请选择' == date_start) {
      wx.showModal({
        title: "请填写活动开始日期",
        content: "",
        showCancel: false,
        confirmText: "确定"
      })
      return false
    }
    if ('请选择' == date_end) {
      wx.showModal({
        title: "请填写活动结束日期",
        content: "",
        showCancel: false,
        confirmText: "确定"
      })
      return false
    }
    var startDate = new Date(Date.parse(date_start))
    var endDate = new Date(Date.parse(date_end))
    if (startDate > endDate) {
      wx.showModal({
        title: "活动开始日期不能大于结束日期",
        content: "",
        showCancel: false,
        confirmText: "确定"
      })
      return false
    }
    var title = date_start + '至' + date_end + '折扣活动'
    wx.request({
      url: host + 'marketing.php?action=discount',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
        title: title,
        discount: discount,
        reduce_max: reduce_max,
        date_start: date_start,
        date_end: date_end
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        if ('fail' == res.data) {
          wx.showModal({
            title: "该时段内已有活动",
            content: "",
            showCancel: false,
            confirmText: "确定"
          })
          return false
        } else {
          wx.showToast({
            title: '操作成功',
            icon: 'success',
            duration: 2000,
            success: function () {
              wx.navigateBack({
                delta: 1
              })
            }
          })
        }
      }
    })
  },
  bindTimeChange: function (e) {
    this.setData({
      time: e.detail.value
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
  previewDiscountImage: function (e) {
    var current = 'https://keyoucrmcard.oss-cn-hangzhou.aliyuncs.com/xiaowei/20190506/discount_demo.jpg'
    wx.previewImage({
      current: current,
      urls: [current]
    })
  },
  back: function () {
    wx.navigateBack({
      delta: 1
    })
  }
})
