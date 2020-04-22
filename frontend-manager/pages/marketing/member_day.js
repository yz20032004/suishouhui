// pages/marketing/member_day.js
const host = require('../../config').host
Page({

  /**
   * 页面的初始数据
   */
  data: {
    campaigns: [{
        type: '0',
        title: "请选择"
      },
      {
        type: 'point',
        title: "积分加速"
      },
      {
        type: 'reduce',
        title: "消费立减"
      },
      {
        type: 'discount',
        title: "消费折扣"
      },
      {
        type: 'rebate',
        title: "消费返券"
      },
    ],
    campaignIndex: 0,
    days: [{
        day: 0,
        title: '请选择'
      },
      {
        day: 1,
        title: '每周一'
      },
      {
        day: 2,
        title: '每周二'
      },
      {
        day: 3,
        title: '每周三'
      },
      {
        day: 4,
        title: '每周四'
      },
      {
        day: 5,
        title: '每周五'
      },
      {
        day: 6,
        title: '每周六'
      },
      {
        day: 7,
        title: '每周日'
      }
    ],
    dayIndex: 0,
    point_speed_range: [
      { speed: '1', title: "请选择" },
      { speed: '1.1', title: "1.1倍" },
      { speed: '1.2', title: "1.2倍" },
      { speed: '1.5', title: "1.5倍" },
      { speed: '2', title: "2倍" },
      { speed: '2.5', title: "2.5倍" },
      { speed: '3', title: "3倍" },
      { speed: '3.5', title: "3.5倍" },
      { speed: '4', title: "4倍" },
      { speed: '4.5', title: "4.5倍" },
      { speed: '5', title: "5倍" },
      { speed: '6', title: "6倍" },
      { speed: '7', title: "7倍" },
      { speed: '8', title: "8倍" },
      { speed: '9', title: "9倍" },
      { speed: '10', title: "10倍" },
    ],
    speedIndex: 0,
    counts: [1, 2, 3, 4, 5],
    countIndex: 0,
    limit:'1张'
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function(options) {

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
    var that = this
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
  submit: function (e) {
    if ('admin' != wx.getStorageSync('user_role')) {
      wx.showModal({
        title: '温馨提示',
        content: '体验者或非管理员身份不可执行此操作',
        showCancel: false
      })
      return
    }
    var day = e.detail.value.day
    var campaign = e.detail.value.campaign
    var point_speed = 'point_speed' in e.detail.value ? e.detail.value.point_speed : 1 
    var consume = 'consume' in e.detail.value ? e.detail.value.consume : 0
    var reduce  = 'reduce' in e.detail.value ? e.detail.value.reduce : 0
    var reduce_max = 'reduce_max' in e.detail.value ? e.detail.value.reduce_max : 0
    var discount   = 'discount' in e.detail.value ? e.detail.value.discount : 0
    var coupon_id = 'coupon_id' in e.detail.value ? e.detail.value.coupon_id : 0
    var coupon_total = 'coupon_total' in e.detail.value ? this.data.counts[e.detail.value.coupon_total] : 0
    if ('0' == day) {
      wx.showModal({
        title: "请设置会员日",
        content: "",
        showCancel: false,
        confirmText: "确定"
      })
      return false
    }
    if ('0' == campaign) {
      wx.showModal({
        title: "请设置优惠活动",
        content: "",
        showCancel: false,
        confirmText: "确定"
      })
      return false
    }
    if ('point' == campaign) {
      if ('1' == point_speed) {
        wx.showModal({
          title: "请设置积分加速",
          content: "",
          showCancel: false,
          confirmText: "确定"
        })
        return false
      }
    } else if ('reduce' == campaign) {
      if (!consume) {
        wx.showModal({
          title: "请填写需要消费金额",
          content: "",
          showCancel: false,
          confirmText: "确定"
        })
        return false
      }
      if (!reduce) {
        wx.showModal({
          title: "请填写立减金额",
          content: "",
          showCancel: false,
          confirmText: "确定"
        })
        return false
      }
    } else if ('discount' == campaign) {
      if (!discount) {
        wx.showModal({
          title: "请填写折扣",
          content: "",
          showCancel: false,
          confirmText: "确定"
        })
        return false
      }
      if (discount > 9.9 || discount < 1) {
        wx.showModal({
          title: "折扣只能在1~9.9之间",
          content: "",
          showCancel: false,
          confirmText: "确定"
        })
        return false
      }
    } else if ('rebate' == campaign) {
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
    }
    var title = this.data.days[this.data.dayIndex].title+'会员日'
    wx.request({
      url: host + 'marketing.php?action=member_day',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
        title: title,
        day:day,
        campaign:campaign,
        point_speed:point_speed,
        consume:consume,
        reduce:reduce,
        reduce_max:reduce_max,
        discount:discount,
        coupon_id:coupon_id,
        coupon_total:coupon_total
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        if ('fail' == res.data) {
          wx.showModal({
            title: '创建失败',
            content: '已有会员日活动',
            showCancel:false
          })
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
  bindCampaignChange: function(e) {
    var campaigns = this.data.campaigns
    this.setData({
      campaignIndex: e.detail.value,
      campaignType: campaigns[e.detail.value].type
    })
  },
  bindMemberDayChange: function(e) {
    this.setData({
      dayIndex: e.detail.value
    })
  },
  bindPointSpeedChange: function (e) {
    this.setData({
      speedIndex: e.detail.value
    })
  },
  bindCouponChange: function (e) {
    this.setData({
      couponIndex: e.detail.value
    })
  },
  bindCountChange: function (e) {
    var that = this
    this.setData({
      countIndex: e.detail.value,
      limit: that.data.counts[e.detail.value] + '张'
    })
  },
  back: function () {
    wx.navigateBack({
      delta: 1
    })
  }
})