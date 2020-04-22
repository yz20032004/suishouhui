// pages/marketing/rebate.js
const host = require('../../config').host
const app = getApp()
var myDate = new Date()
Page({
  data: {
    couponIndex: 0,
    date: "2016-09-01",
    time: "12:01",
    rules: [
      { type: 'ge', title: "单笔消费每满" },
      { type: 'egt', title: "单笔消费达到" }
    ],
    ruleIndex: 1,
    rebate_condition_title: '消费达到',
    select_begin_date: myDate.toLocaleDateString(),
    select_end_date: (myDate.getFullYear() + 1) + '-' + (myDate.getMonth() + 1) + '-' + myDate.getDate()
  },
  onLoad: function (options) {
    // 页面初始化 options为页面跳转所带来的参数
    var that = this
    this.getGrades()
    wx.request({
      url: host + 'coupon.php?action=get_list',
      data: {
        mch_id: wx.getStorageSync('mch_id')
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
  getGrades: function () {
    var that = this
    wx.request({
      url: host + 'mch.php?action=get_grades',
      data: {
        mch_id: wx.getStorageSync('mch_id')
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        //grades = res.data
        var grades = new Array
        grades[0] = { grade: 0, name: '所有会员' }
        for (var i = 1; i <= res.data.length; i++) {
          grades[i] = res.data[i - 1]
        }
        that.setData({
          grades: grades,
          gradeIndex: 0
        })
      }
    })
  },
  onReady: function () {
    // 页面渲染完成
  },
  onShow: function () {
    // 页面显示
    this.setData({
      date_start: '请选择',
      date_end: '请选择'
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
        showCancel:false
      })
      return
    }
    var grade     = e.detail.value.grade
    var condition = e.detail.value.award_condition
    var consume = e.detail.value.consume
    var coupon_id = e.detail.value.coupon_id
    var date_start = e.detail.value.date_start
    var date_end = e.detail.value.date_end
    if (!consume) {
      wx.showModal({
        title: "请填写需要消费金额",
        content: "",
        showCancel: false,
        confirmText: "确定"
      })
      return false
    }
    if ('0' == coupon_id) {
      wx.showModal({
        title: "请选择赠送优惠券",
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
    var title = this.data.grades[this.data.gradeIndex].name + '消费返券'
    wx.request({
      url: host + 'marketing.php?action=rebate',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
        grade:grade,
        title: title,
        condition: condition,
        consume: consume,
        coupon_id: coupon_id,
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
          wx.removeStorageSync('merchant_rebate_campaign')
          app.loadRebateCampaign()
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
  bindRuleChange: function (e) {
    if ('0' == e.detail.value) {
      var rebate_condition_title = '消费每满'
    } else {
      var rebate_condition_title = '消费达到'
    }
    this.setData({
      ruleIndex: e.detail.value,
      rebate_condition_title: rebate_condition_title
    })
  },
  bindTimeChange: function (e) {
    this.setData({
      time: e.detail.value
    })
  },
  bindCouponChange: function (e) {
    this.setData({
      couponIndex: e.detail.value
    })
  },
  bindGradeChange: function (e) {
    this.setData({
      gradeIndex: e.detail.value
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
  back: function () {
    wx.navigateBack({
      delta: 1
    })
  }
})
