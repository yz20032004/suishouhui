const host = require('../../config').host
var myDate = new Date()
Page({

  /**
   * 页面的初始数据
   */
  data: {
    counts: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
    countIndex: 0,
    select_begin_date: myDate.toLocaleDateString(),
    select_end_date: myDate.getFullYear() + '-' + (myDate.getMonth() + 2) + '-' + myDate.getDate()
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    var that = this
    wx.request({
      url: host + 'coupon.php?action=get_list',
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
        ret['enable'][ret['enable'].length] = { id: 0, name: '请选择' }
        that.setData({
          coupons: ret['enable'],
          couponIndex: ret['enable'].length - 1
        })
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

  /**
   * 页面相关事件处理函数--监听用户下拉动作
   */
  onPullDownRefresh: function () {

  },

  /**
   * 页面上拉触底事件的处理函数
   */
  onReachBottom: function () {

  },

  /**
   * 页面上拉触底事件的处理函数
   */
  onReachBottom: function () {

  },

  /**
   * 页面相关事件处理函数--监听用户下拉动作
   */
  onPullDownRefresh: function () {

  },
  submit: function (e) {
    if ('admin' != wx.getStorageSync('user_role')) {
      wx.showModal({
        title: '温馨提示',
        content: '体验者或非管理员身份不可执行此操作',
        showCancel: false
      })
      return
    }
    var that = this
    var consume = e.detail.value.consume
    var amount = e.detail.value.amount
    var coupon_id = e.detail.value.coupon_id
    var coupon_total = e.detail.value.count
    var date_start = e.detail.value.date_start
    var date_end = e.detail.value.date_end
    if (!consume) {
      wx.showModal({
        title: "请填写顾客参与活动的最低买单金额",
        content: "",
        showCancel: false,
        confirmText: "确定"
      })
      return false
    }
    if ('0' == coupon_id) {
      wx.showModal({
        title: "请选择优惠券",
        content: "",
        showCancel: false,
        confirmText: "确定"
      })
      return false
    }
    if (!amount) {
      wx.showModal({
        title: "请填写加价金额",
        content: "",
        showCancel: false,
        confirmText: "确定"
      })
      return false
    }
    wx.request({
      url: host + 'marketing.php?action=create_paybuycoupon',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
        consume:consume,
        amount: amount,
        coupon_id: coupon_id,
        coupon_total:coupon_total,
        coupon_name:that.data.coupons[that.data.couponIndex].name,
        date_start: date_start,
        date_end: date_end
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        if ('fail' == res.data) {
          wx.showModal({
            title: "已有此活动了",
            content: "",
            showCancel: false,
            confirmText: "确定"
          })
          return false
        } else {
          wx.showModal({
            title: '创建成功',
            content: "",
            showCancel: false,
            success: function () {
              wx.navigateBack({ url: '../campaign/list' })
            }
          })
        }
      }
    })
  },
  bindCouponChange: function (e) {
    this.setData({
      couponIndex: e.detail.value
    })
  },
  bindCountChange: function (e) {
    this.setData({
      countIndex: e.detail.value,
    })
  },
  previewDemoImage:function(){
    var current = 'https://keyoucrmcard.oss-cn-hangzhou.aliyuncs.com/xiaowei/20200107/WechatIMG410.png'
    wx.previewImage({
      current: current,
      urls: [current]
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
  back: function () {
    wx.navigateBack({
      delta: 1
    })
  }
})
