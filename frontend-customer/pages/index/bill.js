// pages/index/bill.js
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
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
  },
  onShow:function(){
    var that = this
    wx.request({
      url: host + 'huipay/user.php?action=get_trade_list',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
        openid: wx.getStorageSync('openid'),
        page: that.data.page,
        page_count: that.data.pageCount
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        that.setData({
          list: res.data.list,
          totalPage: res.data.page_total
        })
      }
    })
  },
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
        url: host + 'huipay/user.php?action=get_trade_list',
        data: {
          mch_id: wx.getStorageSync('mch_id'),
          openid: wx.getStorageSync('openid'),
          page_count: that.data.pageCount,
          page: page
        },
        success: function (res) {
          wx.hideLoading()
          wx.stopPullDownRefresh()
          // 将新获取的数据 res.data.list，concat到前台显示的showlist中即可。
          that.setData({
            list: that.data.list.concat(res.data.list)
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
  waimai_detail:function(e){
    var out_trade_no =  e.currentTarget.dataset.id
    wx.navigateTo({
      url: '../waimai/detail?out_trade_no='+out_trade_no,
    })
  },
  mall_detail:function(e){
    var out_trade_no =  e.currentTarget.dataset.id
    wx.navigateTo({
      url: '../vip/mall_detail?out_trade_no='+out_trade_no,
    })
  },
  ordering_detail:function(e){
    var out_trade_no =  e.currentTarget.dataset.id
    wx.navigateTo({
      url: '../order/detail?out_trade_no='+out_trade_no,
    })
  },
  /**
   * 生命周期函数--监听页面初次渲染完成
   */
  onReady: function () {

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

  }
})
