// pages/trade/list.js
const host = require('../../config').host
Page({
  data: {
    page: 1,
    // 总页数
    totalPage: 0,
    pageCount: 20,
    date_start: '',//默认起始时间  
    date_end: '',//默认结束时间 
    shopIndex: 0,
    shopId: 0
  },
  onLoad: function (options) {
    // 页面初始化 options为页面跳转所带来的参数
    var myDate = new Date()
    var date = myDate.getDate()
    var hour = myDate.getHours()
    var date_start = myDate.getFullYear() + '-' + (myDate.getMonth() + 1) + '-' + date
    this.setData({
      date_start: date_start,
      date_end: date_start
    })
    this.getShops()
    this.get_list()
  },
  onReady: function () {

    // 页面渲染完成
  },
  getShops: function () {
    var that = this
    wx.request({
      url: host + 'tt_shop.php?action=get_list',
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
  },
  get_list: function () {
    var that = this
    wx.request({
      url: host + 'ssh_trade.php?action=get_list',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
        date_start: that.data.date_start,
        date_end: that.data.date_end,
        page: that.data.page,
        page_count: that.data.pageCount,
        shop_id:that.data.shopId
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        that.setData({
          totalPage: res.data.page_total,
          tradeData: res.data.list
        })
      }
    })
  },
  /**
 * 页面上拉触底事件的处理函数
 */
  onReachBottom: function () {
    var that = this;
    // 当前页+1
    var page = that.data.page + 1;
    if (page <= that.data.totalPage) {
      wx.showLoading({
        title: '加载中',
      })
      // 请求后台，获取下一页的数据。
      wx.request({
        url: host + 'ssh_trade.php?action=get_list',
        data: {
          mch_id: wx.getStorageSync('mch_id'),
          date_start: that.data.date_start,
          date_end: that.data.date_end,
          page_count: that.data.pageCount,
          page: page,
          shop_id:that.data.shopId
        },
        success: function (res) {
          wx.hideLoading()
          wx.stopPullDownRefresh()
          // 将新获取的数据 res.data.list，concat到前台显示的showlist中即可。
          that.setData({
            tradeData: that.data.tradeData.concat(res.data.list)
          })
        },
        fail: function (res) {
          wx.hideLoading()
        }
      })
    } else {
      var page = that.data.page;
    }
    that.setData({
      page: page,
    })
  },
  bindDateStartChange: function (e) {
    this.setData({
      page: 1,
      date_start: e.detail.value
    })
    this.get_list()
  },
  bindDateEndChange: function (e) {
    this.setData({
      page: 1,
      date_end: e.detail.value
    })
    this.get_list()
  },
  bindShopChange: function (e) {
    var that = this
    this.setData({
      shopIndex: e.detail.value,
      shopId: that.data.shops[e.detail.value].id,
      page: 1
    })
    this.get_list()
  },
  onShow: function () {
  },
  onHide: function () {
    // 页面隐藏
  },
  onUnload: function () {
    // 页面关闭
  }
})
