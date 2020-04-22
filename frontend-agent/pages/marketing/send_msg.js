// pages/marketing/send_msg.js
const host = require('../../config').host
Page({
  data: {
    date: "2016-09-01",
    time: "12:01",
    grades: null,
    textcount:0,
    smscount: 0,
    disabled: false,
    sms_balance_tip:''
  },
  onLoad: function (options) {
    // 页面初始化 options为页面跳转所带来的参数
    var myDate = new Date()
    var date = myDate.getDate()
    var hour = myDate.getHours()
    if (hour > 18) {
      date = date + 2
    } else {
      date = date + 1
    }
    var date_start = myDate.getFullYear() + '-' + (myDate.getMonth() + 1) + '-' + date

    var grades = ''
    var that = this
    wx.request({
      url: host + 'ssh_mch.php?action=get_grades',
      data: {
        mch_id: wx.getStorageSync('mch_id')
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        var grades = new Array
        grades[0] = { grade: 0, name: '所有会员' }
        for (var i = 1; i <= res.data.length; i++) {
          grades[i] = res.data[i - 1]
        }
        that.setData({
          grades: grades,
          gradeIndex: 0,
          date_start:date_start
        })
      }
    })


    wx.request({
      url: host + 'ssh_mch.php?action=get_members_total',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
        grade: 0
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        that.setData({
          member_total: res.data.total,
          mobile_total: res.data.mobile_total
        })
      }
    })
  },
  onReady: function () {
    // 页面渲染完成
  },
  onShow: function () {
    // 页面显示
    var myDate = new Date();
    this.setData({
      date: '请选择',
      time: '请选择',
      date_start: myDate.getDate,
      date_end: (myDate.getFullYear() + 1) + '-' + myDate.getMonth() + '-' + myDate.getDate()
    })
  },
  onHide: function () {
    // 页面隐藏
  },
  onUnload: function () {
    // 页面关闭
  },
  bindDateChange: function (e) {
    this.setData({
      date: e.detail.value
    })
  },
  bindTimeChange: function (e) {
    this.setData({
      time: e.detail.value
    })
  },
  submit: function (e) {
    var grade = e.detail.value.grade
    var date = e.detail.value.senddate
    var sendtime = e.detail.value.sendtime
    var detail = e.detail.value.detail
    if ('请选择' == date) {
      wx.showModal({
        title: "请选择短信发送日期",
        content: "",
        showCancel: false,
        confirmText: "确定"
      })
      return false
    }
    if ('请选择' == sendtime) {
      wx.showModal({
        title: "请选择短信发送时间",
        content: "",
        showCancel: false,
        confirmText: "确定"
      })
      return false
    }
    if (!detail) {
      wx.showModal({
        title: "请填写短信内容",
        content: "",
        showCancel: false,
        confirmText: "确定"
      })
      return false
    }
    var title = '群发短信给'+this.data.grades[this.data.gradeIndex].name
    wx.request({
      url: host + 'marketing.php?action=send_msg',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
        grade: grade,
        date: date,
        sendtime: sendtime,
        title:title,
        detail: detail
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        wx.showToast({
          title: '创建成功',
          icon: 'success',
          duration: 2000,
          success: function () {
            wx.navigateBack({
              delta: 1
            })
          }
        })
      }
    })
  },
  bindGradeChange: function (e) {
    var that = this
    this.setData({
      gradeIndex: e.detail.value
    })
    wx.request({
      url: host + 'ssh_mch.php?action=get_members_total',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
        grade: e.detail.value
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        that.setData({
          member_total: res.data.total,
          mobile_total: res.data.mobile_total
        })
      }
    })
  },
  set_sms: function (e) {
    var sms_content = e.detail.value + '回T退订'
    var textcount = sms_content.length
    var smscount = textcount <= 70 ? 1 : Math.ceil(textcount / 67)
    var sms_total = this.data.mobile_total * smscount
    var user = wx.getStorageSync('user')
    var sms_balance = user.sms_total
    this.setData({
      textcount: textcount,
      smscount: smscount,
      sms_total: sms_total,
      disabled: sms_total > sms_balance ? true : false,
      sms_balance_tip: sms_total > sms_balance ? '余额不足' : ''
    })
  },
  back: function () {
    wx.navigateBack({
      delta: 1
    })
  }
})
