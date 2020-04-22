
const host = require('../../config').host
Page({

  /**
   * 页面的初始数据
   */
  data: {
    pay_action:'mall',
    address_name:'',
    address_mobile:'',
    address: '',
    address_no:'',
    action: '',
    key: 0,
    trade: 0,
    amount: 0,
    use_recharge: 0,
    use_point: 0,
    consume_recharge: 0,
    consume_point: 0,
    is_member: false,
    interval: '',
    radio_none_checked: false,
    pay_disabled: true,
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function(options) {
    var that = this
    var amount = options.amount
    var distribute_id = options.distribute_id
    var cart = options.cart
    var buy_totals = options.buy_totals
    wx.request({
      url: host + 'huipay/mall.php?action=get_config',
      data: {
        mch_id:wx.getStorageSync('mch_id'),
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        var delivery_cost = res.data.delivery_cost
        if (res.data.delivery_free_atleast && amount > res.data.delivery_free_atleast) {
          delivery_cost = 0
        }
        var trade = parseFloat(amount) + parseFloat(delivery_cost)
        that.setData({
          amount:amount,
          trade:trade,
          delivery_cost:delivery_cost,
          delivery_free_atleast:res.data.delivery_free_atleast,
          distribute_id:distribute_id,
          cart:cart,
          buy_totals:buy_totals
        })
        that.selfpay()
      }
    })
  },
  selfpay:function(){
    var that = this
    wx.request({
      url: host + 'pay.php?action=selfpay',
      data: {
        trade: that.data.trade,
        sub_mch_id: wx.getStorageSync('mch_id'),
        openid: wx.getStorageSync('openid'),
        counter: 0,
        counter_name: '在线下单',
        shop_id: 0
      },
      header: {
        'content-type': 'application/json'
      },
      success: function(res) {
        var key = res.data.key
        that.get_detail(key)
      }
    })
  },
  get_detail:function(key) {
    var that = this
    var member = wx.getStorageSync('member')
    wx.request({
      url: host + 'pay.php?action=get_detail',
      data: {
        sub_openid: wx.getStorageSync('openid'),
        pay_action: that.data.pay_action,
        key: key,
        grade: member.grade
      },
      header: {
        'content-type': 'application/json'
      },
      success: function(res) {
        wx.hideLoading()
        if (!res.data.trade) {
          wx.showModal({
            title: '支付已经过期',
            content: '',
            success(res) {
              wx.reLaunch({
                url: '../index/index',
              })
            }
          })
        }
        that.setData({
          pay_disabled:false,
          key: key,
          trade: res.data.trade,
          get_point: res.data.get_point,
          consume: res.data.consume,
          award_coupon_id: res.data.award_coupon_id,
          award_coupon_name: res.data.award_coupon_name,
          award_coupon_total: res.data.award_coupon_total,
          member_coupons: res.data.member_coupons,
          recharge: member ? member.recharge : 0,
          point: member ? member.point : 0,
          use_coupon_id: 0,
          use_coupon_amount: 0,
          use_coupon_name: '',
          save: res.data.save,
          point_amount: 0,
          reduce: res.data.reduce,
          discount: res.data.discount,
          member_discount: res.data.member_discount,
          grade_title: member ? member.grade_title : '',
          point_speed: res.data.point_speed,
          point_title: res.data.point_title,
          reduce_title: res.data.reduce_title,
          discount_title: res.data.discount_title,
          can_cash: res.data.can_cash,
          exchange_need_points: res.data.exchange_need_points,
          award_title: res.data.award_title,
          payed_share: res.data.payed_share,
          recharge_point: 0,
          mch_id: res.data.mch_id
        })
      }
    })
  },
  checkboxChange: function(e) {
    var that = this
    var length = e.detail.value.length
    var obj = e.detail.value
    this.setData({
      'use_recharge': 0,
      'use_point': 0
    })
    for (var i = 0; i < length; i++) {
      if ('use_recharge' == obj[i]) {
        that.setData({
          'use_recharge': that.data.recharge
        })
      } else if ('use_point' == obj[i]) {
        that.setData({
          'use_point': that.data.point
        })
      }
    }
    that.refreshTrade()
  },
  /**
   * 生命周期函数--监听页面初次渲染完成
   */
  onReady: function() {
  },

  /**
   * 生命周期函数--监听页面显示
   */
  onShow: function() {
    this.setData({
      pay_disabled: false
    })
    this.refreshAddress()
  },
  refreshAddress:function(){
    var that = this
    wx.request({
      url: host + 'huipay/user.php?action=get_waimai_address',
      data: {
        mch_id:wx.getStorageSync('mch_id'),
        openid:wx.getStorageSync('openid')
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        if (res.data) {
          that.setData({
            address_name:res.data.name,
            address_mobile:res.data.mobile,
            address:res.data.address,
            address_no:res.data.address_no,
            user_latitude:res.data.latitude,
            user_longitude:res.data.longitude
          })
        }
      }
    })
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

  /**
   * 页面相关事件处理函数--监听用户下拉动作
   */
  onPullDownRefresh: function() {

  },

  /**
   * 页面上拉触底事件的处理函数
   */
  onReachBottom: function() {

  },
  refreshTrade: function() {
    var that = this
    var is_member = wx.getStorageSync('is_member')
    var member = wx.getStorageSync('member')
    wx.request({
      url: host + 'pay.php?action=refresh_detail',
      data: {
        sub_openid: wx.getStorageSync('openid'),
        openid: is_member ? member.openid : '',
        key: that.data.key,
        is_member: true,
        grade: is_member ? member.grade : 0,
        pay_action: that.data.pay_action,
        use_coupon_amount: that.data.use_coupon_amount,
        use_recharge: that.data.use_recharge,
        use_point: that.data.use_point
      },
      header: {
        'content-type': 'application/json'
      },
      success: function(res) {
        that.setData({
          trade: res.data.trade,
          point_amount: res.data.point_amount,
          consume_point: res.data.consume_point,
          get_point: res.data.get_point,
          consume: res.data.consume,
          award_coupon_id: res.data.award_coupon_id,
          award_coupon_name: res.data.award_coupon_name,
          award_coupon_total: res.data.award_coupon_total,
          consume_recharge: res.data.consume_recharge,
          consume_point: res.data.consume_point,
          save: res.data.save,
          reduce: res.data.reduce,
          discount: res.data.discount,
          point_speed: res.data.point_speed,
          recharge_point_speed: res.data.recharge_point_speed,
          recharge_point: res.data.recharge_point,
          recharge_point_title: res.data.recharge_point_title
        })
      }
    })
  },
  useCouponChange: function(e) {
    var that = this
    var member_coupons = this.data.member_coupons
    var coupon_id = e.detail.value
    for (var i = 0; i < member_coupons.length; i++) {
      var obj = member_coupons[i]
      if (obj.coupon_id == coupon_id) {
        var coupon_type = obj.coupon_type
        var discount = obj.discount
        var name = obj.coupon_name
        var amount = obj.amount
      }
    }

    if ('discount' == coupon_type) {
      //优惠券折扣在会员折扣买单折扣后计算
      var consume = this.data.trade - this.data.member_discount - this.data.discount
      var coupon_amount = consume * (10 - discount) / 10
      coupon_amount = coupon_amount.toFixed(2)
    } else {
      coupon_amount = amount
    }

    this.setData({
      use_coupon_id: coupon_id,
      use_coupon_amount: coupon_amount,
      use_coupon_name: name
    })
    this.refreshTrade()
  },
  submit: function(e) {
    if (!this.data.address_no) {
      wx.showModal({
        title:'请填写收货地址',
        showCancel:false
      })
      return
    }
    this.setData({
      pay_disabled: true
    })
    var trade = this.data.trade
    var consume = e.detail.value.consume
    var that = this
    if (consume > 0) {
      wx.request({
        url: host + 'pay.php?action=getPrepay',
        data: {
          openid: wx.getStorageSync('openid'),
          trade: trade,
          get_point: that.data.get_point,
          consume: that.data.consume,
          use_coupon_id: that.data.use_coupon_id,
          use_coupon_amount: that.data.use_coupon_amount,
          use_coupon_name: that.data.use_coupon_name,
          consume_recharge: that.data.consume_recharge,
          consume_point: that.data.consume_point,
          point_amount: that.data.point_amount,
          use_point: that.data.use_point,
          reduce: that.data.reduce,
          save: that.data.save,
          discount: that.data.discount,
          member_discount: that.data.member_discount,
          key: that.data.key,
          is_member: true,
          award_coupon_id: that.data.award_coupon_id,
          award_coupon_name: that.data.award_coupon_name,
          award_coupon_total: that.data.award_coupon_total,
          amount:that.data.amount,
          delivery_cost:that.data.delivery_cost,
          pay_action: that.data.pay_action,
          distribute_id:that.data.distribute_id,
          cart:that.data.cart,
          buy_totals:that.data.buy_totals
        },
        header: {
          'content-type': 'application/json'
        },
        success: function(res) {
          var payargs = res.data
          var out_trade_no = payargs.out_trade_no
          wx.requestPayment({
            'timeStamp': payargs.timeStamp,
            'nonceStr': payargs.nonceStr,
            'package': payargs.package,
            'signType': payargs.signType,
            'paySign': payargs.paySign,
            'success': function(res) {
              wx.removeStorageSync('my_cart')
              setTimeout(function(){ 
                wx.reLaunch({
                  url: '../vip/mall_detail?out_trade_no='+out_trade_no
                })
              }, 1000);
            },
            'complete': function(res) {
              that.setData({
                pay_disabled:false
              })
            }
          })
        }
      })
    } else {
      wx.showModal({
        title: '请确认',
        content: '本次消费共使用储值余额' + that.data.consume_recharge + '元',
        success(res) {
          if (res.confirm) {
            wx.request({
              url: host + 'pay.php?action=consume_no_money',
              data: {
                appid:wx.getStorageSync('appid'),
                openid: wx.getStorageSync('openid'),
                key: that.data.key,
                trade: trade,
                use_coupon_id: that.data.use_coupon_id,
                use_coupon_amount: that.data.use_coupon_amount,
                use_coupon_name: that.data.use_coupon_name,
                use_recharge: that.data.use_recharge,
                use_point: that.data.use_point,
                consume_recharge: that.data.consume_recharge,
                consume_point: that.data.consume_point,
                point_amount: that.data.point_amount,
                use_point: that.data.use_point,
                reduce: that.data.reduce,
                save: that.data.save,
                discount: that.data.discount,
                member_discount: that.data.member_discount,
                consume: 0,
                get_point: that.data.recharge_point,
                pay_action: that.data.pay_action,
                delivery_cost:delivery_cost,
              },
              success: function(res) {
                var out_trade_no = res.data.out_trade_no
                wx.removeStorageSync('my_cart')
                setTimeout(function(){ 
                  wx.reLaunch({
                    url: '../vip/mall_detail?out_trade_no='+out_trade_no
                  })
                }, 1000);
              }
            })
          } else {
            /*that.setData({
              pay_disabled:false
            })*/
          }
        }
      })
    }
  },
  edit_address:function(){
    wx.navigateTo({
      url: '../waimai/address',
    })
  },
})
