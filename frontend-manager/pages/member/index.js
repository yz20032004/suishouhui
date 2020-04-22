var app = getApp()
var sliderWidth = 96
const host = require('../../config').host
Page({
  data: {
    activeIndex: '0',
    sliderOffset: 0,
    sliderLeft: 0,
    custom_recharge_amount_display: 'none',
    coupons: null,
    used_coupon_type: '',
    trade: 0
  },
  onLoad: function (options) {
    var tabs = new Array()
    tabs[0] = '积分兑换'
    tabs[1] = '调整等级'
    tabs[2] = '充值'
    var that = this
    //调用应用实例的方法获取全局数据
    wx.getSystemInfo({
      success: function (res) {
        that.setData({
          tabs: tabs,
          sliderLeft: (res.windowWidth / tabs.length - sliderWidth) / 2,
          sliderOffset: res.windowWidth / tabs.length * that.data.activeIndex
        });
      }
    })
    var merchant_point_rule = wx.getStorageSync('merchant_point_rule')
    that.setData({
      userInfo: wx.getStorageSync('current_search_member'),
      can_used_for_money: merchant_point_rule.can_used_for_money,
      merchant: wx.getStorageSync('merchant'),
      demoMode: wx.getStorageSync('demoMode')
    })
  },
  tabClick: function (e) {
    this.setData({
      sliderOffset: e.currentTarget.offsetLeft,
      activeIndex: e.currentTarget.id
    });
  },
  onReady: function () {
    // 页面渲染完成
  },
  onShow: function () {
    // 页面显示
    var that = this
    var userInfo = wx.getStorageSync('current_search_member')
    var merchant_point_rule = wx.getStorageSync('merchant_point_rule')
    var merchant_grades = wx.getStorageSync('merchant_grades')
    wx.request({
      url: host + 'member.php?action=get_info',
      data: {
        merchant_id: wx.getStorageSync('merchant_id'),
        mobile: userInfo.mobile,
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        wx.setStorageSync('current_search_member', res.data)
        that.setData({
          userInfo: res.data,
        })
      }
    })

    wx.request({
      url: host + 'member.php?action=get_coupons',
      data: {
        merchant_id: wx.getStorageSync('merchant_id'),
        mobile: userInfo.mobile
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        var coupons = res.data['enable']
        if (coupons.length > 0) {
          coupons[coupons.length] = { coupon_id: 0, name: '请选择', amount: 0 }
          var couponIndex = coupons.length - 1
        }
        that.data.coupons = coupons
        that.setData({
          coupons: coupons,
          couponIndex: couponIndex,
          custom_coupon_amount_display: 'none'

        })
      }
    })
    wx.request({
      url: host + 'trade.php?action=get_recharges',
      data: {
        merchant_id: wx.getStorageSync('merchant_id'),
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        that.setData({
          rechargeData: res.data
        })
      }
    })
    wx.request({
      url: host + 'trade.php?action=get_point_exchanges',
      data: {
        merchant_id: wx.getStorageSync('merchant_id'),
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        that.setData({
          exchangeData: res.data,
        })
      }
    })
    that.setData({
      gradeData: merchant_grades
    })
  },
  onHide: function () {
    // 页面隐藏
  },
  onUnload: function () {
    // 页面关闭
  },
  openTradeList: function () {
    wx.navigateTo({ url: '../member/trade_list' })
  },
  openBalanceList: function () {
    wx.navigateTo({ url: '../member/recharge_list' })
  },
  openBalance: function () {
    wx.navigateTo({ url: '../member/set_balance' })
  },
  openPoint: function () {
    wx.navigateTo({ url: '../member/set_point' })
  },
  openCouponList: function () {
    wx.navigateTo({ url: '../member/coupon_list' })
  },
  bindCouponChange: function (e) {
    if (0 == this.data.trade) {
      wx.showModal({
        title: "请先输入消费金额",
        content: "",
        showCancel: false,
        confirmText: "确定"
      })
      return false
    }
    var custom_coupon_amount_display = ''
    var amount = parseInt(this.data.coupons[e.detail.value].amount)
    if (amount > 0) {
      custom_coupon_amount_display = 'none'
    } else if (this.data.coupons[e.detail.value].coupon_type == 'discount') {
      custom_coupon_amount_display = 'none'
      var trade = this.data.trade
      amount = Math.ceil(trade * (100 - this.data.coupons[e.detail.value].discount) / 100)
    } else {
      amount = ''
    }
    this.setData({
      couponIndex: e.detail.value,
      coupon_amount: amount,
      custom_coupon_amount_display: custom_coupon_amount_display
    })
  },
  recharge: function (e) {
    var recharge_id = e.detail.value.recharge_id
    var amount = e.detail.value.amount
    if ('' == recharge_id) {
      wx.showModal({
        title: "请选择一个储值档次",
        content: "",
        showCancel: false,
        confirmText: "确定"
      })
      return false
    } else if ('0' == recharge_id) {
      if (!amount) {
        wx.showModal({
          title: "请输入储值金额",
          content: "",
          showCancel: false,
          confirmText: "确定"
        })
        return false
      }
      if (isNaN(amount) || '0' == amount) {
        wx.showModal({
          title: "储值金额请填写数字且不能为0",
          content: "",
          showCancel: false,
          confirmText: "确定"
        })
        return false
      }
    }
    wx.navigateTo({ url: 'recharge_preview?amount=' + amount + '&recharge_id=' + recharge_id })
  },
  trade: function (e) {
    var trade_original = e.detail.value.trade_original
    var use_point = e.detail.value.use_point
    var use_recharge = e.detail.value.use_recharge
    var coupon_id = e.detail.value.coupon_id
    var coupon_name = e.detail.value.coupon_name
    var coupon_amount = e.detail.value.coupon_amount
    if (!trade_original) {
      wx.showModal({
        title: "请输入消费金额",
        content: "",
        showCancel: false,
        confirmText: "确定"
      })
      return false
    }
    if ('0' != coupon_id && !coupon_amount) {
      wx.showModal({
        title: "请输入优惠券抵扣金额",
        content: "",
        showCancel: false,
        confirmText: "确定"
      })
      return false
    }
    if (coupon_id != 0) {
      wx.request({
        url: host + 'trade.php?action=can_coupon_use',
        data: {
          merchant_id: wx.getStorageSync('merchant_id'),
          trade_original: trade_original,
          coupon_id: coupon_id
        },
        header: {
          'content-type': 'application/json'
        },
        success: function (res) {
          if ('success' == res.data) {
            wx.navigateTo({ url: 'preview?trade=' + trade_original + '&use_point=' + use_point + '&use_recharge=' + use_recharge + '&coupon_id=' + coupon_id + '&coupon_name=' + coupon_name + '&coupon_amount=' + coupon_amount })
          } else {
            wx.showModal({
              title: "该优惠券最低消费金额是" + res.data + '元',
              content: "",
              showCancel: false,
              confirmText: "确定"
            })
            return false
          }
        }
      })
    } else {
      wx.navigateTo({ url: 'preview?trade=' + trade_original + '&use_point=' + use_point + '&use_recharge=' + use_recharge + '&coupon_id=' + coupon_id + '&coupon_name=' + coupon_name + '&coupon_amount=' + coupon_amount })
    }
  },
  exchange: function (e) {
    var exchange_id = e.detail.value.exchange_id
    if (!exchange_id) {
      wx.showModal({
        title: "请选择兑换礼品",
        content: "",
        showCancel: false,
        confirmText: "确定"
      })
      return false
    }
    var userInfo = wx.getStorageSync('current_search_member')
    wx.request({
      url: host + 'member.php?action=exchange_point',
      data: {
        merchant_id: wx.getStorageSync('merchant_id'),
        mobile: userInfo.mobile,
        exchange_id: exchange_id,
        member_point: userInfo.point,
        shop_id: wx.getStorageSync('current_shop'),
        openid: wx.getStorageSync('openid')
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        if ('fail' == res.data) {
          wx.showModal({
            title: "积分余额不足",
            content: "",
            showCancel: false,
            confirmText: "确定"
          })
          return false
        } else {
          wx.showModal({
            title: "积分兑换成功",
            content: "",
            showCancel: false,
            confirmText: "确定",
            success: function (res) {
              wx.switchTab({ url: '../index/index' })
            }
          })
        }
      }
    })
  },
  changeGrade: function (e) {
    var that = this
    var grade = e.detail.value.grade
    var userInfo = wx.getStorageSync('current_search_member')
    if (userInfo.grade == grade) {
      wx.showModal({
        title: "等级没有变化",
        content: "",
        showCancel: false,
        confirmText: "确定"
      })
      return false
    }
    wx.request({
      url: host + 'member.php?action=change_grade',
      data: {
        merchant_id: wx.getStorageSync('merchant_id'),
        mobile: userInfo.mobile,
        grade: grade,
        openid: wx.getStorageSync('openid')
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        var message = '手工调整会员' + userInfo.mobile + '(' + userInfo.name + ')' + '等级到' + that.data.gradeData[grade - 1].name
        app.updateLog(message)
        wx.showModal({
          title: "等级调整成功",
          content: "",
          showCancel: false,
          confirmText: "确定",
          success: function (res) {
            wx.switchTab({ url: '../index/index' })
          }
        })
      }
    })
  },
  trade_recharge: function () {
    wx.navigateTo({ url: 'preview_recharge' })
  },
  previewMember: function () {
    var userInfo = wx.getStorageSync('current_search_member')
    wx.navigateTo({ url: '../member/index?mobile=' + userInfo.mobile })
  },
  bindRechargeChange: function (e) {
    if ('0' == e.detail.value) {
      this.setData({
        custom_recharge_amount_display: ''
      })
    } else {
      this.setData({
        custom_recharge_amount_display: 'none'
      })
    }
  },
  bindExchangeChange: function (e) {
    this.setData({
      exchangeIndex: e.detail.value
    })
  },
  get_trade_amount: function (e) {
    var trade = e.detail.value
    this.data.trade = trade
  }
})
