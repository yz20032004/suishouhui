// pages/trade/detail.js
const host = require('../../config').host
var app = getApp()
Page({
  data: {
    trade_image:'wechatpay'
  },
  onLoad: function (options) {
    if (options.hasOwnProperty('out_trade_no')) {
      var out_trade_no = options.out_trade_no
    } else if (options.hasOwnProperty('q')) {
      var url = decodeURIComponent(options.q)
      var params = url.split('=')
      var out_trade_no = params[1]
    }
    this.get_detail(out_trade_no)
  },
  get_detail:function(out_trade_no){
    var that = this
    wx.request({
      url: host + 'trade.php?action=get_detail',
      data: {
        out_trade_no: out_trade_no
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        if(res.data.mch_id != wx.getStorageSync('mch_id')) {
          wx.showModal({
            title: '您无权限查看此商户订单',
            content:'请联系您的管理员',
            showCancel:false,
            success(res){
              wx.reLaunch({
                url: '../index/index',
              })
            }
          })
          return
        }
        var point_amount = '0'
        var trade_image = 'wechatpay'
        if ('1' == res.data.pay_type) {
        } else if ('2' == res.data.pay_type) {
          trade_image = 'alipay'
        } else if ('3' == res.data.pay_type) {
          trade_image = 'recharge'
        } else {
          trade_image = 'cash'
        }
        if ('waimai' == res.data.pay_from) {
          that.get_waimai_detail(res.data.out_trade_no)
        } else if ('mall' == res.data.pay_from) {
          that.get_mall_detail(res.data.out_trade_no)
        }
        if ('在线点单' == res.data.detail) {
          that.get_ordering_detail(res.data.out_trade_no)
        }
        that.setData({
          tradeData: res.data,
          trade_image:trade_image,
          user_role:wx.getStorageSync('user_role')
        })
      }
    })
  },
  refund:function(){
    if ('admin' != wx.getStorageSync('user_role')) {
      wx.showModal({
        title: '温馨提示',
        content: '体验者或非管理员身份不可执行此操作',
        showCancel: false
      })
      return
    }
    var that = this
    var pay_type = that.data.tradeData.pay_type
    var url = host + 'trade.php?action=refund_wechat'
    if ('2' == pay_type) {
      url = host + 'trade.php?action=refund_alipay'
    }
    wx.showModal({
      title: '确定要退款吗',
      content: '',
      success(res){
        if (res.confirm) {
          wx.request({
            url: url,
            data: {
              mch_id: wx.getStorageSync('mch_id'),
              out_trade_no: that.data.tradeData.out_trade_no
            },
            header: {
              'content-type': 'application/json'
            },
            success: function (res) {
              if ('success' == res.data) {
                that.get_detail(that.data.tradeData.out_trade_no)
                wx.showToast({
                  title: '退款成功',
                })
              } else {
                wx.showToast({
                  title: '退款失败',
                  icon:'none'
                })
              }
            }
          })
        }
      }
    })
  },
  get_ordering_detail:function(out_trade_no){
    var that = this
    wx.request({
      url: host + 'trade.php?action=get_ordering_detail',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
        out_trade_no:out_trade_no
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        that.setData({
           order:res.data.order,
           dishes:res.data.dishes,
        })
      }
    })
  },
  get_waimai_detail:function(out_trade_no){
    var that = this
    wx.request({
      url: host + 'trade.php?action=get_waimai_detail',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
        out_trade_no:out_trade_no
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        var order_status = '已支付'
        if (res.data.order.closed_at != '0000-00-00 00:00:00') {
          order_status = '已收货'
        } else if (res.data.order.delivery_at != '0000-00-00 00:00:00') {
          order_status = '配送中'
        } else if  (res.data.order.accept_at != '0000-00-00 00:00:00') {
          order_status = '已接单'
        }
        that.setData({
           order:res.data.order,
           dishes:res.data.dishes,
           order_status:order_status
        })
      }
    })
  },
  get_mall_detail:function(out_trade_no){
    var that = this
    wx.request({
      url: host + 'trade.php?action=get_mall_detail',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
        out_trade_no:out_trade_no
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        var order_status = '已支付'
        if (res.data.order.closed_at != '0000-00-00 00:00:00') {
          order_status = '已收货'
        } else if (res.data.order.delivery_at != '0000-00-00 00:00:00') {
          order_status = '快递中'
        } else if  (res.data.order.accept_at != '0000-00-00 00:00:00') {
          order_status = '已接单'
        }
        that.setData({
           order:res.data.order,
           products:res.data.products,
           order_status:order_status
        })
      }
    })
  },
  order_delivery_product:function(){
    wx.navigateTo({
      url: '../mall/delivery?out_trade_no='+this.data.tradeData.out_trade_no,
    })
  },
  order_accept:function(){
    this.order_chanage_status('accept')
  },
  order_delivery:function(){
    this.order_chanage_status('delivery')
  },
  order_close:function(){
    this.order_chanage_status('close')
  },
  order_chanage_status:function(change_status){
    var that = this
    wx.request({
      url: host + 'trade.php?action=update_waimai_status',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
        out_trade_no:that.data.tradeData.out_trade_no,
        change_status:change_status
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        that.get_detail(that.data.tradeData.out_trade_no)
        wx.showToast({
          title: '外送状态变更成功',
          icon: 'success',
          duration: 2000
        })
      }
    })
  },
  copy_delivery_no:function(){
    var that = this
    var info = this.data.order.delivery_type + this.data.order.delivery_no
    wx.setClipboardData({
      data: info,
      success(res) {
        wx.getClipboardData({
          success(res) {
          }
        })
      }
    })
  },
  copy_address:function(){
    var that = this
    var info = this.data.order.contact_name + this.data.order.contact_mobile + this.data.order.contact_address
    wx.setClipboardData({
      data: info,
      success(res) {
        wx.getClipboardData({
          success(res) {
          }
        })
      }
    })
  },
  onReady: function () {
    // 页面渲染完成
  },
  onShow: function () {
    
  },
  onHide: function () {
    // 页面隐藏
  },
  onUnload: function () {
    // 页面关闭
  },
  back: function () {
    wx.navigateBack({
      delta: -1
    })
  }
})
