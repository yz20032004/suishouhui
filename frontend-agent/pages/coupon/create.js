// pages/coupon/create.js
const host = require('../../config').host + 'ssh_'
var myDate = new Date()
Page({
  data: {
    expireTypes: ["自领取起后多少天", "从某一天到某一天"],
    expireTypeIndex: 0,
    hard_display: '',
    relative_display: 'none',
    discount_display: 'none',
    date_start: '请选择',
    date_end: '请选择',
    name_title: '',
    select_begin_date: myDate.toLocaleDateString(),
    select_end_date: (myDate.getFullYear()+1) + '-' + (myDate.getMonth() + 1) + '-' + myDate.getDate()
  },
  onLoad: function(options) {
    var type = options.type
    var coupon_title = ''
    if ('cash' == type) {
      coupon_title = '代金券'
    } else if ('gift' == type) {
      coupon_title = '礼品券'
    } else if ('discount' == type) {
      coupon_title = '折扣券'
    } else if ('groupon' == type) {
      coupon_title = '团购券';
    } else {
      coupon_title = '次卡券'
    }
    var merchant = wx.getStorageSync('mch')
    this.setData({
      coupon_type: type,
      amount_placeholder: 'cash' == type || 'groupon' == type || 'timing' == type ? '请填写金额' : '请填写金额，也可以不填',
      name_display: 'cash' == type ? 'none' : '',
      discount_display: 'discount' == type ? '' : 'none',
      amount_display: 'discount' == type ? 'none' : '',
      coupon_title: coupon_title,
      is_waimai:merchant.is_waimai,
      is_mall:merchant.is_mall
    })
    // 页面初始化 options为页面跳转所带来的参数

  },
  onReady: function() {
    // 页面渲染完成
  },
  onShow: function() {
    // 页面显示
   
  },
  onHide: function() {
    // 页面隐藏
  },
  onUnload: function() {
    // 页面关闭
  },
  bindExpireTypeChange: function(e) {
    if ('1' == e.detail.value) {
      var hard_display = 'none'
      var relative_display = ''
    } else {
      var hard_display = ''
      var relative_display = 'none'
    }
    this.setData({
      expireTypeIndex: e.detail.value,
      hard_display: hard_display,
      relative_display: relative_display
    })
  },
  bindDateStartChange: function(e) {
    this.setData({
      date_start: e.detail.value
    })
  },
  bindDateEndChange: function(e) {
    this.setData({
      date_end: e.detail.value
    })
  },
  submit: function(e) {
    if ('admin' != wx.getStorageSync('user_role')) {
      wx.showModal({
        title: '温馨提示',
        content: '体验者或非管理员身份不可执行此操作',
        showCancel: false
      })
      return
    }
    var coupon_type = e.detail.value.type
    var discount = e.detail.value.discount
    var name = e.detail.value.name
    var amount = e.detail.value.amount
    var consume_limit = e.detail.value.consume_limit
    var is_usefully_sendday = e.detail.value.is_usefully_sendday
    var is_single = e.detail.value.is_single
    var validity_type = e.detail.value.validity_type
    var date_start = e.detail.value.date_start
    var date_end = e.detail.value.date_end
    var description = e.detail.value.description
    var total_days = e.detail.value.total_days
    var balance    = e.detail.value.balance
    var deal_detail = e.detail.value.deal_detail
    if (('cash' != coupon_type && 'waimai' != coupon_type && 'mall' != coupon_type) && !name) {
      wx.showModal({
        title: "请填写券的名称",
        content: "",
        showCancel: false,
        confirmText: "确定"
      })
      return false
    }
    if (name.length > 9) {
      wx.showModal({
        title: "券名称不能超过9个汉字",
        content: "",
        showCancel: false,
        confirmText: "确定"
      })
      return false
    }
    if (('cash' == coupon_type || 'waimai' == coupon_type || 'mall' == coupon_type || 'groupon' == coupon_type || 'timing' == coupon_type) && !amount) {
      wx.showModal({
        title: "请填写券金额",
        content: "",
        showCancel: false,
        confirmText: "确定"
      })
      return false
    }
    if ('discount' == coupon_type) {
      if (!discount) {
        wx.showModal({
          title: "请填写券折扣大小",
          content: "",
          showCancel: false,
          confirmText: "确定"
        })
        return false
      } else if (isNaN(discount)) {
        wx.showModal({
          title: "请填写数字",
          content: "",
          showCancel: false,
          confirmText: "确定"
        })
        return false
      } else if (discount >= 9.9 || discount < 1) {
        wx.showModal({
          title: "折扣数字格式不正确",
          content: "",
          showCancel: false,
          confirmText: "确定"
        })
        return false
      }
    }
    if ('0' == validity_type) {
      if (!total_days) {
        wx.showModal({
          title: "请填写券有效天数",
          content: "",
          showCancel: false,
          confirmText: "确定"
        })
        return false
      }
    } else {
      if ('请选择' == date_start) {
        wx.showModal({
          title: "请填写券起用日期",
          content: "",
          showCancel: false,
          confirmText: "确定"
        })
        return false
      }
      if ('请选择' == date_end) {
        wx.showModal({
          title: "请填写券结束日期",
          content: "",
          showCancel: false,
          confirmText: "确定"
        })
        return false
      }
    }
    if (!balance) {
      wx.showModal({
        title: "请填写券库存",
        content: "",
        showCancel: false,
        confirmText: "确定"
      })
      return false
    }
    if (balance > 10000) {
      wx.showModal({
        title: "券库存不能超过1万张",
        content: "",
        showCancel: false,
        confirmText: "确定"
      })
      return false
    }
    if ('groupon' == coupon_type && !deal_detail) {
      wx.showModal({
        title: "团购券详情内容必填",
        content: "",
        showCancel: false,
        confirmText: "确定"
      })
      return false
    }
    if ('timing' == coupon_type && !deal_detail) {
      wx.showModal({
        title: "次卡券详情内容必填",
        content: "",
        showCancel: false,
        confirmText: "确定"
      })
      return false
    }
    wx.request({
      url: host + 'coupon.php?action=create',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
        type: coupon_type,
        discount: discount,
        name: name,
        amount: amount,
        consume_limit: consume_limit,
        is_usefully_sendday: true === is_usefully_sendday ? '1' : '0',
        is_single: is_single,
        validity_type: '1' == validity_type ? 'hard' : 'relative',
        date_start: date_start,
        date_end: date_end,
        description: description,
        deal_detail:deal_detail,
        total_days: total_days,
        balance:balance
      },
      header: {
        'content-type': 'application/json'
      },
      success: function(res) {
        var coupon_id = res.data
        wx.showModal({
          title: "创建成功",
          content: "是否为该优惠券添加图片？",
          showCancel: true,
          confirmText: "去添加",
          success: function(res) {
            if (res.confirm) {
              wx.navigateTo({
                url: 'add_pic?id=' + coupon_id + '&name='+name,
              })
            } else {
              wx.navigateTo({
                url: 'preview?id=' + coupon_id,
              })
            }
          }
        })
      }
    })
  },
  copy_discount: function(e) {
    var discount = e.detail.value
    if (isNaN(discount)) {
      wx.showModal({
        title: "请填写数字",
        content: "",
        showCancel: false,
        confirmText: "确定"
      })
      return ''
    } else if (discount >= 100 || discount < 1) {
      wx.showModal({
        title: "折扣数字格式不正确",
        content: "",
        showCancel: false,
        confirmText: "确定"
      })
      return ''
    }
    this.setData({
      name_title: discount + '折扣券'
    })
  },
  back: function() {
    wx.navigateBack({
      delta: 1
    })
  }
})
