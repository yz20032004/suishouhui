// pages/marketing/member_day.js
const host = require('../../config').host
Page({

  /**
   * 页面的初始数据
   */
  data: {
    campaignTypes: [{
        type: '0',
        title: "请选择"
      },
      {
        type: 'point',
        title: "积分兑换活动"
      },
      {
        type: 'recharge',
        title: "储值活动"
      },
      {
        type: 'groupon',
        title: "超值抢购活动"
      }
    ],
    campaignTypeIndex: 0,
    campaignIndex: 0
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function(options) {
    var that = this
    wx.request({
      url: host + 'ssh_marketing.php?action=get_pay_result_recommend',
      data: {
        mch_id: wx.getStorageSync('mch_id')
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        that.setData({
          recommend:res.data
        })
      }
    })
  },

  /**
   * 生命周期函数--监听页面初次渲染完成
   */
  onReady: function() {},

  /**
   * 生命周期函数--监听页面显示
   */
  onShow: function() {

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
  submit: function(e) {
    var that = this
    var campaign_type = e.detail.value.campaign_type
    var campaign_id = e.detail.value.campaign_id
    if ('0' == this.data.campaignTypeIndex) {
      wx.showModal({
        title: "请设置推荐活动类型",
        content: "",
        showCancel: false,
        confirmText: "确定"
      })
      return false
    }
    wx.request({
      url: host + 'ssh_marketing.php?action=update_pay_result_recommend',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
        campaign_type: campaign_type,
        campaign_type_title:that.data.campaignTypes[that.data.campaignTypeIndex].title,
        campaign_id: campaign_id,
        title: that.data.campaigns[that.data.campaignIndex].title
      },
      header: {
        'content-type': 'application/json'
      },
      success: function(res) {
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
    })
  },
  bindCampaignTypeChange: function(e) {
    var that = this
    var campaignTypes = this.data.campaignTypes
    var campaignType = campaignTypes[e.detail.value].type
    this.setData({
      campaignTypeIndex: e.detail.value,
      campaignType: campaignType
    })
    wx.request({
      url: host + 'ssh_marketing.php?action=get_pay_result_campaigns',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
        campaign_type: campaignType
      },
      header: {
        'content-type': 'application/json'
      },
      success: function(res) {
        that.setData({
          campaigns: res.data,
          campaignIndex: 0
        })
      }
    })
  },
  bindCampaignChange: function(e) {
    this.setData({
      campaignIndex: e.detail.value
    })
  },
  back: function() {
    wx.navigateBack({
      delta: 1
    })
  }
})