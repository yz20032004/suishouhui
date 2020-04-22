// pages/vlog/list.js
const host = require('../../config').host
Page({

  /**
   * 页面的初始数据
   */
  data: {
    page:1
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    var mch_id = options.mch_id
    var page = options.hasOwnProperty('page') ? options.page : 1
    var width = wx.getSystemInfoSync().windowWidth
    var height = wx.getSystemInfoSync().windowHeight
    this.setData({
      width:width,
      height:height,
      page:page,
      mch_id:mch_id
    })
    this.get_current()
  },
  get_current:function(){
    var that = this
    wx.request({
      url: host + 'huipay/vlog.php?action=get_detail',
      data: {
        mch_id: that.data.mch_id,
        page:that.data.page
      },
      header: {
        'content-type': 'application/json'
      },
      success: function(res) {
        if (!res.data) {
          wx.showToast({
            title: '没有新的视频',
            icon:'none'
          })
        } else {
          var is_love = false
          var vlog_loves = wx.getStorageSync('vlog_loves')
          if (vlog_loves.indexOf(res.data.id) > -1) {
            is_love = true
          }
          var height = Math.round((that.data.width * res.data.height) / res.data.width)
          if (height < that.data.height) {
            height = that.data.height
          }
          that.setData({
            vlog: res.data,
            loves:res.data.loves,
            is_love:is_love,
            height:height
          })
        }
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
    this.get_shop()
  },
  buy:function(){
    var that = this
    wx.navigateTo({
      url: '../groupon/detail?id='+that.data.vlog.groupon_id,
    })
  },
  /**
   * 生命周期函数--监听页面隐藏
   */
  onHide: function () {
    this.setData({
      page:1
    })
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
    var page = this.data.page - 1
    this.setData({
      page:page
    })
    this.get_current()
  },
  /**
   * 页面上拉触底事件的处理函数
   */
  onReachBottom: function () {
    var page = this.data.page + 1
    this.setData({
      page:page
    })
    this.get_current()
  },
  home:function(){
    wx.switchTab({
      url: '../index/index',
    })
  },
  love:function(){
    var that = this
    if(this.data.is_love) {
      return
    }
    wx.request({
      url: host + 'huipay/vlog.php?action=love',
      data: {
        vlog_id:that.data.vlog.id
      },
      header: {
        'content-type': 'application/json'
      },
      success: function(res) {
        var vlog_loves = wx.getStorageSync('vlog_loves')
        if (!vlog_loves) {
          vlog_loves = [that.data.vlog.id]
        } else {
          vlog_loves.push(that.data.vlog.id)
        }
        wx.setStorageSync('vlog_loves', vlog_loves)
        that.setData({
          is_love:true,
          loves:parseInt(that.data.vlog.loves) + 1
        })
      }
    })
  },
  share:function(){
    this.setData({
      show_share_box:true
    })
  },
  bindended:function(){
    console.log('in')
    var page = this.data.page + 1
    this.setData({
      page:page
    })
    this.get_current()
  },
  /**
   * 用户点击右上角分享
   */
  onShareAppMessage: function () {
    var that = this
    return{
      title:that.data.vlog.detail,
      path: '/pages/vlog/list?mch_id='+that.data.mch_id+'&page='+that.data.page
    }
  },
  get_shop: function() {
    var shop = wx.getStorageSync('shop')
    if (shop && shop.mch_id == this.data.mch_id) {
      this.setData({
        shop: shop,
      })
      return
    }
    var mch_id = this.data.mch_id
    var that = this
    wx.request({
      url: host + 'shop.php?action=get_detail',
      data: {
        mch_id: mch_id
      },
      header: {
        'content-type': 'application/json'
      },
      success: function(res) {
        wx.setStorageSync('shop', res.data)
        that.setData({
          shop: res.data,
        })
      }
    })
  },
})