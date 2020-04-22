const host = require('../../config').host
var app = getApp()
Page({
  data: {
    rules: [
      { type: 'money_constant', title: "返固定金额" },
      { type: 'money_percent', title: "返充值比例金额" },
      { type: 'coupon', title: "返优惠券" }
    ],
    couponIndex:0,
    ruleIndex: 0,
    counts: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
    countIndex: 0,
    money_constant_display: '',
    money_percent_display: 'none',
    coupon_id:0,
    coupon_display: 'none',
    couponData: new Array()
  },
  onLoad: function (options) {
    // 页面初始化 options为页面跳转所带来的参数
    var id = options.id
    var that = this
    wx.request({
      url: host + 'ssh_marketing.php?action=get_recharge',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
        id: id
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        var award_coupons = new Array()
        var money_constant_display = 'none'
        var money_percent_display = 'none'
        var coupon_display = 'none'
        var ruleIndex = 0
        if ('money_constant' == res.data.award_type) {
          money_constant_display = ''
        } else if ('money_percent' == res.data.award_type) {
          money_percent_display = ''
          ruleIndex = 1
        } else {
          coupon_display = ''
          ruleIndex = 2
          award_coupons = res.data.coupons
        }
        that.setData({
          rechargeData: res.data,
          money_constant_display: money_constant_display,
          money_percent_display: money_percent_display,
          coupon_display: coupon_display,
          ruleIndex: ruleIndex,
          award_coupons:award_coupons
        })
      }
    })
  },
  onReady: function () {
    // 页面渲染完成
    var that = this
    wx.request({
      url: host + 'ssh_coupon.php?action=get_list',
      data: {
        mch_id: wx.getStorageSync('mch_id')
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        var ret = res.data
        that.setData({
          coupons: ret['enable']
        })
      }
    })
  },
  onShow: function () {
    // 页面显示
  },
  onHide: function () {
    // 页面隐藏
  },
  onUnload: function () {
    // 页面关闭
  },
  submit: function (e) {
    var id = e.detail.value.id
    var touch = e.detail.value.touch
    var coupon_id = e.detail.value.coupon_id
    var count = parseInt(e.detail.value.count_id) + parseInt(1)
    var coupon_name = this.data.coupons[this.data.couponIndex].name
    var card_id = this.data.coupons[this.data.couponIndex].wechat_cardid
    if (!coupon_id) {
      wx.showModal({
        title: "请选择奖励优惠券",
        content: "",
        showCancel: false,
        confirmText: "确定"
      })
      return false
    }
    wx.request({
      url: host + 'marketing.php?action=add_recharge_coupon',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
        id: id,
        touch:touch,
        coupon_id: coupon_id,
        card_id:card_id,
        coupon_name:coupon_name,
        count: count
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        if ('fail' == res.data) {
          wx.showModal({
            title: "不能重复添加该优惠券",
            content: "",
            showCancel: false,
            confirmText: "确定"
          })
          return false
        } else {
          wx.showToast({
            title: "操作成功",
            content: "",
            icon: 'success',
            duration: 2000,
            success: function (res) {
              wx.navigateBack({
                delta: 1
              })
            }
          })
        }
      }
    })
  },
  delete: function (e) {
    var id = e.currentTarget.dataset.id
    wx.showModal({
      title: '确定要删除此储值规则吗？',
      content: '',
      success: function (res) {
        if (res.confirm) {
          wx.request({
            url: host + 'ssh_marketing.php?action=delete_recharge',
            data: {
              mch_id: wx.getStorageSync('mch_id'),
              id: id
            },
            header: {
              'content-type': 'application/json'
            },
            success: function (res) {
              wx.showToast({
                title: "操作成功",
                content: "",
                icon: 'success',
                duration: 2000,
                success: function (res) {
                  wx.navigateBack({
                    delta: 1
                  })
                }
              })
            }
          })
        } else if (res.cancel) {

        }
      }
    })
  },
  del_coupon: function (e) {
    var id = e.currentTarget.dataset.id
    wx.showModal({
      title: '确定要删除此优惠券吗？',
      content: '',
      success: function (res) {
        if (res.confirm) {
          wx.request({
            url: host + 'ssh_marketing.php?action=delete_recharge_coupon',
            data: {
              mch_id: wx.getStorageSync('mch_id'),
              id: id
            },
            header: {
              'content-type': 'application/json'
            },
            success: function (res) {
              wx.showToast({
                title: "操作成功",
                content: "",
                icon: 'success',
                duration: 2000,
                success: function (res) {
                  wx.navigateBack({
                    delta: 1
                  })
                }
              })
            }
          })
        } else if (res.cancel) {
        }
      }
    })
  },
  bindRuleChange: function (e) {
    var that = this
    var money_constant_display = 'none'
    var money_percent_display = 'none'
    var coupon_display = 'none'
    if ('0' == e.detail.value) {
      money_constant_display = ''
    } else if ('1' == e.detail.value) {
      money_percent_display = ''
    } else {
      coupon_display = ''
      wx.request({
        url: host + 'ssh_coupon.php?action=get_list',
        data: {
          mch_id: wx.getStorageSync('mch_id')
        },
        header: {
          'content-type': 'application/json'
        },
        success: function (res) {
          var ret = res.data
          for (var i = 0; i < ret['unenable'].length; i++) {
            ret['enable'][ret['enable'].length + i] = ret['unenable'][i]
          }
          that.setData({
            coupons: ret['enable'],
            couponIndex: 0
          })
        }
      })
    }
    this.setData({
      ruleIndex: e.detail.value,
      money_constant_display: money_constant_display,
      money_percent_display: money_percent_display,
      coupon_display: coupon_display
    })
  },
  bindCouponChange: function (e) {
    this.setData({
      couponIndex: e.detail.value
    })
  },
  bindCountChange: function (e) {
    this.setData({
      countIndex: e.detail.value
    })
  },
  back: function () {
    wx.navigateBack({
      delta: 1
    })
  }
})
