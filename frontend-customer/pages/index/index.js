//index.js
//获取应用实例
const app = getApp()
const host = require('../../config').host
Page({
  data: {
    showGroupTip: false,
    showOpenCardBox: false,
    init_load: false,
    is_selfpay: true,
    marketing_type: 'pay'
  },
  //事件处理函数
  onReady: function() {
    wx.hideLoading()
  },
  onLoad: function(options) {
    wx.showLoading({
      title: '加载中',
    })
    var that = this
    this.data.interval = setInterval(
      function() {
        if (wx.getStorageSync('is_load_member')) {
          clearInterval(that.data.interval)
          that.initIndex()
        }
      }, 200);
  },
  onHide: function(e) {
    this.setData({
      showGroupTip: false,
      init_load: true
    })
  },
  initIndex() {
    if ('0' == wx.getStorageSync('member_multiple_cards')) {
      var mch_id = wx.getStorageSync('mch_id')
      if (mch_id) {
        wx.redirectTo({
          url: 'get_membercard?mch_id='+mch_id,
        })
      } else {
        wx.redirectTo({
          url: 'no_shop',
        })
      }
    } else {
      this.getBackgroundTopImage()
      this.getMerchant()
      this.get_shop()
      this.getMemberDetail()
      this.loadGroupon()
      this.loadTogether()
      this.loadVipcardList()
      this.loadMallList()
    }
  },
  onShow: function() {
    if (this.data.init_load) {
      this.getMemberDetail()
      this.get_shop()
    }
    var that = this
    wx.getSystemInfo({
      success: function (res) {
        that.setData({
          swiper_height: res.windowWidth * 0.6
        })
      }
    })
  },
  openimg:function(e){
    wx.navigateTo({ 
      url: '../vlog/list?mch_id='+wx.getStorageSync('mch_id'), 
    })
  },
  getBackgroundTopImage:function(){
    var that = this
    wx.request({
    url: host + 'huipay/mch.php?action=get_top_background_img',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
      },
      header: {
        'content-type': 'application/json'
      },
      success: function(res) {
        that.setData({
          background_top:res.data
        })
      }
    })
  },
  getMemberDetail: function() {
    var that = this
    var mch_id = wx.getStorageSync('mch_id')
    var openid = wx.getStorageSync('openid')
    if (!mch_id || !openid) {
      return
    }
    wx.request({
      url: host + 'huipay/user.php?action=get_mch_detail',
      data: {
        openid: openid,
        mch_id: mch_id
      },
      header: {
        'content-type': 'application/json'
      },
      success: function(res) {
        wx.setStorageSync('member', res.data)
        if (!res.data.cardnum) {
          wx.redirectTo({
            url: 'get_membercard?mch_id='+mch_id,
          })
          return
        }
        wx.setStorageSync('is_member', true)
        that.setData({
          member:res.data,
          multiple_cards: wx.getStorageSync('member_multiple_cards'),
          showOpenCardBox: res.data.cardnum == '' ? true : false
        })
      }
    })
  },
  open_location(e) {
    var that = this
    var latitude = parseFloat(this.data.shop.latitude)
    var longitude = parseFloat(this.data.shop.longitude)
    wx.openLocation({
      latitude,
      latitude,
      longitude,
      longitude,
      scale: 18,
      name: that.data.shop.business_name
    })
  },
  call: function(e) {
    var phone = e.target.dataset.phone
    wx.makePhoneCall({
      phoneNumber: phone //仅为示例，并非真实的电话号码
    })
  },
  openPoint: function() {
    wx.navigateTo({
      url: '../point/list',
    })
  },
  openBalanceList: function() {
    wx.navigateTo({
      url: '../recharge/list',
    })
  },
  openCouponList: function() {
    wx.navigateTo({
      url: '../coupon/list',
    })
  },
  open_grades: function() {
    wx.navigateTo({
      url: '../vip/grade',
    })
  },
  openvip: function() {
    wx.switchTab({
      url: '../vip/index',
    })
  },
  openBill: function() {
    wx.switchTab({
      url: '../index/bill',
    })
  },
  open_card: function() {
    var mch_id = wx.getStorageSync('mch_id')
    var member = wx.getStorageSync('member')
    wx.navigateTo({
      url: 'get_membercard?key=key&get_point=0&mch_id=' + mch_id + '&grade=' + member.grade
    })
  },
  switch_shop: function() {
    wx.navigateTo({
      url: 'switch_shop',
    })
  },
  exchange: function(e) {
    var id = e.currentTarget.dataset.id
    wx.navigateTo({
      url: '../point/detail?id=' + id,
    })
  },
  groupon: function(e) {
    wx.switchTab({
      url: '../groupon/list',
    })
  },
  getMerchant: function() {
    var that = this
    var mch_id = wx.getStorageSync('mch_id')
    if (!mch_id) {
      return
    }
    wx.request({
      url: host + 'ssh_mch.php?action=get_detail',
      data: {
        mch_id: wx.getStorageSync('mch_id')
      },
      header: {
        'content-type': 'application/json'
      },
      success: function(res) {
        wx.setStorageSync('merchant', res.data)
        that.setData({
          merchant:res.data,
          mch_type: res.data.mch_type,
          marketing_type:res.data.marketing_type,
          is_selfpay : 'general' != res.data.mch_type ? true : false,
        })
      }
    })
  },
  get_shop: function() {
    var shop = wx.getStorageSync('shop')
    var mch_id = wx.getStorageSync('mch_id')
    if (!mch_id) {
      return
    }
    if (shop && shop.mch_id == mch_id) {
      this.setData({
        shop: shop
      })
      wx.setNavigationBarTitle({
        title: shop.business_name
      })
      return
    }
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
        wx.setNavigationBarTitle({
          title: res.data.business_name
        })
        wx.setStorageSync('shop', res.data)
        if (res.data.total > 1) {
          that.setData({
            is_selfpay: false
          })
        }
        that.setData({
          shop: res.data,
        })
      }
    })
  },
  selfpay: function() {
    var mch_id = wx.getStorageSync('mch_id')
    wx.request({
      url: host + 'pay.php?action=get_self_counter',
      data: {
        mch_id: mch_id
      },
      header: {
        'content-type': 'application/json'
      },
      success: function(res) {
        var url = 'counter=' + res.data.counter
        wx.navigateTo({
          url: 'selfpay?q=' + encodeURIComponent(url)
        })
      }
    })
  },
  openPoint: function() {
    wx.navigateTo({
      url: '../vip/point_history',
    })
  },
  openCouponList: function() {
    wx.navigateTo({
      url: '../coupon/list',
    })
  },
  open_grades: function() {
    wx.navigateTo({
      url: '../vip/grade',
    })
  },
  pointmall: function() {
    wx.navigateTo({
      url: '../point/list',
    })
  },
  waimai:function(){
    wx.navigateTo({
      url: '../waimai/index',
    })
  },
  mall:function(){
    wx.reLaunch({
      url: '../mall/index',
    })
  },
  ordering:function(){
    wx.navigateTo({
      url: '../order/index',
    })
  },
  recharge: function() {
    wx.navigateTo({
      url: '../recharge/list',
    })
  },
  openContactBtn: function() {
    this.setData({
      showGroupTip: true
    })
  },
  closeGroupBox: function() {
    this.setData({
      showGroupTip: false
    })
  },
  closeOpenCardBox: function() {
    this.setData({
      showOpenCardBox: false
    })
  },
  grouponHistory: function() {
    wx.switchTab({
      url: '../vip/groupon_history'
    })
  },
  buy: function (e) {
    var id = e.currentTarget.dataset.id
    wx.navigateTo({
      url: '../groupon/detail?id=' + id,
    })
  },
  loadGroupon: function () {
    var that = this
    var mch_id = wx.getStorageSync('mch_id')
    if (!mch_id) {
      return
    }
    wx.request({
      url: host + 'huipay/groupon.php?action=get_top',
      data: {
        mch_id: wx.getStorageSync('mch_id')
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        that.setData({
          grouponData: res.data
        })
      }
    })
  },
  loadTogether: function () {
    var that = this
    var mch_id = wx.getStorageSync('mch_id')
    if (!mch_id) {
      return
    }
    wx.request({
      url: host + 'huipay/together.php?action=get_top',
      data: {
        mch_id: wx.getStorageSync('mch_id')
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        that.setData({
          togetherData: res.data
        })
      }
    })
  },
  loadMallList: function () {
    var that = this
    var mch_id = wx.getStorageSync('mch_id')
    if (!mch_id) {
      return
    }
    wx.request({
      url: host + 'huipay/mall.php?action=get_top',
      data: {
        mch_id: wx.getStorageSync('mch_id')
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        that.setData({
          mallData: res.data
        })
      }
    })
  },
  buy_product:function(e){
    var id = e.currentTarget.dataset.id
    wx.navigateTo({
      url: '../mall/detail?id=' + id,
    })
  },
  buy_vipcard: function (e) {
    var id = e.currentTarget.dataset.id
    wx.navigateTo({
      url: '../vipcard/detail?id=' + id,
    })
  },
  together: function (e) {
    var id = e.currentTarget.dataset.id
    wx.navigateTo({
      url: '../together/detail?id=' + id,
    })
  },
  loadVipcardList: function () {
    var that = this
    var mch_id = wx.getStorageSync('mch_id')
    wx.request({
      url: host + 'huipay/vipcard.php?action=get_list',
      data: {
        mch_id: mch_id
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        that.setData({
          vipcardData: res.data
        })
      }
    })
  },
  open_intro:function(){
    wx.navigateToMiniProgram({
      appId: 'wx18a4d4b1d74b229f',
      path: 'pages/index/index',
      success(res) {
        // 打开成功
      }
    })
  },
  onShareAppMessage: function(res) {
    var that = this
    var shop = wx.getStorageSync('shop')
    var member = wx.getStorageSync('member')
    var mch_id = wx.getStorageSync('mch_id')
    return {
      title: member.nickname + '邀请您加入' + shop.business_name + '会员，尊享开卡礼和会员特权',
      imageUrl: shop.logo_url,
      path: '/pages/index/get_membercard?mch_id=' + mch_id
    }
  }
})
