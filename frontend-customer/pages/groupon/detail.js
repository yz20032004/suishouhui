// pages/groupon/detail.js
const host = require('../../config').host
Page({

  /**
   * 页面的初始数据
   */
  data: {
    buy_disabled:false,
    buy_title:'立即购买',
    show_share_box:false
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    wx.showLoading({
      title: '加载中',
    })
    var id = options.id
    var that = this
    wx.request({
      url: host + 'huipay/groupon.php?action=get_detail',
      data: {
        id:id
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        var now = new Date()
        var date_start = new Date(res.data.date_start)
        var date_end   = new Date(res.data.date_end)
        var dateTime = date_end.setDate(date_end.getDate() + 1);
        date_end = new Date(dateTime)
        if (now < date_start) {
          var buy_disable = true
          var buy_title = '活动未开始'
        } else if (now > date_end || '1' == res.data.is_stop) {
          var buy_disable = true
          var buy_title = '活动已结束'
        } else {
          buy_disable = false
          buy_title = '立即购买'
        }
        if (parseInt(res.data.total_limit) - parseInt(res.data.sold) <= 0) {
          buy_disable = true
          buy_title = '已售完'
        }
        var member = wx.getStorageSync('member')
        wx.setNavigationBarTitle({
          title: res.data.shop.business_name
        })
        that.setData({
          groupon:res.data,
          shop:res.data.shop,
          buy_disable:buy_disable,
          buy_title:buy_title,
          member:member,
          balance:res.data.total_limit - res.data.sold > 0 ? res.data.total_limit - res.data.sold : 0
        })
      }
    })
  },
  buy:function(){
    if (this.data.buy_disable) {
      return
    }
    wx.navigateTo({
      url: 'confirm?id='+this.data.groupon.id,
    })
  },
  call: function (e) {
    var phone = e.target.dataset.phone
    wx.makePhoneCall({
      phoneNumber: phone //仅为示例，并非真实的电话号码
    })
  },
  more:function(){
    wx.navigateTo({
      url: 'list',
    })
  },
  /**
   * 生命周期函数--监听页面初次渲染完成
   */
  onReady: function () {
    wx.hideLoading()
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
    this.setData({
      show_share_box:false
    })
  },
  getUser: function (e) {
    var that = this
    var user = e.detail.userInfo
    var encryptedData = e.detail.encryptedData
    var iv = e.detail.iv
    wx.request({
      url: host + 'huipay/user.php?action=update_user_info',
      data: {
        openid: wx.getStorageSync('openid'),
        mch_id: that.data.groupon.mch_id,
        encryptedData: encryptedData,
        iv: iv,
        session_key: wx.getStorageSync('session_key')
      },
      success: function (res) {
        that.setData({
          is_follow: true
        })
        if ('success' == res.data) {
          that.setData({
            show_share_box: true
          })
        }
      }
    })
  },
  share:function(){
    this.setData({
      show_share_box:true
    })
  },
  shareimage:function(){
    wx.showLoading({
      title: '生成海报中...',
    })
    var that = this
    wx.request({
      url: host + 'huipay/groupon.php?action=get_share_image',
      data: {
        id: that.data.groupon.id,
        openid: wx.getStorageSync('openid'),
        coupon_id: that.data.groupon.coupon_id,
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        wx.hideLoading()
        var url = res.data
        wx.previewImage({
          current: url,
          urls: [url]
        })
      }
    })
  },
  /**
   * 用户点击右上角分享
   */
  onShareAppMessage: function () {
  },
  backtoindex:function(){
    wx.switchTab({
      url: '../index/index',
    })
  }
})