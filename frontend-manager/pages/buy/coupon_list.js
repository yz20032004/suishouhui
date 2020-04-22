// pages/buy/coupon_list.js
const host = require('../../config').host
Page({

  /**
   * 页面的初始数据
   */
  data: {
    get_type:'buy',
    page: 1,
    // 总页数
    totalPage: 0,
    pageCount: 20,
    date_start: '',//默认起始时间  
    date_end: '',//默认结束时间 
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    var coupon_id = options.coupon_id
    var type = options.type
    var get_type = options.hasOwnProperty('get_type') ? options.get_type : 'buy'

    var myDate = new Date()
    var date = myDate.getDate()
    var hour = myDate.getHours()
    var date_start = myDate.getFullYear() + '-' + myDate.getMonth() + '-' + date
    var date_end = myDate.getFullYear() + '-' + (myDate.getMonth() + 1) + '-' + date
    this.setData({
      date_start: date_start,
      date_end: date_end,
      coupon_id: coupon_id,
      type:type,
      get_type:get_type
    })
    this.get_list()

  },
  get_list:function(){
    var that = this
    wx.request({
      url: host + 'groupon.php?action=get_coupon_list',
      data: {
        coupon_id: that.data.coupon_id,
        type: that.data.type,
        get_type:that.data.get_type,
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
          couponData: res.data.list
        })
      }
    })
  },
  previewMember: function (e) {
    var openid = e.currentTarget.dataset.openid
    wx.navigateTo({ url: '../member/detail?openid=' + openid })
  },

  /**
   * 生命周期函数--监听页面初次渲染完成
   */
  onReady: function () {

  },

  /**
   * 生命周期函数--监听页面显示
   */
  onShow: function () {

  },

  /**
   * 生命周期函数--监听页面隐藏
   */
  onHide: function () {

  },

  /**
   * 生命周期函数--监听页面卸载
   */
  onUnload: function () {

  },

  /**
   * 页面相关事件处理函数--监听用户下拉动作
   */
  onPullDownRefresh: function () {

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
        url: host + 'groupon.php?action=get_coupon_list',
        data: {
          coupon_id: that.data.coupon_id,
          type: that.data.type,
          date_start: that.data.date_start,
          date_end: that.data.date_end,
          page: page,
          page_count: that.data.pageCount
        },
        success: function (res) {
          wx.hideLoading()
          wx.stopPullDownRefresh()
          // 将新获取的数据 res.data.list，concat到前台显示的showlist中即可。
          that.setData({
            couponData: that.data.couponData.concat(res.data.list)
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
  }
})