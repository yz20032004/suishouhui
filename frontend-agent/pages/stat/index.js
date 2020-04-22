// pages/stat/index.js
const host = require('../../config').host
var sliderWidth = 96;
Page({
  data: {
    date_start: '', //默认起始时间  
    date_end: '', //默认结束时间 
    tabs: ["我的商户", "团队商户", "佣金统计"],
    activeIndex: 0,
    sliderOffset: 0,
    sliderLeft: 0,
  },
  onLoad: function(options) {
    var that = this;
    wx.getSystemInfo({
      success: function(res) {
        that.setData({
          sliderLeft: (res.windowWidth / that.data.tabs.length - sliderWidth) / 2,
          sliderOffset: res.windowWidth / that.data.tabs.length * that.data.activeIndex
        });
      }
    });
    var myDate = new Date()
    var date = myDate.getDate()
    var hour = myDate.getHours()
    var date_start = myDate.getFullYear() + '-' + (myDate.getMonth() + 1) + '-' + (date - 1)
    this.setData({
      date_start: date_start,
      date_end: date_start,
      select_date_start:date_start,
      select_date_end:date_start
    })
    this.getDateStatMyShop()
    this.getDateStatTeamShop()
    this.getDateStatRevenue()
  },
  tabClick: function(e) {
    this.setData({
      sliderOffset: e.currentTarget.offsetLeft,
      activeIndex: e.currentTarget.id
    });
  },
  onReady: function() {
    // 页面渲染完成
  },
  onShow: function() {
    var user = wx.getStorageSync('user')
    this.setData({
      user:user
    })
    // 页面显示
  },
  onHide: function() {
    // 页面隐藏
    this.onLoad()
  },
  onUnload: function() {
    // 页面关闭
  },
  getDateStatMyShop: function() {
    var that = this
    wx.request({
      url: host + 'tt_stat.php?action=stat_date_myshop',
      data: {
        uid: wx.getStorageSync('uid'),
        date_start: that.data.date_start,
        date_end: that.data.date_end
      },
      header: {
        'content-type': 'application/json'
      },
      success: function(res) {
        if (res.data.consumes) {
          var rate = res.data.member_consumes / res.data.consumes * 100
          var member_consume_rate = rate.toFixed(0) + '%'
        } else {
          var member_consume_rate = '-'
        }
        var discount_total = res.data.save / res.data.trade_amount * 100
        that.setData({
          statData: res.data,
          member_consume_rate: member_consume_rate,
          discount_total: discount_total.toFixed(0) + '%'
        })
      }
    })
  },
  getDateStatTeamShop: function() {
    var that = this
    wx.request({
      url: host + 'tt_stat.php?action=stat_date_teamshop',
      data: {
        uid: wx.getStorageSync('uid'),
        date_start: that.data.date_start,
        date_end: that.data.date_end
      },
      header: {
        'content-type': 'application/json'
      },
      success: function(res) {
        if (res.data.consumes) {
          var rate = res.data.member_consumes / res.data.consumes * 100
          var member_consume_rate = rate.toFixed(0) + '%'
        } else {
          var member_consume_rate = '-'
        }
        var discount_total = res.data.save / res.data.trade_amount * 100
        that.setData({
          teamStatData: res.data,
          team_member_consume_rate: member_consume_rate,
          dteam_iscount_total: discount_total.toFixed(0) + '%'
        })
      }
    })
  },

  getDateStatRevenue: function() {
    var that = this
    wx.request({
      url: host + 'tt_stat.php?action=stat_date_revenue',
      data: {
        uid: wx.getStorageSync('uid'),
        date_start: that.data.date_start,
        date_end: that.data.date_end
      },
      header: {
        'content-type': 'application/json'
      },
      success: function(res) {
        that.setData({
          revenueData: res.data
        })
      }
    })
  },
  bindDateStartChange: function(e) {
    this.setData({
      date_start: e.detail.value
    })
    this.getDateStatMyShop()
    this.getDateStatTeamShop()
    this.getDateStatRevenue()
  },
  bindDateEndChange: function(e) {
    this.setData({
      date_end: e.detail.value
    })
    this.getDateStatMyShop()
    this.getDateStatTeamShop()
    this.getDateStatRevenue()
  }
})