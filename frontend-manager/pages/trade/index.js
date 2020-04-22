// pages/stat/trade.js
const host = require('../../config').host
var sliderWidth = 96;
Page({
  data: {
    date_start: '',//默认起始时间  
    date_end: '',//默认结束时间 
    tabs: ["收款统计", "会员统计", "团购统计"],
    activeIndex: 0,
    sliderOffset: 0,
    sliderLeft: 0,
    page: 1,
    // 总页数
    totalPage: null,
    pageCount: 20,
    shopIndex: 0,
    shopId: 0
  },
  onLoad: function (options) {
    var that = this;
    var merchant = wx.getStorageSync('mch')
    wx.getSystemInfo({
      success: function (res) {
        that.setData({
          sliderLeft: (res.windowWidth / that.data.tabs.length - sliderWidth) / 2,
          sliderOffset: res.windowWidth / that.data.tabs.length * that.data.activeIndex
        });
      }
    });
    var myDate = new Date()
    var date = myDate.getDate()
    var hour = myDate.getHours()
    var date_start = myDate.getFullYear() + '-' + (myDate.getMonth() + 1) + '-' + date
    this.setData({
      marketing_type:merchant.marketing_type,
      date_start: date_start,
      date_end: date_start
    })
    this.getShops()
  },
  tabClick: function (e) {
    this.setData({
      sliderOffset: e.currentTarget.offsetLeft,
      activeIndex: e.currentTarget.id
    });
  },
  onReady: function () {
    // 页面渲染完成
  },
  onShow: function () {
    // 页面显示
    this.getDateStat()
  },
  onHide: function () {
    // 页面隐藏
  },
  onUnload: function () {
    // 页面关闭
  },
  getDateStat: function () {
    var that = this
    wx.request({
      url: host + 'stat.php?action=get_date',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
        date_start:that.data.date_start,
        date_end:that.data.date_end,
        shop_id:that.data.shopId
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        if (res.data.consumes > 0) {
          var rate = res.data.member_consumes / res.data.consumes * 100
          var member_consume_rate = rate.toFixed(0) + '%'
        } else {
          var member_consume_rate = '-'
        }
        if (res.data.trade_amount > 0) {
          var discount_total = res.data.save / res.data.trade_amount * 100
          discount_total = discount_total.toFixed(0) + '%'
        } else {
          var discount_total = '-'
        }
        that.setData({
          statData: res.data,
          grouponData:res.data.groupon,
          member_consume_rate: member_consume_rate,
          discount_total: discount_total
        })
      }
    })
  },
  getCouponUsed: function () {
    var that = this
    wx.request({
      url: host + 'coupon.php?action=get_used_list',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
        date_start: that.data.date_start,
        date_end: that.data.date_end,
        page_count: that.data.pageCount,
        page: that.data.page,
        shop_id: that.data.shopId
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        that.setData({
          usedData: res.data.list,
          totalPage: res.data.page_total,
          total: res.data.total
        })
      }
    })
  },
  bindDateStartChange: function (e) {
    this.setData({
      date_start: e.detail.value
    })
    this.getDateStat()
  },
  bindDateEndChange: function (e) {
    this.setData({
      date_end: e.detail.value
    })
    this.getDateStat()
  },
  previewMember: function (e) {
    var openid = e.currentTarget.dataset.openid
    wx.navigateTo({ url: '../member/detail?openid=' + openid })
  },
  bindShopChange: function (e) {
    var that = this
    this.setData({
      shopIndex: e.detail.value,
      shopId: that.data.shops[e.detail.value].id,
      page: 1
    })
    this.getDateStat()
    this.getCouponUsed()
  },
  getShops: function () {
    var that = this
    wx.request({
      url: host + 'shop.php?action=get_list',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        var length = res.data.length
        res.data[length] = { id: 0, branch_name: '所有门店' }
        that.setData({
          shops: res.data,
          shopIndex: res.data.length - 1
        })
      }
    })
  }
})
