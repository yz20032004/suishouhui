// pages/order/preview.js
const host = require('../../config').host
Page({

  /**
   * 页面的初始数据
   */
  data: {
    is_loaded:false
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    var that = this
    wx.request({
      url: host + 'huipay/ordering.php?action=get_order',
      data: {
        mch_id:wx.getStorageSync('mch_id'),
        openid:wx.getStorageSync('openid'),
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        if ('0' == res.data.amount) {
          wx.showModal({
            title: '请至少选购一件商品',
            showCancel:false,
            success(res){
              wx.navigateTo({
                url: 'index',
              })
            }
          })
          return
        } 
        that.setData({
          is_loaded:true,
          order:res.data,
          member:wx.getStorageSync('member')
        })
        if ('1' == res.data.pay_first) {
          that.selfpay()          
        }
      }
    })
  },
  selfpay:function(){
    var that = this
    var trade = this.data.order.amount
    var table_id = this.data.order.table_id
    wx.request({
      url: host + 'pay.php?action=selfpay',
      data: {
        trade: trade,
        sub_mch_id: wx.getStorageSync('mch_id'),
        openid: wx.getStorageSync('openid'),
        counter: table_id,
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
  opencard:function(){
    wx.navigateTo({
      url: '../index/get_membercard?mch_id='+wx.getStorageSync('mch_id'),
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
    if (this.data.is_loaded) {
      wx.navigateBack({
        delta:-1
      })
    }
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
})