// pages/index/pay.js
const host = require('../../config').host
Page({

  /**
   * 页面的初始数据
   */
  data: {
    pay_action:'waimai',
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
    order_type:'delivery',
    subscribe_index: 0,
    subscribe_day_index:'0',
    delivery_day:'',
    contact_name:'',
    contact_mobile:''
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function(options) {
    var that = this
    wx.request({
      url: host + 'huipay/waimai.php?action=get_order',
      data: {
        mch_id:wx.getStorageSync('mch_id'),
        openid:wx.getStorageSync('openid'),
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        if ('0' == res.data.order_amount) {
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
        } else if (res.data.order_amount <= res.data.cost_atleast) {
          wx.showModal({
            title: '点单满'+res.data.cost_atleast+'元起送',
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
          order:res.data,
          delivery_cost:res.data.delivery_cost,
          cost_total:res.data.cost_total,
          delivery_subscribe: res.data.can_immediate ? false : true,
          subscribe_times:res.data.subscribe_delivery_times,
          subscribe_days:res.data.subscribe_delivery_days
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
        trade: that.data.cost_total,
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
    this.get_shop()
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
          that.get_distance()
        }
      }
    })
  },
  get_shop:function(){
    var shop = wx.getStorageSync('shop')
    if (!shop) {
      wx.request({
        url: host + 'shop.php?action=get_detail',
        data: {
          mch_id: wx.getStorageSync('mch_id')
        },
        header: {
          'content-type': 'application/json'
        },
        success: function(res) {
          wx.setStorageSync('shop', res.data)
        }
      })
    }
  },
  get_distance:function(){
    var that = this
    var shop = wx.getStorageSync('shop')
    this.data.shop_interval = setInterval(
      function () {
        if (shop) {
          clearInterval(that.data.shop_interval)
          wx.hideLoading()
        } else {
          wx.showLoading()
        }
      }
    )
    wx.request({
      url: host + 'huipay/waimai.php?action=get_distance',
      data: {
        shop_latitude:shop.latitude,
        shop_longitude:shop.longitude,
        user_latitude:that.data.user_latitude,
        user_longitude:that.data.user_longitude
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        that.setData({
          user_distance:res.data
        })
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
        pay_action: that.data.pay_action,
        grade: is_member ? member.grade : 0,
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
    if ('delivery' == this.data.order_type) {
      if (!this.data.address_no) {
        wx.showModal({
          title:'请填写收货地址',
          showCancel:false
        })
        return
      }
      var user_distance = this.data.user_distance
      var delivery_distance = this.data.order.delivery_distance
      if (user_distance > delivery_distance) {
        wx.showModal({
          title:'收货地址超出配送距离',
          showCancel:false
        })
        return
      }
    } else {
      var contact_name = e.detail.value.contact_name
      var contact_mobile = e.detail.value.contact_mobile
      if (!contact_name) {
        wx.showModal({
          title:'请填写提货人姓名',
          showCancel:false
        })
        return
      }
      if (!contact_mobile) {
        wx.showModal({
          title:'请填写提货人手机号',
          showCancel:false
        })
        return
      }
      this.update_contact(contact_name,contact_mobile)
    }
    this.setData({
      pay_disabled: true
    })
    var id = e.detail.value.id
    var consume = e.detail.value.consume
    var trade = this.data.trade
    var delivery_cost = e.detail.value.delivery_cost
    var package_cost  = e.detail.value.package_cost
    var cost_total = e.detail.value.cost_total
    var delivery_title = 'delivery' == this.data.order_type ? '送到' : '自提'
    var delivery_day   = this.data.subscribe_days[this.data.subscribe_day_index]
    var delivery_subscribe_time = this.data.delivery_subscribe ? this.data.subscribe_times[this.data.subscribe_index] : '立即'
    var delivery_time = delivery_day + delivery_subscribe_time + delivery_title
    var formId = e.detail.formId
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
          delivery_cost:delivery_cost,
          package_cost:package_cost,
          delivery_time:delivery_time,
          order_type:that.data.order_type,
          pay_action: that.data.pay_action
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
              that.subscribe(out_trade_no)
              setTimeout(function(){ 
                wx.redirectTo({
                  url: 'detail?out_trade_no='+out_trade_no
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
                formId: formId,
                consume: 0,
                get_point: that.data.recharge_point,
                pay_action: that.data.pay_action,
                delivery_cost:delivery_cost,
                delivery_time:delivery_time,
                package_cost:package_cost
              },
              success: function(res) {
                var out_trade_no = res.data.out_trade_no
                setTimeout(function(){ 
                  wx.redirectTo({
                    url: 'detail?out_trade_no='+out_trade_no
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
      url: 'address',
    })
  },
  subscribe:function(out_trade_no){
    var that = this
    if ('delivery' == this.data.order_type) {
      var tmplIds = ['AjhkjC7EskqQZ1fYH-qoefHbOP-Qiswpygx4rQrImuY', 'EVe5Em40ho0JzCuJAhtgjnch52ZiE84zvovrGpoxcpA']
    } else {
      //自提，添加自提消息订阅
      var tmplIds = ['AjhkjC7EskqQZ1fYH-qoefHbOP-Qiswpygx4rQrImuY', 'c-e8evLEn0UsxLKalPigrP3yMPyez0qEmnBOo2zDPIk']
    }
    wx.requestSubscribeMessage({
      tmplIds: tmplIds,
      success (res) {
        for(var tmp in res) {
          if ('AjhkjC7EskqQZ1fYH-qoefHbOP-Qiswpygx4rQrImuY' == tmp && 'accept' == res[tmp]) {
            that.update_remind('is_accept_remind', 1, out_trade_no)
          } else if ('EVe5Em40ho0JzCuJAhtgjnch52ZiE84zvovrGpoxcpA' == tmp && 'accept' == res[tmp]) {
            that.update_remind('is_delivery_remind', 1, out_trade_no)
          } else if ('c-e8evLEn0UsxLKalPigrP3yMPyez0qEmnBOo2zDPIk' == tmp && 'accept' == res[tmp]) {
            that.update_remind('is_delivery_remind', 1, out_trade_no)
          }
        }
      },fail(res) {
      }
    })
  },
  update_remind:function(remind_function, is_remind, out_trade_no) {
    var that = this
    wx.request({
      url: host + 'huipay/waimai.php?action=update_remind',
      data: {
        out_trade_no:out_trade_no,
        remind_function:remind_function,
        is_remind:is_remind
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
      }
    })
  },
  update_contact:function(contact_name, contact_mobile){
    wx.request({
      url: host + 'huipay/user.php?action=update_waimai_contact',
      data: {
        mch_id:wx.getStorageSync('mch_id'),
        openid:wx.getStorageSync('openid'),
        name: contact_name,
        mobile: contact_mobile,
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
      }
    })
  },
  exchangeDeliveryTypeSwitch: function (e) {
    var that = this
    var delivery_type = e.detail.value
    this.setData({
      subscribe_index:0,
      subscribe_day_index:0
     })
    if ('immediate' == delivery_type) {
       this.setData({
        delivery_subscribe:false,
       })
    } else {
      this.setData({
        delivery_subscribe:true,
        subscribe_times:that.data.order.subscribe_delivery_times,
       })
    }
  },
  exchangeOrderTypeSwitch: function (e) {
    var that = this
    var order_type = e.detail.value
    this.setData({
      order_type:e.detail.value,
      delivery_day:'',
      subscribe_index:0,
      subscribe_day_index:0,
      delivery_subscribe:!that.data.order.can_immediate ? true : false
    })
    if ('self' == order_type) {
      var delivery_cost = 0
      var cost_total = parseFloat(this.data.order.cost_total) - parseFloat(this.data.order.delivery_cost)
      var contact_name = ''
      var contact_mobile = ''
      if (this.data.address_name) {
        contact_name = this.data.address_name
        contact_mobile = this.data.address_mobile
      } else {
        var member = wx.getStorageSync('member')
        contact_name = member.hasOwnProperty('name') ? member.name : ''
        contact_mobile = member.hasOwnProperty('mobile') ? member.mobile : ''
      }
      this.setData({
        delivery_cost:delivery_cost,
        cost_total:cost_total,
        shop:wx.getStorageSync('shop'),
        contact_name:contact_name,
        contact_mobile:contact_mobile
      })
    } else {
      this.refreshAddress()
      var delivery_cost = this.data.order.delivery_cost
      var cost_total = this.data.order.cost_total
      this.setData({
        delivery_cost:delivery_cost,
        cost_total:cost_total,
      })
    }
    this.selfpay()
  },
  bindDeliveryTimeChange(e) {
    var that = this
    var delivery_time = this.data.subscribe_times[e.detail.value]
    this.setData({
      subscribe_index: e.detail.value,
      delivery_time:delivery_time
    })
  },
  bindDeliveryDayChange(e) {
    var that = this
    var delivery_day = this.data.order.subscribe_delivery_days[e.detail.value]
    if ('0' == e.detail.value){
      var subscribe_times = this.data.order.subscribe_delivery_times
    } else {
      var subscribe_times = this.data.order.subscribe_delivery_totalday_times
    }
    this.setData({
      subscribe_day_index: e.detail.value,
      delivery_day:delivery_day,
      delivery_time:subscribe_times[0],
      subscribe_times:subscribe_times,
      subscribe_index:0
    })
  }
})
