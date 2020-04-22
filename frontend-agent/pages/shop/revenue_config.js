// pages/shop/revenue_config.js
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
    this.setData({
      merchant:wx.getStorageSync('mch')
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
  submit:function(e){
    var basic_fee_rate = e.detail.value.wechat_fee_rate
    var marketing_fee_rate = e.detail.value.marketing_fee_rate
    if (basic_fee_rate < 0.2) {
      wx.showModal({
        title: "基础支付费率不能低于0.2%",
        content: "",
        showCancel: false,
        confirmText: "确定"
      })
      return false
    }
    if (basic_fee_rate > 0.6) {
      wx.showModal({
        title: "基础支付费率不能高于0.6%",
        content: "",
        showCancel: false,
        confirmText: "确定"
      })
      return false
    }
    if (marketing_fee_rate < basic_fee_rate) {
      wx.showModal({
        title: "营销支付费率不能低于基础支付费率",
        content: "",
        showCancel: false,
        confirmText: "确定"
      })
      return false
    } else if (marketing_fee_rate > 30) {
      wx.showModal({
        title: "营销支付费率不能高于30%",
        content: "",
        showCancel: false,
        confirmText: "确定"
      })
      return false
    }
    wx.request({
      url: host + 'tt_mch.php?action=update_fee_rate',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
        basic_fee_rate: basic_fee_rate,
        marketing_fee_rate: marketing_fee_rate
      },
      header: {
        'content-type': 'application/json'
      },
      success: function(res) {
        wx.setStorageSync('mch', res.data)
        wx.showModal({
          title: '操作成功',
          content: '新的费率将于明日更新',
          showCancel:false
        })
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
  back: function () {
    wx.navigateBack({
      delta: 1
    })
  }
})