// pages/buy/sold_list.js
const host = require('../../config').host
Page({

  /**
   * 页面的初始数据
   */
  data: {
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
    var groupon_id = options.groupon_id
    var myDate = new Date()
    var date = myDate.getDate()
    var hour = myDate.getHours()
    var date_start = myDate.getFullYear() + '-' + myDate.getMonth() + '-' + date
    var date_end = myDate.getFullYear() + '-' + (myDate.getMonth()+1) + '-' + date
    this.setData({
      date_start: date_start,
      date_end: date_end,
      groupon_id:groupon_id
    })
    this.get_list()
  },
  previewMember: function (e) {
    var openid = e.currentTarget.dataset.openid
    wx.navigateTo({ url: '../member/detail?openid=' + openid })
  },
  get_list:function(){
    var that = this
    wx.request({
      url: host + 'groupon.php?action=get_sold_list',
      data: {
        groupon_id: that.data.groupon_id,
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
          soldData: res.data.list
        })
      }
    })
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
        url: host + 'groupon.php?action=get_sold_list',
        data: {
          groupon_id: that.data.groupon_id,
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
            soldData: that.data.soldData.concat(res.data.list)
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