// pages/marketing/paygift.js
const host = require('../../config').host
var myDate = new Date()
Page({

  /**
   * 页面的初始数据
   */
  data: {
    btn_disabled:false,
    couponIndex: 0,
    coupon_total:1,
    bindsubmit:'submit',
    gift_coupons:new Array()
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
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
      date_start: '请选择',
      date_end: '请选择',
      select_begin_date: next_date.getFullYear() + '-' + (next_date.getMonth() + 1) + '-' + next_date.getDate(),
      select_end_date: end_date.getFullYear() + '-' + (end_date.getMonth() + 1) + '-' + end_date.getDate(),
      btn_disabled: mch.mch_type == 'xiaowei' ? true : false
    })
    var that = this
    wx.request({
      url: host + 'ssh_coupon.php?action=get_list',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
        get_type: 'cash'
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
    that.get_payed_gifts()
    that.get_payed_gift_coupons()
  },
  get_payed_gifts:function(){
    var that = this
    wx.request({
      url: host + 'ssh_marketing.php?action=get_payed_gifts',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        that.setData({
          payed_gifts: res.data
        })
      }
    })
  },
  get_payed_gift_coupons: function () {
    var that = this
    wx.request({
      url: host + 'ssh_marketing.php?action=get_payed_gift_coupons',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        that.setData({
          gift_coupons: res.data,
          bindsubmit: res.data.length > 0 ? 'add_coupon' : 'submit'
        })
      }
    })
  },
  submit:function(e){
    if (this.data.gifts && this.data.gifts.length >= 5) {
      wx.showModal({
        title: '温馨提示',
        content: '最多只能添加5张代金券',
        showCancel: false
      })
      return
    }
    var that = this
    var consume = e.detail.value.consume
    var coupon_id = e.detail.value.coupon_id
    var date_start = e.detail.value.date_start
    var date_end = e.detail.value.date_end
    if (!consume) {
      wx.showToast({
        title: '请填写消费金额',
        icon:'none'
      })
      return
    }
    if (!coupon_id) {
      wx.showToast({
        title: '请选择优惠券',
        icon: 'none'
      })
      return
    }
    if ('请选择' == date_start) {
      wx.showToast({
        title: '请填写活动开始日期',
        icon: 'none'
      })
      return
    }
    if ('请选择' == date_end) {
      wx.showToast({
        title: '请填写活动结束日期',
        icon: 'none'
      })
      return
    }
    var startDate = new Date(Date.parse(date_start))
    var endDate = new Date(Date.parse(date_end))
    if (startDate > endDate) {
      wx.showModal({
        title: "活动开始日期不能大于结束日期",
        content: "",
        showCancel: false,
        confirmText: "确定"
      })
      return false
    }
    wx.request({
      url: host + 'ssh_marketing.php?action=create_payed_gift',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
        consume:consume,
        coupon_id:coupon_id,
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
            title: "已有此代金券了",
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
            success: function () {
              that.get_payed_gift_coupons()
            }
          })
        }
      }
    })
  },
  add_coupon:function(e){
    var that = this
    var coupon_id = e.detail.value.coupon_id
    wx.request({
      url: host + 'ssh_marketing.php?action=add_payed_gift_coupon',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
        coupon_id: coupon_id,
        coupon_name: that.data.coupons[that.data.couponIndex].name,
        consume:that.data.payed_gifts.consume
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        if ('success' == res.data) {
          wx.showToast({
            title: '操作成功',
            icon: 'success',
            duration: 2000,
            success: function () {
              that.get_payed_gift_coupons()
            }
          })
        } else {
          wx.showModal({
            title: "已有此代金券了",
            content: "",
            showCancel: false,
            confirmText: "确定"
          })
        }
      }
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
  del: function (e) {
    var that = this
    var coupon_id = e.currentTarget.dataset.id
    wx.showModal({
      title: '确定要删除该券吗？',
      success(res) {
        if (res.confirm) {
          wx.request({
            url: host + 'ssh_marketing.php?action=delete_payed_gift',
            data: {
              mch_id: wx.getStorageSync('mch_id'),
              coupon_id: coupon_id
            },
            header: {
              'content-type': 'application/json'
            },
            success: function (res) {
              that.get_payed_gift_coupons()
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
  previewPayGiftImage: function (e) {
    var current = 'https://keyoucrmcard.oss-cn-hangzhou.aliyuncs.com/xiaowei/20190506/paygiftdemo.png'
    wx.previewImage({
      current: current,
      urls: [current]
    })
  },
  back: function () {
    wx.navigateBack({
      delta: 1
    })
  }
})