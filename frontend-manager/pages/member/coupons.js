var sliderWidth = 96; // 需要设置slider的宽度，用于计算中间位置
var coupons = null
const host = require('../../config').host
Page({
  data: {
    tabs: ["可用券", "未生效券", "过期券", "已使用"],
    activeIndex: 0,
    sliderOffset: 0,
    sliderLeft: 0,
  },
  onLoad: function (options) {
    var openid = options.openid
    var that = this
    wx.getSystemInfo({
      success: function (res) {
        that.setData({
          sliderLeft: (res.windowWidth / that.data.tabs.length - sliderWidth) / 2,
          sliderOffset: res.windowWidth / that.data.tabs.length * that.data.activeIndex,
        });
      }
    });
  },
  get_list:function(){
    var that = this
    var member = wx.getStorageSync('current_search_member')
    var openid = member.sub_openid
    wx.request({
      url: host + 'member.php?action=get_coupons',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
        openid: openid
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        that.setData({
          coupons: res.data
        })
      }
    })
  },
  consume:function(e){
    var code = e.currentTarget.dataset.code
    wx.request({
      url: host + 'coupon.php?action=get_code_detail',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
        code: code
      },
      header: {
        'content-type': 'application/json'
      },
      success: function(res) {
        if (res.data.id) {
          wx.navigateTo({
            url: '../coupon/consume?params=' + JSON.stringify(res.data)
          })
        } else {
          wx.showModal({
            title: "优惠券号码不存在",
            content: "",
            showCancel: false,
            confirmText: "确定"
          })
          return false
        }
      }
    })
  },
  onShow: function () {
    this.get_list()
  },
  tabClick: function (e) {
    this.setData({
      sliderOffset: e.currentTarget.offsetLeft,
      activeIndex: e.currentTarget.id
    });
  },
  adjust_coupon: function (e) {
    wx.navigateTo({
      url: 'coupon_adjust',
    })
  },
  validate: function (e) {
    wx.request({
      url: host + 'coupon.php?action=get_code_detail',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
        code: code
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        if ('0' == res.data.errcode) {
          wx.navigateTo({
            url: '../coupon/consume?params=' + JSON.stringify(res.data)
          })
        }
      }
    })
  },
  back: function () {
    wx.navigateBack({
      delta: 1
    })
  }
});
