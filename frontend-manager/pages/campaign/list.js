// pages/campaign/list.js
var sliderWidth = 96; // 需要设置slider的宽度，用于计算中间位置
const host = require('../../config').host
Page({
  data: {
    tabs: ["执行中", "未开始", "已结束"],
    activeIndex: 0,
    sliderOffset: 0,
    sliderLeft: 0
  },
  onLoad: function () {
    var that = this;
    wx.getSystemInfo({
      success: function (res) {
        that.setData({
          sliderLeft: (res.windowWidth / that.data.tabs.length - sliderWidth) / 2,
          sliderOffset: res.windowWidth / that.data.tabs.length * that.data.activeIndex
        });
      }
    });
  },
  tabClick: function (e) {
    this.setData({
      sliderOffset: e.currentTarget.offsetLeft,
      activeIndex: e.currentTarget.id
    });
  },
  onShow: function () {
    // 页面显示
    var that = this
    wx.request({
      url: host + 'marketing.php?action=get_list',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        that.setData({
          campaignData: res.data
        })
      }
    })
  }
});
