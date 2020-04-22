// pages/mall/detail.js
const host = require('../../config').host
Page({

  /**
   * 页面的初始数据
   */
  data: {
    buy_disable:false,
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
    this.get_shop()
    this.get_mall_config()
    var id = options.id
    var that = this
    wx.request({
      url: host + 'huipay/mall.php?action=get_detail',
      data: {
        id:id
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        var buy_disable = false
        var buy_title = '立即下单'
        if (parseInt(res.data.total_limit) - parseInt(res.data.sold) <= 0) {
          buy_disable = true
          buy_title = '已售完'
        }
        var member = wx.getStorageSync('member')
        that.setData({
          product:res.data,
          image_list:res.data.detail_images.split(','),
          buy_disable:buy_disable,
          buy_title:buy_title,
          balance:res.data.total_limit - res.data.sold > 0 ? res.data.total_limit - res.data.sold : 0,
          member:member
        })
      }
    })
  },
  get_mall_config:function(){
    var that = this
    wx.request({
      url: host + 'huipay/mall.php?action=get_config',
      data: {
        mch_id:wx.getStorageSync('mch_id')
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        that.setData({
          delivery_tip:res.data.delivery_tip
        })
      }
    })
  },
  buy:function(){
    if (this.data.buy_disable) {
      return
    }
    var that = this
    if (this.data.product.is_member_limit == '1' && !wx.getStorageSync('is_member')) {
      wx.showModal({
        title: '本优惠仅限会员购买',
        content: '现在去加入会员享受更多优惠',
        success(res){
          if (res.confirm) {
            wx.navigateTo({
              url: '../index/get_membercard?mch_id='+that.data.product.mch_id,
            })
          }
        }
      })
    }
    wx.showLoading()
    var product_id = this.data.product.id
    var cart = wx.getStorageSync('my_cart')
    if (!cart) {
      cart = [product_id]
    } else {
      if (cart.indexOf(product_id) > -1) {
      } else {
        cart.push(product_id)
      }
    }
    wx.setStorageSync('my_cart', cart)
    wx.hideLoading()
    this.open_cart()
  },
  call: function (e) {
    var phone = e.target.dataset.phone
    wx.makePhoneCall({
      phoneNumber: phone //仅为示例，并非真实的电话号码
    })
  },
  more:function(){
    wx.navigateTo({
      url: 'index',
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
    var appInfo = wx.getStorageSync('app_info')
    wx.setNavigationBarTitle({
      title: appInfo.nickname
    })
  },
  /**
   * 生命周期函数--监听页面隐藏
   */
  onHide: function () {
    this.setData({
      show_share_box:false
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

  },

  /**
   * 页面上拉触底事件的处理函数
   */
  onReachBottom: function () {

  },
  getUser: function (e) {
    var that = this
    var user = e.detail.userInfo
    var encryptedData = e.detail.encryptedData
    var iv = e.detail.iv
    wx.request({
      url: host + 'huipay/user.php?action=update_user_info',
      data: {
        appid: wx.getStorageSync('appid'),
        key: 'placeholder',
        openid: wx.getStorageSync('openid'),
        mch_id: that.data.product.mch_id,
        encryptedData: encryptedData,
        iv: iv,
        session_key: wx.getStorageSync('session_key')
      },
      success: function (res) {
        that.setData({
          member: res.data
        })
        that.setData({
          show_share_box: true
        })
      }
    })
  },
  put_to_cart:function(){
    wx.showLoading()
    var product_id = this.data.product.id
    var cart = wx.getStorageSync('my_cart')
    if (!cart) {
      cart = [product_id]
    } else {
      if (cart.indexOf(product_id) > -1) {
      } else {
        cart.push(product_id)
      }
    }
    wx.setStorageSync('my_cart', cart)
    wx.hideLoading()
    wx.showToast({
      title: '已添加至购物车',
      icon: 'none',
      duration: 2000
    })
  },
  get_shop: function() {
    var shop = wx.getStorageSync('shop')
    if (shop) {
      this.setData({
        shop: shop,
      })
      return
    }
    var mch_id = wx.getStorageSync('mch_id')
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
  open_cart:function(){
    var my_cart = wx.getStorageSync('my_cart')
    if (!my_cart || my_cart.length == 0) {
      wx.showToast({
        title: '购物车是空的',
        icon: 'none',
        duration: 2000
      })
      return
    }
    wx.navigateTo({
      url: 'cart',
    })
  },
  share:function(){
    this.setData({
      show_share_box:true
    })
    return
  },
  shareimage:function(){
    wx.showLoading({
      title: '生成海报中...',
    })
    var that = this
    wx.request({
      url: host + 'huipay/mall.php?action=get_share_image',
      data: {
        appid:wx.getStorageSync('appid'),
        openid: wx.getStorageSync('openid'),
        product_id: that.data.product.id,
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
  previewImage:function(e){
    var current_url = e.target.dataset.src
    var urls = new Array
    var url_list = this.data.image_list
    for(var i=0;i<url_list.length;i++){
      urls[i] = url_list[i]
    }
    wx.previewImage({
      current: current_url,
      urls: urls
    })
  },
  /**
   * 用户点击右上角分享
   */
  onShareAppMessage: function (res) {
    var that = this
    var path = '/pages/mall/detail?id=' + that.data.product.id
    return {
      title: that.data.product.title,
      imageUrl:that.data.product.icon_url,
      path: path
    }
  },
  backtoindex:function(){
    wx.reLaunch({
      url: '../index/index',
    })
  },
  buttontap(e) {
  }
})
