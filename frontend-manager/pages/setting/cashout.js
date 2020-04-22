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
  },
  onLoad: function (options) {
    // 页面初始化 options为页面跳转所带来的参数
    var myDate = new Date()
    var date = myDate.getDate()
    var hour = myDate.getHours()
    var date_start = myDate.getFullYear() + '-' + myDate.getMonth() + '-' + date
    var date_end = myDate.getFullYear() + '-' + (myDate.getMonth() + 1) + '-' + date

    this.setData({
      date_start: date_start,
      date_end: date_end
    })
    this.get_revenue_today()
    this.get_list()
  },
  onReady: function () {
    // 页面渲染完成
  },
  get_revenue_today: function () {
    var that = this
    wx.request({
      url: host + 'mch.php?action=get_revenue_today',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        that.setData({
          cashout: res.data,
        })
      }
    })
  },
  get_list: function () {
    var that = this
    wx.request({
      url: host + 'mch.php?action=get_cash_out_history',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
        date_start: that.data.date_start,
        date_end: that.data.date_end,
        page: that.data.page,
        page_count: that.data.pageCount
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
        url: host + 'mch.php?action=get_cash_out_list',
        data: {
          mch_id: wx.getStorageSync('mch_id'),
          date_start: that.data.date_start,
          date_end: that.data.date_end,
          page_count: that.data.pageCount,
          page: page
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
  onShow: function () {
  },
  onHide: function () {
    // 页面隐藏
  },
  onUnload: function () {
    // 页面关闭
  },
  cashout: function () {
    var that = this
    var wait_cash_out = parseFloat(this.data.cashout.wait_cash_out)
    if (wait_cash_out < 1) {
      wx.showModal({
        title: '提现失败',
        content: '提现金额需大于1元',
        showCancel:false
      })
      return
    } else if (wait_cash_out > 5000) {
      wx.showModal({
        title: '提现失败',
        content: '提现金额不能超过5000元',
        showCancel: false
      })
      return
    }
    var merchant = wx.getStorageSync('mch')
    var groupon_fee_rate = parseFloat(merchant.groupon_fee_rate)
    var groupon_fee_rate_percent = (groupon_fee_rate * 100) + '%'
    var user = wx.getStorageSync('user')
    var service_fee = wait_cash_out * groupon_fee_rate
    var revenue = wait_cash_out - service_fee
    wx.showModal({
      title: '提现须知',
      content: "每笔和每天的提现上限为5000元,提现将收取"+groupon_fee_rate_percent+"的手续费,该笔提现手续费为"+service_fee+"元，实际到账金额为"+revenue+"元",
      showCancel: true,
      success(res){
        if (res.confirm) {
          wx.request({
            url: host + 'mch.php?action=cashout',
            data: {
              mch_id: wx.getStorageSync('mch_id'),
              cashout:wait_cash_out,
              openid: wx.getStorageSync('openid'),
              username:user.name
            },
            success: function (res) {
              if ('success' == res.data.result) {
                wx.showModal({
                  title: '已发起提现请求',
                  content: '提现金额将直接到'+user.name+'的微信零钱',
                  showCancel: false,
                  success(res) {
                    that.onLoad()
                  }
                })
              } else {
                wx.showModal({
                  title: '提现失败',
                  content: res.data.message,
                  showCancel: false,
                  success(res) {
                    that.onLoad()
                  }
                })
              }
            },
            fail: function (res) {
              wx.hideLoading()
            }
          })
        }
      }
    })
  }
})