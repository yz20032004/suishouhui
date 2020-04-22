// pages/member/index.js
const host = require('../../config').host
Page({
  data: {
    editIndex:0,
    member_edits: ["赠送优惠券","核销优惠券","调整积分", "调整等级"]
  },
  onLoad: function (options) {
    var openid = options.openid
    this.setData({
      openid:openid
    })
  },
  onReady: function () {
    // 页面渲染完成
  },
  onShow: function () {
    // 页面显示
    var that = this
    wx.request({
      url: host + 'member.php?action=get_info',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
        openid: that.data.openid
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        wx.setStorageSync('current_search_member', res.data)
        that.setData({
          member: res.data,
          user: wx.getStorageSync('user')
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
  coupon:function(e){
    var openid = e.currentTarget.dataset.openid
    wx.navigateTo({
      url: 'coupons?openid='+openid,
    })
  },
  consumes:function(e){
    var openid = e.currentTarget.dataset.openid
    wx.navigateTo({
      url: 'consumes?openid='+openid,
    })
  },
  bindEditChange: function (e) {
    if ('0' == e.detail.value) {
      wx.navigateTo({
        url: 'adjust_coupon?openid='+this.data.member.sub_openid+'&name='+this.data.member.name,
      })
    } else if ('1' == e.detail.value) {
      wx.navigateTo({
        url: 'coupons?openid='+this.data.member.sub_openid,
      })
    } else if ('2' == e.detail.value) {
      wx.navigateTo({
        url: 'adjust_point?openid=' + this.data.member.sub_openid + '&name=' + this.data.member.name
      })
    } else if ('3' == e.detail.value) {
      wx.navigateTo({
        url: 'adjust_grade?openid=' + this.data.member.sub_openid + '&grade=' + this.data.member.grade
      })
    }
  },
  back: function () {
    wx.navigateBack({
      delta: 1
    })
  }
})
