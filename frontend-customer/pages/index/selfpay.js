// pages/index/selfpay.js
const host = require('../../config').host
Page({

  /**
   * 页面的初始数据
   */
  data: {
    is_bind: false,
    trade:'',
    input_disabled:false,
    disabled: true,
    paybeforCampaign: [],
    is_recharge_nopay:false,
    is_paybuycoupon:false,
    show_rechargenopay: false,
    show_paybuycoupon:false,
    buy_coupon_id:0,
    buy_coupon_total:0
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function(options) {
    wx.showLoading({
      title: '加载中',
    })
    if ('counter' in options) {
      //扫小程序码
      var counter = options.counter
    } else {
      //扫网页链接二维码
      var url = decodeURIComponent(options.q)
      var params = url.split('=')
      var counter = params[1]
    }
    var that = this
    wx.request({
      url: host + 'pay.php?action=get_counter',
      data: {
        counter: counter
      },
      header: {
        'content-type': 'application/json'
      },
      success: function(res) {
        if (!res.data) {
          wx.showModal({
            title: '无法付款',
            content: '此收款码未绑定商户',
            showCancel: false
          })
          return
        } else {
          wx.setNavigationBarTitle({
            title: res.data.merchant_name
          })
          wx.setStorageSync('mch_id', res.data.mch_id)
          that.setData({
            is_bind: true,
            merchant_name: res.data.merchant_name,
            branch_name: res.data.branch_name,
            mch_id: res.data.mch_id,
            counter: counter,
            counter_name: res.data.name,
            shop_id: res.data.shop_id
          })
          that.getPayCampaign(res.data.mch_id)
        }
      }
    })
  },
  /**
   * 生命周期函数--监听页面显示
   */
  onShow: function() {

  },
  monitor: function(e) {
    var trade = e.detail.value
    if (!this.data.is_bind) {
      return
    }
    if (parseFloat(trade) > 0) {
      this.setData({
        origin_trade:trade,
        trade:trade,
        disabled: false
      })
    } else {
      this.setData({
        disabled: true
      })
    }
    var tmp = trade.split('.')
    if (tmp.length > 1) {
      if (tmp[1].length > 2) {
        return trade.substr(0, e.detail.cursor - 1)
      }
      if (tmp.length > 2) {
        return trade.substr(0, e.detail.cursor - 1)
      }
    }
    if (this.data.paybeforCampaign.length > 0) {
      var obj = {}
      for (var i = 0; i < this.data.paybeforCampaign.length; i++) {
        obj = this.data.paybeforCampaign[i]
        if ('rechargenopay' == obj.campaign_type) {
          if (parseFloat(trade) >= parseFloat(obj.consume)) {
            this.setData({
              show_rechargenopay: true,
              recharge_total: obj.total,
              recharge_discount: parseFloat(obj.discount)
            })
            break
          } else {
            this.setData({
              show_rechargenopay: false
            })
          }
        } else if ('paybuycoupon' == obj.campaign_type) {
          if (parseFloat(trade) >= parseFloat(obj.consume)) {
            this.setData({
              show_paybuycoupon: true,
              title:obj.title,
              buy_coupon_id: obj.coupon_id,
              buy_coupon_total:obj.coupon_total,
              coupon_amount: parseFloat(obj.coupon_amount)
            })
            break
          } else {
            this.setData({
              show_paybuycoupon: false
            })
          }
        }
      }
    }
  },
  submit: function(e) {
    var trade = e.detail.value.trade
    if (!trade) {
      wx.showToast({
        title: '请输入买单金额',
        image: '/images/close.png',
        duration: 2000
      })
      return;
    }
    var that = this
    this.data.interval = setInterval(
      function() {
        if (wx.getStorageSync('openid')) {
          clearInterval(that.data.interval)
          wx.hideLoading()
          if (that.data.is_recharge_nopay) {
            that.rechargenopay(trade)
          } else {
            that.pay(trade)
          }
        } else {
          wx.showLoading({
            title: '请求中...',
          })
        }
      }, 200);
  },
  pay: function(trade) {
    var that = this
    wx.request({
      url: host + 'pay.php?action=selfpay',
      data: {
        trade: trade,
        sub_mch_id: that.data.mch_id,
        openid: wx.getStorageSync('openid'),
        counter: that.data.counter,
        counter_name: that.data.counter_name,
        shop_id: that.data.shop_id
      },
      header: {
        'content-type': 'application/json'
      },
      success: function(res) {
        var key = res.data.key
        var can_cash = res.data.can_cash
        var member = res.data.member
        var is_member = member.cardnum != '' ? true : false
        var get_point = is_member ? res.data.get_point : 0
        if ((parseInt(member.coupons) > 0 || parseFloat(member.recharge) > 0 || parseInt(member.grade) > 1 || (member.point && can_cash)) && !that.data.is_paybuycoupon){
          wx.setStorageSync('member', member)
          wx.setStorageSync('is_member', is_member)
          var url = 'action=self&key=' + key + '&shop_id=' + that.data.shop_id
          wx.navigateTo({
            url: 'pay?q=' + encodeURIComponent(url),
          })
        } else {
          wx.request({
            url: host + 'pay.php?action=getPrepay',
            data: {
              openid: wx.getStorageSync('openid'),
              trade: trade,
              consume: trade,
              key: key,
              get_point: get_point,
              reduce: 0,
              save: 0,
              discount: 0,
              member_discount: 0,
              is_member: is_member,
              is_paybuycoupon:that.data.is_paybuycoupon ? 1 : 0,
              buy_coupon_id:that.data.buy_coupon_id,
              buy_coupon_total:that.data.buy_coupon_total,
              pay_action:'self'
            },
            header: {
              'content-type': 'application/json'
            },
            success: function(res) {
              var payargs = res.data
              wx.requestPayment({
                'timeStamp': payargs.timeStamp,
                'nonceStr': payargs.nonceStr,
                'package': payargs.package,
                'signType': payargs.signType,
                'paySign': payargs.paySign,
                'success': function(res) {
                  wx.redirectTo({
                    url: 'paydirect?key=' + key + '&consume=' + trade + '&is_member=' + is_member,
                  })
                }
              })
            }
          })
        }
      }
    })
  },
  rechargenopay: function (trade) {
    var that = this
    wx.request({
      url: host + 'pay.php?action=getRechargeNoPayPrepay',
      data: {
        openid: wx.getStorageSync('openid'),
        mch_id: that.data.mch_id,
        counter:that.data.counter,
        origin_trade:that.data.origin_trade,
        trade: trade,
        recharge_discount:that.data.recharge_discount
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        var payargs = res.data
        wx.requestPayment({
          'timeStamp': payargs.timeStamp,
          'nonceStr': payargs.nonceStr,
          'package': payargs.package,
          'signType': payargs.signType,
          'paySign': payargs.paySign,
          'success': function (res) {
            wx.redirectTo({
              url: 'paydirect?key=placeholder&consume=0&is_member=' + wx.getStorageSync('is_member'),
            })
          }
        })
      }
    })
  },
  /**
   * 生命周期函数--监听页面初次渲染完成
   */
  onReady: function() {
    wx.hideLoading()
  },

  /**
   * 生命周期函数--监听页面隐藏
   */
  onHide: function() {

  },

  /**
   * 生命周期函数--监听页面卸载
   */
  onUnload: function() {

  },
  checkPayBuyCouponChange:function(e){
    var that = this
    var length = e.detail.value.length
    var obj = e.detail.value
    var origin_trade = this.data.origin_trade
    if (length > 0) {
      for (var i = 0; i < length; i++) {
        if ('use_paybuycoupon' == obj[i]) {
          var coupon_amount = e.target.dataset.coupon_amount
          var new_trade = Number(origin_trade) + Number(coupon_amount)
          if (String(new_trade).indexOf('.') > 0) {
            new_trade = new_trade.toFixed(2)
          }
          that.setData({
            is_paybuycoupon:true,
            trade:new_trade,
            input_disabled:true
          })
        }
      }
    } else {
      that.setData({
        is_paybuycoupon:false,
        trade:origin_trade,
        input_disabled:false
      })
    }
  },
  checkRechargeNopayChange:function(e){
    var that = this
    var length = e.detail.value.length
    var obj = e.detail.value
    var origin_trade = this.data.origin_trade
    if (length > 0) {
      for (var i = 0; i < length; i++) {
        if ('use_recharge' == obj[i]) {
          var recharge_total = e.target.dataset.recharge
          var discount       = e.target.dataset.discount
          var new_trade = origin_trade * recharge_total
          if (String(new_trade).indexOf('.') > 0) {
            new_trade = new_trade.toFixed(2)
          }
          that.setData({
            is_recharge_nopay:true,
            trade:new_trade,
            input_disabled:true
          })
        }
      }
    } else {
      that.setData({
        is_recharge_nopay:false,
        trade:origin_trade,
        input_disabled:false
      })
    }
  },
  getPayCampaign: function(mch_id) {
    var that = this
    wx.request({
      url: host + 'pay.php?action=getPayBefor',
      data: {
        mch_id: mch_id
      },
      header: {
        'content-type': 'application/json'
      },
      success: function(res) {
        that.setData({
          paybeforCampaign: res.data
        })
      }
    })
  }
})