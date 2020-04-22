// pages/marketing/send_coupon.js
const host = require('../../config').host
var app = getApp()
var date = new Date()
Page({
  data: {
    //date_start: date.toLocaleDateString(),
    brand_name: '',
    coupons: null,
    shops: null,
    grades: new Array,
    counts: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
    countIndex: 0,
    coupon_total:1,
    coupon_content:'',
    comment:'',
    smscount:0,
    sendsms:false,
    disabled:false
  },
  onLoad: function (options) {
    // 页面初始化 options为页面跳转所带来的参数
    //var grades = ''
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
        for(var i=1;i<=res.data.length;i++){
          grades[i] = res.data[i-1]
        }
        that.setData({
          grades: grades,
          gradeIndex: 0,
          display:'none',
          textcount:0,
        })
      }
    })

    wx.request({
      url: host + 'mch.php?action=get_members_total',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
        grade:0
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        that.setData({
          member_total:res.data.total,
          mobile_total:res.data.mobile_total
        })
      }
    })

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
  onReady: function () {
    // 页面渲染完成
  },
  onShow: function () {
    // 页面显示
    var myDate = new Date();
    var next_date = new Date((myDate / 1000 + 86400) * 1000)
    var end_date = new Date((myDate / 1000 + 86400*30) * 1000)
    this.setData({
      date: '请选择',
      time: '请选择',
      date_start: next_date.getFullYear()+'-'+(next_date.getMonth()+1)+'-'+next_date.getDate(),
      date_end: end_date.getFullYear() + '-' + (end_date.getMonth()+1) + '-' + end_date.getDate(),
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
    var grade = e.detail.value.grade
    var coupon_id = e.detail.value.coupon_id
    var count = this.data.counts[e.detail.value.count]
    var date = e.detail.value.senddate
    var sendtime = e.detail.value.sendtime
    var is_send_me = e.detail.value.is_send_me
    var detail = e.detail.value.detail
    var is_send_sms = e.detail.value.is_send_sms
    if ('0' == coupon_id) {
      wx.showModal({
        title: "请选择赠送优惠券",
        content: "",
        showCancel: false,
        confirmText: "确定"
      })
      return false
    }
    if ('请选择' == date) {
      wx.showModal({
        title: "请选择发送日期",
        content: "",
        showCancel: false,
        confirmText: "确定"
      })
      return false
    }
    if ('请选择' == sendtime) {
      wx.showModal({
        title: "请选择发送时间",
        content: "",
        showCancel: false,
        confirmText: "确定"
      })
      return false
    }
    var that = this
    var title = this.data.grades[this.data.gradeIndex].name + '赠送' + this.data.coupons[this.data.couponIndex].name
    wx.request({
      url: host + 'marketing.php?action=send_coupon',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
        title: title,
        grade: grade,
        coupon_id: coupon_id,
        count:count,
        date: date,
        sendtime: sendtime,
        detail: detail,
        is_send_me: is_send_me?1:0,
        is_send_sms:is_send_sms?1:0,
        brand_name:that.data.brand_name,
        comment:that.data.comment,
        coupon_name:that.data.coupon_name,
        expire_title:that.data.expire_title
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        if ('fail' == res.data) {
          wx.showModal({
            title: "已有相同活动",
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
  bindDateChange: function (e) {
    this.setData({
      date: e.detail.value
    })
  },
  bindCouponChange: function (e) {
    var that = this
    var user = wx.getStorageSync('user')
    var brand_name = user.merchant_name
    var coupon = this.data.coupons[e.detail.value]
    var comment = this.data.comment
    var coupon_name = coupon.name
    if ('hard' == coupon.validity_type) {
      var date_start = new Date(coupon.date_start)
      var dateStartTitle = (date_start.getMonth() + 1) + '月' + date_start.getDate() + '日'

      var date_end = new Date(coupon.date_end)
      var dateEndTitle = (date_end.getMonth() + 1) + '月' + date_end.getDate() + '日'

      var expireTitle = '自' + dateStartTitle + '到' + dateEndTitle
    } else {
      var expireTitle = coupon.total_days + '天'
    }
    var coupon_content = '赠您'+this.data.coupon_total+'张' + coupon_name + ',有效期' + expireTitle
    var sms_content = '【随手惠】尊敬的'+brand_name+'会员，'+comment + coupon_content + '，可进微信-我-卡包查看。回T退订'
    var textcount = sms_content.length
    var smscount = textcount <= 70 ? 1 : Math.ceil(textcount / 67)
    var sms_total = this.data.mobile_total * smscount
    var sms_balance = user.sms_total
    this.setData({
      couponIndex:e.detail.value,
      coupon_content: coupon_content,
      sms_content: coupon_content ? sms_content : '',
      comment: comment,
      textcount: textcount,
      smscount: smscount,
      sms_total: that.data.mobile_total * smscount,
      disabled: that.data.sendsms && sms_total > sms_balance ? true : false,
      sms_balance:sms_balance,
      sms_balance_tip: sms_total > sms_balance ? '余额不足' : '',
      brand_name:brand_name,
      coupon_name:coupon_name,
      expire_title:expireTitle
    })
  },
  bindGradeChange: function (e) {
    var that = this
    this.setData({
      gradeIndex: e.detail.value
    })
    wx.request({
      url: host + 'mch.php?action=get_members_total',
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
          mobile_total:res.data.mobile_total
        })
      }
    })
  },
  bindCountChange: function (e) {
    var that = this
    var coupon_total = that.data.counts[e.detail.value]
    var coupon_content = '赠您' + coupon_total + '张' + that.data.coupon_name + ',有效期' + that.data.expire_title
    var sms_content = '【随手惠】尊敬的' + that.data.brand_name + '会员，' + that.data.comment + coupon_content + '，可进微信-我-卡包查看。回T退订'
    this.setData({
      countIndex: e.detail.value,
      coupon_total:coupon_total,
      sms_content:sms_content
    })
  },
  smsSwitch: function (e) {
    var isChecked = e.detail.value
    if (isChecked) {
      this.setData({
        display: '',
        sendsms:true,
        disabled: this.data.sms_total > this.data.sms_balance ? true : false,
      })
    } else {
      this.setData({
        display: 'none',
        sendsms:false,
        disabled: false
      })
    }
  },
  set_comment:function(e){
    var comment = e.detail.value+', '
    var brand_name = this.data.brand_name
    var coupon_content = this.data.coupon_content
    var sms_content = '【随手惠】尊敬的'+brand_name+'会员，' + comment + coupon_content + '，可进微信-我-卡包查看。回T退订'
    var textcount = sms_content.length
    var smscount = textcount <= 70 ? 1 : Math.ceil(textcount / 67)
    var sms_total = this.data.mobile_total * smscount
    this.setData({
      sms_content: coupon_content?sms_content:'',
      comment:comment,
      textcount:textcount,
      smscount: smscount,
      sms_total:sms_total,
      disabled:sms_total >this.data.sms_balance?true:false,
      sms_balance_tip: sms_total > this.data.sms_balance ? '余额不足' : ''
    })
  },
  back: function () {
    wx.navigateBack({
      delta: 1
    })
  }
})
