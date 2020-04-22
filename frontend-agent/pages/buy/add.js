// pages/buy/add.js
const host = require('../../config').host + 'ssh_'
var myDate = new Date()
Page({

  /**
   * 页面的初始数据
   */
  data: {
    distribute_display:'none',
    display: 'none',
    amount_display: 'none',
    amount:0,
    single_limit: '不限制',
    coupon_totals: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10,15,20,25,30,35,40,45,50],
    totalIndex: 0,
    coupon_total:'1',
    counts: ['不限制', 1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
    countIndex: 0,
    select_begin_date: myDate.toLocaleDateString(),
    select_end_date: (myDate.getFullYear() + 1) + '-' + (myDate.getMonth() + 1) + '-' + myDate.getDate()
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    var coupon_type = options.coupon_type
    this.setData({
      coupon_type:coupon_type,
      coupon_title: 'groupon' == coupon_type ? '团购券' : '次卡券',
      coupon_unit : 'groupon' == coupon_type ? '张' : '次'
    })
    var that = this
    wx.request({
      url: host + 'groupon.php?action=get_enable_coupons',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
        coupon_type:coupon_type
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        var length = res.data.length
        res.data[length] = { id: 0, name: '请选择' }
        that.setData({
          coupons: res.data,
          couponIndex: res.data.length - 1
        })
        that.getGrades()
      }
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
    // 页面显示
    var mch = wx.getStorageSync('mch')
    var myDate = new Date();
    var next_date = new Date((myDate / 1000 + 86400) * 1000)
    var end_date = new Date((myDate / 1000 + 86400 * 30) * 1000)
    this.setData({
      date: '请选择',
      time: '请选择',
      date_start: next_date.getFullYear() + '-' + (next_date.getMonth() + 1) + '-' + next_date.getDate(),
      date_end: end_date.getFullYear() + '-' + (end_date.getMonth() + 1) + '-' + end_date.getDate(),
      merchant:wx.getStorageSync('mch')
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

  getGrades:function(){
    var that = this
    wx.request({
      url: host + 'mch.php?action=get_grades',
      data: {
        mch_id: wx.getStorageSync('mch_id')
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        that.setData({
          grades: res.data,
          gradeIndex: 0,
        })
      }
    })
  },
  submit:function(e){
    var coupon_id = e.detail.value.coupon_id
    var coupon_total = e.detail.value.coupon_total
    var amount    = e.detail.value.amount
    var price     = e.detail.value.price
    var is_limit  = e.detail.value.is_limit
    var total_limit = e.detail.value.total_limit
    var single_limit = this.data.countIndex != 0 ? e.detail.value.single_limit : 0
    var is_member_limit = e.detail.value.hasOwnProperty('is_member_limit') ? e.detail.value.is_member_limit : 0
    var is_distribute = e.detail.value.hasOwnProperty('is_distribute') ? e.detail.value.is_distribute : 0
    var grade = e.detail.value.hasOwnProperty('grade') ? e.detail.value.grade : 0
    var bonus = e.detail.value.hasOwnProperty('bonus') ? e.detail.value.bonus : 0
    var date_start = e.detail.value.date_start
    var date_end   = e.detail.value.date_end
    if ('0' == coupon_id) {
      wx.showToast({
        title: '请选择一张券',
        icon:'none',
        duration: 2000
      })
      return false
    }
    if (!price) {
      wx.showToast({
        title: '请填写售卖价格',
        icon: 'none',
        duration: 2000
      })
      return false
    }
    if (parseInt(price) >= parseInt(amount)) {
      wx.showToast({
        title: '售卖价格必须低于原价',
        icon: 'none',
        duration: 2000
      })
      return false
    }
    if (is_limit && !total_limit) {
      wx.showToast({
        title: '请填写限购总份数',
        icon: 'none',
        duration: 2000
      })
      return false
    }
    if (is_distribute) {
      if (!bonus) {
        wx.showToast({
          title: '请填写分销提成金额',
          icon: 'none',
          duration: 2000
        })
        return false
      }
      var d = bonus / price
      if (d > 0.3) {
        wx.showToast({
          title: '分销提成金额不能高于抢购价的30%',
          icon: 'none',
          duration: 2000
        })
        return false
      }
    }
    
    var coupon_name = this.data.coupons[this.data.couponIndex].name
    var coupon_type = this.data.coupon_type
    wx.request({
      url: host + 'groupon.php?action=create',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
        coupon_id:coupon_id,
        coupon_type:coupon_type,
        coupon_total:coupon_total,
        coupon_name:coupon_name,
        amount:amount,
        price:price,
        is_limit: is_limit ? 1 : 0,
        total_limit: total_limit,
        single_limit: single_limit,
        is_member_limit: is_member_limit,
        is_distribute:is_distribute,
        grade:grade,
        bonus:bonus,
        date_start:date_start,
        date_end:date_end
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        if ('fail' == res.data) {
          wx.showToast({
            title: '已经有此活动了',
            icon:'none',
            duration: 2000
          })
          return false
        } else {
          wx.showToast({
            title: '创建成功',
            icon:'success',
            duration: 2000,
            success(res){
              wx.navigateBack({
                url:'list?coupon_type='+coupon_type
              })
            }
          })
        }
      }
    })
  },
  bindGradeChange: function (e) {
    this.setData({
      gradeIndex: e.detail.value
    })
  },
  bindDateStartChange: function (e) {
    this.setData({
      date_start: e.detail.value
    })
  },
  bindDateEndChange: function (e) {
    this.setData({
      date_end: e.detail.value
    })
  },
  bindCouponChange: function (e) {
    var that = this
    var single_amount = that.data.coupons[e.detail.value].amount
    this.setData({
      couponIndex: e.detail.value,
      single_amount:single_amount,
      amount_display:'',
      amount: that.data.coupon_totals[that.data.totalIndex] * single_amount
    })
  },
  bindCouponTotalChange: function (e) {
    var that = this
    var coupon_unit = this.data.coupon_unit
    this.setData({
      totalIndex: e.detail.value,
      coupon_total: that.data.coupon_totals[e.detail.value],
      amount: that.data.coupon_totals[e.detail.value] * that.data.single_amount
    })
  },
  bindCountChange: function (e) {
    var that = this
    var single_limit = that.data.counts[e.detail.value] + '份'
    if (e.detail.value == 0) {
      var single_limit = '不限制'
    }
    this.setData({
      countIndex: e.detail.value,
      single_limit: single_limit
    })
  },
  limitSwitch: function (e) {
    var isChecked = e.detail.value
    if (isChecked) {
      this.setData({
        display: ''
      })
    } else {
      this.setData({
        display: 'none',
      })
    }
  },
  distributeSwitch: function (e) {
    var isChecked = e.detail.value
    if (isChecked) {
      this.setData({
        distribute_display: ''
      })
    } else {
      this.setData({
        distribute_display: 'none',
      })
    }
  },
  back: function () {
    wx.navigateBack({
      delta: 1
    })
  }
})
