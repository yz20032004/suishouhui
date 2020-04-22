// pages/waimai/index.js
const host = require('../../config').host
Page({

  /**
   * 页面的初始数据
   */
  data: {
    order_self:true,
    enter_order:true
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    wx.showLoading({
      title: '加载中',
    })
    var that = this
    this.data.interval = setInterval(
      function () {
        if (wx.getStorageSync('is_load_member')) {
          clearInterval(that.data.interval)
          if (options.hasOwnProperty('mch_id')) {
            var mch_id = options.mch_id
            wx.setStorageSync('mch_id', mch_id)
          } else {
            var mch_id = wx.getStorageSync('mch_id')
          }
          var table_id = options.hasOwnProperty('table_id') ? options.table_id : 0
          var openid = wx.getStorageSync('openid')
          that.getOrderUrl(mch_id, openid, table_id)
        }
      }, 200)
  },

  /**
   * 生命周期函数--监听页面初次渲染完成
   */
  onReady: function () {
    wx.hideLoading()
    var that = this
    var shop = wx.getStorageSync('shop')
    if (!shop) {
      wx.request({
        url: host + 'ssh_mch.php?action=get_detail',
        data: {
          mch_id: wx.getStorageSync('mch_id')
        },
        header: {
          'content-type': 'application/json'
        },
        success: function(res) {
          that.setData({
            merchant_name:res.data.merchant_name
          })
        }
      })
    } else {
      that.setData({
        merchant_name:shop.business_name
      })
    }
  },
  getOrderUrl: function (mch_id, openid, table_id) {
    var that = this
    wx.request({
      url: host + 'huipay/ordering.php?action=get_order_url',
      data: {
        mch_id:mch_id,
        openid:openid,
        table_id:table_id
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        if ('fail' == res.data.errcode) {
          wx.showModal({
            title: '自助点单功能已关闭',
            showCancel:false,
            success:function(){
              wx.switchTab({
                url: '../index/index',
              })
            }
          })
        } else if ('close' == res.data.errcode) {
          wx.showModal({
            title: '现在为商户非自助点单时间段',
            content:'',
            showCancel:false,
            success:function(){
              wx.switchTab({
                url: '../index/index',
              })
            }
          })
        } else if ('repeat' == res.data.errcode) {
          that.setData({
            enter_order:false
          })
          that.getOrder(mch_id, openid)
        } else if ('hasseat' == res.data.errcode) {
          that.setData({
            order_self:false,
            enter_order:false
          })
          that.getOrder(mch_id, res.data.openid)
        } else {
          that.setData({
            order_url:encodeURI(res.data)
          })
        }
      }
    })
  },
  getOrder:function(mch_id, openid){
    var that = this
    wx.request({
      url: host + 'huipay/ordering.php?action=get_order',
      data: {
        mch_id: mch_id,
        openid: openid
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        that.setData({
           order:res.data,
           dishes:res.data.dishes,
        })
      }
    })
  },
  order_append:function(){
    var that = this
    if (!this.data.order_self) {
      wx.showModal({
        title: '您不可以加菜',
        content:'请让最先点菜的人加菜',
        showCancel:false,
        success:function(){
        }
      })
      return
    }
    wx.request({
      url: host + 'huipay/ordering.php?action=get_order_append_url',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
        openid: wx.getStorageSync('openid'),
        table_id:that.data.order.table_id
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        that.setData({
          enter_order:true,
          order_url:res.data,
        })
      }
    })
  },
  order_pay:function(){
    var that = this
    var trade = this.data.order.amount
    wx.request({
      url: host + 'pay.php?action=selfpay',
      data: {
        trade: trade,
        sub_mch_id: wx.getStorageSync('mch_id'),
        openid: wx.getStorageSync('openid'),
        counter: that.data.order.table_id,
        counter_name: '在线点单',
        shop_id: 0
      },
      header: {
        'content-type': 'application/json'
      },
      success: function(res) {
        var key = res.data.key
        var url = 'action=ordering&key=' + key + '&shop_id=0'
        wx.navigateTo({
          url: '../index/pay?q=' + encodeURIComponent(url),
        })
      }
    })
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

  }
})