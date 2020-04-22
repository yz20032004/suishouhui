// pages/shop/ability.js
const host = require('../../config').host
Page({

  /**
   * 页面的初始数据
   */
  data: {

  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    var mch = wx.getStorageSync('mch')
    var marketing_type = mch.marketing_type
    marketing_type = 'marketing'
    this.setData({
      mch:mch,
      marketing_type:marketing_type,
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
  submit:function(e){
    var is_groupon = e.detail.value.is_groupon ? 1 : 0
    var is_distribute = e.detail.value.is_distribute ? 1 : 0
    if (e.detail.value.hasOwnProperty('is_together')) {
      var is_together   = e.detail.value.is_together ? 1 : 0
    } else {
      var is_together = 0
    }
    var is_payed_share = e.detail.value.is_payed_share ? 1 : 0
    var is_pay_gift = e.detail.value.is_pay_gift ? 1 : 0
    var is_rechargenopay = e.detail.value.is_rechargenopay ? 1 : 0
    var is_sharecoupon = e.detail.value.is_sharecoupon ? 1 : 0
    var marketing_type = this.data.marketing_type
    var is_waimai = e.detail.value.is_waimai ? 1 : 0
    var is_reduce = e.detail.value.is_reduce ? 1 : 0
    var is_ordering = e.detail.value.is_ordering ? 1 : 0
    var is_grade = e.detail.value.is_grade ? 1 : 0
    var is_mall = e.detail.value.is_mall ? 1 : 0
    var is_wechatgroup = e.detail.value.is_wechatgroup ? 1 : 0
    if ('marketing' == marketing_type) {
      var is_recharge = e.detail.value.is_recharge ? 1 : 0
      var is_vipcard = e.detail.value.is_vipcard ? 1 : 0
      var is_wakeup = e.detail.value.is_wakeup ? 1 : 0
      var is_memberday = e.detail.value.is_memberday ? 1 : 0
      var is_timing = e.detail.value.is_timing ? 1 : 0
      var is_paybuycoupon = e.detail.value.is_paybuycoupon ? 1 : 0
    } else {
      var is_recharge = 0
      var is_vipcard = 0
      var is_wakeup  = 0
      var is_memberday = 0
      var is_timing = 0
      var is_paybuycoupon = 0
    }
    var user = wx.getStorageSync('user')
    if ('pay' == user.agent_type && 'marketing' == marketing_type) {
      /*wx.showModal({
        title: '无权限操作',
        content: '您的级别不可以添加会员营销功能',
        showCancel:false
      })
      return*/
    }
    wx.request({
      url: host + 'ssh_mch.php?action=update_functions',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
        is_waimai: is_waimai,
        is_reduce: is_reduce,
        is_ordering:is_ordering,
        is_grade:is_grade,
        is_groupon:is_groupon,
        is_distribute:is_distribute,
        is_together:is_together,
        is_payed_share:is_payed_share,
        is_pay_gift: is_pay_gift,
        is_recharge:is_recharge,
        is_rechargenopay: is_rechargenopay,
        is_vipcard:is_vipcard,
        is_wakeup:is_wakeup,
        is_memberday:is_memberday,
        is_sharecoupon:is_sharecoupon,
        is_paybuycoupon:is_paybuycoupon,
        is_timing:is_timing,
        is_mall:is_mall,
        is_wechatgroup:is_wechatgroup,
        marketing_type:marketing_type
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        wx.setStorageSync('mch', res.data)
        wx.showModal({
          title: '操作成功',
          content: '商户功能已更新',
          showCancel:false
        })
      }
    })
  },
  exchangeMarketingTypeSwitch:function(e){
    var isChecked = e.detail.value
    if (isChecked) {
      this.setData({
        marketing_type: 'marketing'
      })
    } else {
      this.setData({
        marketing_type: 'pay'
      })
    }
  },
  back: function () {
    wx.navigateBack({
      delta: 1
    })
  }
})
