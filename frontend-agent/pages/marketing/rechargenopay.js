// pages/marketing/rechargenopay.js
const host = require('../../config').host + 'ssh_'
var myDate = new Date()
Page({
  data: {
    counts: [1.2, 1.5, 2, 3, 4, 5, 6, 7, 8, 9, 10],
    countIndex:0,
    discounts:[0,1,2,3,4,5,6,7,8,9],
    discountIndex: 9,
    discount_title:'当笔9折',
  },
  onLoad: function (options) {
    // 页面初始化 options为页面跳转所带来的参数

  },
  onReady: function () {
    // 页面渲染完成
  },
  onShow: function () {
    var mch = wx.getStorageSync('mch')
    var myDate = new Date();
    var next_date = new Date((myDate / 1000 + 86400) * 1000)
    var end_date = new Date((myDate / 1000 + 86400 * 30) * 1000)
    this.setData({
      date_start: '请选择',
      date_end: '请选择',
      select_begin_date: next_date.getFullYear() + '-' + (next_date.getMonth() + 1) + '-' + myDate.getDate(),
      select_end_date: end_date.getFullYear() + '-' + (end_date.getMonth() + 1) + '-' + end_date.getDate()
    })
  },
  onHide: function () {
    // 页面隐藏
  },
  onUnload: function () {
    // 页面关闭
  },
  submit: function (e) {
    var consume = parseInt(e.detail.value.consume)
    var count   = e.detail.value.count
    var discount = e.detail.value.discount
    var date_start = e.detail.value.date_start
    var date_end = e.detail.value.date_end
    if (!consume) {
      wx.showModal({
        title: "请填写消费金额",
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
    var title = '储值'+count+'倍享受'+this.data.discount_title
    wx.request({
      url: host + 'marketing.php?action=create_rechargenopay',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
        title: title,
        consume: consume,
        count:count,
        discount:discount,
        date_start: date_start,
        date_end: date_end
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        if ('fail' == res.data) {
          wx.showModal({
            title: "该时段内已有同类活动",
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
  bindCountChange: function (e) {
    var that = this
    this.setData({
      countIndex: e.detail.value,
    })
  },
  bindDiscountChange: function (e) {
    var that = this
    if (0 == e.detail.value) {
      var discount_title = '当笔免单'
    } else {
      var discount_title = '当笔'+e.detail.value + '折'
    }
    this.setData({
      discountIndex: e.detail.value,
      discount_title:discount_title
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
  previewImage: function (e) {
    var current = 'http://keyoucrmcard.oss-cn-hangzhou.aliyuncs.com/images/20191110/1573391220.JPG'
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
