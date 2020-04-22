// pages/marketing/send_coupon.js
const host = require('../../config').host
var app = getApp()
var date = new Date()
Page({
  data: {
    //date_start: date.toLocaleDateString(),
    brand_name: '',
    coupons: null,
    wakeupIndex:0,
    wakeups:[
      {month:1,title:'一个月未消费'},
      {month:2,title:'两个月未消费'},
      {month:3,title:'三个月未消费'},
      {month:4,title:'四个月未消费'},
      { month: 5, title: '五个月未消费' },
      { month: 6, title: '六个月未消费' },
    ],
    counts: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
    countIndex: 0,
    coupon_total: 1,
    coupon_content: '',
    comment: '',
    smscount: 0,
    display:'none',
    sendsms: false,
    disabled: false
  },
  onLoad: function (options) {
    var that = this
    // 页面初始化 options为页面跳转所带来的参数
    wx.request({
      url: host + 'ssh_coupon.php?action=get_list',
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
        console.log(ret['enable'])
        console.log(ret['enable'].length - 1)
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
    var that = this
    wx.request({
      url: host + 'ssh_marketing.php?action=get_wakeups',
      data: {
        mch_id: wx.getStorageSync('mch_id')
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        that.setData({
          time: '请选择',
          wakeup_data:res.data
        })
      }
    })
  },
  onHide: function () {
    // 页面隐藏
  },
  onUnload: function () {
    // 页面关闭
  },
  submit: function (e) {
    var wakeup_day = e.detail.value.month * 30
    var coupon_id = e.detail.value.coupon_id
    var count = this.data.counts[e.detail.value.count]
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
    var that = this
    var title = that.data.wakeups[that.data.wakeupIndex].title+'赠送'+that.data.coupon_name
    wx.request({
      url: host + 'ssh_marketing.php?action=add_wakeup',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
        title: title,
        wakeup_day:wakeup_day,
        coupon_id: coupon_id,
        count: count,
        detail: detail,
        is_send_me: is_send_me ? 1 : 0,
        is_send_sms: is_send_sms ? 1 : 0,
        brand_name: that.data.brand_name,
        coupon_name: that.data.coupon_name
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
              that.onShow()
            }
          })
        }
      }
    })
  },
  bindCouponChange: function (e) {
    var that = this
    var user = wx.getStorageSync('user')
    var brand_name = wx.getStorageSync('merchant_name')
    var coupon = this.data.coupons[e.detail.value]
    var coupon_name = coupon.name
    var coupon_content = '赠您' + this.data.coupon_total + '张' + coupon_name
    var sms_content = '【随手惠】尊敬的' + brand_name + '会员，' + coupon_content + '，可进微信-我-卡包查看。回T退订'
    var textcount = sms_content.length
    var smscount = textcount <= 70 ? 1 : Math.ceil(textcount / 67)
    this.setData({
      couponIndex: e.detail.value,
      coupon_content: coupon_content,
      sms_content: coupon_content ? sms_content : '',
      brand_name: brand_name,
      coupon_name: coupon_name,
      textcount:textcount,
      smscount:smscount
    })
  },
  bindWakeupChange: function (e) {
    var that = this
    this.setData({
      wakeupIndex: e.detail.value
    })
  },
  bindCountChange: function (e) {
    var that = this
    var coupon_total = that.data.counts[e.detail.value]
    var coupon_content = '赠您' + coupon_total + '张' + that.data.coupon_name 
    var sms_content = '【随手惠】尊敬的' + that.data.brand_name + '会员，' + coupon_content + '，可进微信-我-卡包查看。回T退订'
    this.setData({
      countIndex: e.detail.value,
      coupon_total: coupon_total,
      sms_content: sms_content
    })
  },
  smsSwitch: function (e) {
    var isChecked = e.detail.value
    if (isChecked) {
      this.setData({
        display: '',
        sendsms: true,
        disabled: this.data.sms_total > this.data.sms_balance ? true : false,
      })
    } else {
      this.setData({
        display: 'none',
        sendsms: false,
        disabled: false
      })
    }
  },
  open_detail:function(e){
    var id = e.currentTarget.dataset.id
    wx.navigateTo({
      url: '../campaign/detail?id=' + id,
    })
  },
  back: function () {
    wx.navigateBack({
      delta: 1
    })
  }
})
