// pages/marketing/paygift.js
const host = require('../../config').host+'ssh_'
var myDate = new Date()
Page({

  /**
   * 页面的初始数据
   */
  data: {
    btn_disabled: false,
    counts: [3, 4, 5, 6, 7, 8, 9, 10],
    countIndex: 7,
    percents: [10, 20, 30, 40, 50, 60, 70, 80, 90, 100],
    couponIndex: 0,
    percentIndex: 4,
    percentAll:0,
    giftData:[],
    select_begin_date: myDate.toLocaleDateString(),
    select_end_date: myDate.getFullYear() + '-' + (myDate.getMonth() + 2) + '-' + myDate.getDate()
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function(options) {
    var that = this
    this.getGrades()
    wx.request({
      url: host + 'coupon.php?action=get_list',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
      },
      header: {
        'content-type': 'application/json'
      },
      success: function(res) {
        var ret = res.data
        for (var i = 0; i < ret['unenable'].length; i++) {
          ret['enable'][ret['enable'].length + i] = ret['unenable'][i]
        }
        ret['enable'][ret['enable'].length] = {
          id: 0,
          name: '请选择'
        }
        that.setData({
          coupons: ret['enable'],
          couponIndex: ret['enable'].length - 1,
        })
      }
    })
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
    // 页面显示
    var btn_disabled = false
    var disabled_title = ''
    var mch = wx.getStorageSync('mch')
    var myDate = new Date();
    var next_date = new Date((myDate / 1000 + 86400) * 1000)
    var end_date = new Date((myDate / 1000 + 86400 * 30) * 1000)
    this.setData({
      date: '请选择',
      time: '请选择',
      date_start: next_date.getFullYear() + '-' + (next_date.getMonth() + 1) + '-' + next_date.getDate(),
      date_end: end_date.getFullYear() + '-' + (end_date.getMonth() + 1) + '-' + end_date.getDate(),
    })
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
        //grades = res.data
        var grades = new Array
        grades[0] = { grade: 0, name: '所有会员' }
        for (var i = 1; i <= res.data.length; i++) {
          grades[i] = res.data[i - 1]
        }
        that.setData({
          grades: grades,
          gradeIndex: 0
        })
      }
    })
  },
  submit: function(e) {
    var that = this
    var grade   = e.detail.value.grade
    var consume = e.detail.value.consume
    var count = e.detail.value.count
    var date_start = e.detail.value.date_start
    var date_end = e.detail.value.date_end
    if (!consume) {
      wx.showToast({
        title: '请填写消费金额',
        icon: 'none'
      })
      return
    }
    var giftData = this.data.giftData
    if (giftData.length == 0) {
      wx.showToast({
        title: '请添加优惠券',
        icon: 'none'
      })
      return
    }
    if (this.data.percentAll != 100) {
      wx.showToast({
        title: '所有券的抽中机率之和应该为100',
        icon: 'none'
      })
      return
    }
    var coupon_string = encodeURIComponent(JSON.stringify(giftData))
    wx.request({
      url: host + 'marketing.php?action=create_payed_share',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
        grade:grade,
        consume: consume,
        count: count,
        coupons: coupon_string,
        date_start: date_start,
        date_end: date_end
      },
      header: {
        'content-type': 'application/json'
      },
      success: function(res) {
        if ('fail' == res.data) {
          wx.showModal({
            title: "该时段内已有分享有礼活动",
            content: "",
            showCancel: false,
            confirmText: "确定"
          })
          return false
        } else {
          wx.showToast({
            title: '操作成功',
            icon: 'success',
            duration: 2000,
            success: function() {
              wx.navigateBack({
                delta: 1
              })
            }
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
  add_coupon:function(){
    var that = this
    var couponIndex = this.data.couponIndex
    var coupon_id = this.data.coupons[couponIndex].id
    if ('0' == coupon_id) {
      wx.showModal({
        title: "请选择优惠券",
        content: "",
        showCancel: false,
        confirmText: "确定"
      })
      return false
    }
    var giftData = this.data.giftData
    var percentAll = this.data.percents[this.data.percentIndex]
    for (var i = 0; i < giftData.length; i++) {
      percentAll += giftData[i].percent
      if (giftData[i].id == coupon_id) {
        wx.showModal({
          title: '已经有此优惠券了',
          content: '',
          showCancel: false
        })
        return
      }
    }
    if (percentAll  >  100) {
      wx.showModal({
        title: '所有券的抽中机率之和应该为100%',
        content: '',
        showCancel: false
      })
      return
    }
    var newGift = {
      id: coupon_id,
      name: this.data.coupons[this.data.couponIndex].name,
      percent: this.data.percents[this.data.percentIndex]
    }
    giftData[giftData.length] = newGift
    this.setData({
      giftData: giftData,
      couponIndex: that.data.coupons.length - 1,
      percentIndex:4,
      percentAll:percentAll
    })
  },
  del: function (e) {
    var id = e.currentTarget.dataset.id
    var giftData = this.data.giftData
    giftData.splice(id, 1)
    this.setData({
      giftData: giftData
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
  previewImage: function (e) {
    var current = 'http://keyoucrmcard.oss-cn-hangzhou.aliyuncs.com/images/20191110/1573392157.jpeg'
    wx.previewImage({
      current: current,
      urls: [current]
    })
  },
  bindGradeChange: function (e) {
    this.setData({
      gradeIndex: e.detail.value
    })
  },
  bindCouponChange: function(e) {
    this.setData({
      couponIndex: e.detail.value
    })
  },
  bindCountChange: function(e) {
    this.setData({
      countIndex: e.detail.value
    })
  },
  bindPercentChange: function(e) {
    this.setData({
      percentIndex: e.detail.value
    })
  },
  back: function() {
    wx.navigateBack({
      delta: 1
    })
  }
})