// pages/marketing/lbs_coupon.js
const host = require('../../config').host
var myDate = new Date()
Page({
  data: {
    btn_disabled:false,
    couponIndex: 0
  },
  onLoad: function (options) {
    // 页面初始化 options为页面跳转所带来的参数
    var that = this
    wx.request({
      url: host + 'coupon.php?action=get_list',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
        get_type: 'cash'
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        var ret = res.data
        for (var i = 0; i < ret['unenable'].length; i++) {
          ret['enable'][ret['enable'].length + i] = ret['unenable'][i]
        }
        ret['enable'][ret['enable'].length] = { id: 0, name: '请选择' }
        that.setData({
          coupons: ret['enable'],
          couponIndex: ret['enable'].length - 1
        })
      }
    })

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
      btn_disabled: '0' == mch.is_lbs_coupon ? true : false,
      mch_type:mch.mch_type
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
    var title = e.detail.value.title
    var coupon_id = e.detail.value.coupon_id
    var date_start = e.detail.value.date_start
    var date_end = e.detail.value.date_end
    if (!title) {
      wx.showModal({
        title: "请填写活动标题",
        content: "",
        showCancel: false,
        confirmText: "确定"
      })
      return false
    }
    if (title.length > 12) {
      wx.showModal({
        title: "活动标题不能超过12个字",
        content: "",
        showCancel: false,
        confirmText: "确定"
      })
      return false
    }
    if (!coupon_id){
      wx.showModal({
        title: "请选择一张优惠券",
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
    wx.request({
      url: host + 'marketing.php?action=create_lbs_coupon',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
        title: title,
        coupon_id:coupon_id,
        date_start: date_start,
        date_end: date_end
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        if ('fail' == res.data) {
          wx.showModal({
            title: "该时段内已有此活动",
            content: "",
            showCancel: false,
            confirmText: "确定"
          })
          return false
        } else {
          var id = res.data
          wx.showToast({
            title: '操作成功',
            icon: 'success',
            duration: 2000,
            success: function () {
              wx.redirectTo({
                url: '../campaign/detail?id='+id,
              })
            }
          })
        }
      }
    })
  },
  bindCouponChange: function (e) {
    this.setData({
      couponIndex: e.detail.value
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
  previewLbsCouponImage: function (e) {
    var current = 'https://keyoucrmcard.oss-cn-hangzhou.aliyuncs.com/xiaowei/20190506/IMG_3119.jpg'
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
