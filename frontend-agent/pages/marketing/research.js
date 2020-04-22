// pages/marketing/member_day.js
const host = require('../../config').host
Page({

  /**
   * 页面的初始数据
   */
  data: {
    research_display: 'none',
    campaigns: [
      {
        type: 'none',
        title: "无奖励活动"
      },
      {
        type: 'point',
        title: "奖励积分"
      },
      {
        type: 'coupon',
        title: "奖励优惠券"
      }
    ],
    campaignIndex: 0,
    award_coupon_name:''
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function(options) {
    // 页面初始化 options为页面跳转所带来的参数
    var that = this
    wx.request({
      url: host + 'ssh_mch.php?action=get_research_config',
      data: {
        mch_id: wx.getStorageSync('mch_id')
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        if (!res.data) {
          var campaignIndex = 0
        } else if ('none' == res.data.award_type) {
          var campaignIndex = 0
        } else if ('point' == res.data.award_type) {
          var campaignIndex = 1
        } else if ('coupon' == res.data.award_type) {
          var campaignIndex = 2
        }
        that.setData({
          research_config:res.data,
          campaignIndex:campaignIndex,
          campaignType:res.data ? res.data.award_type : '',
          award_coupon_name:res.data.coupon_name,
          research_display:'1' == res.data.is_open ? '' : 'none' 
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
  submit: function (e) {
    var that = this
    var is_open = e.detail.value.is_open
    var formid = e.detail.value.formid
    var award_type = e.detail.value.award_type
    var award_value = e.detail.value.hasOwnProperty('award_value') ? e.detail.value.award_value : 0
    if (!formid) {
      wx.showModal({
        title: "请填写表单ID",
        content: "",
        showCancel: false,
        confirmText: "确定"
      })
      return false
    }
    if ('point' == award_type && award_value <= 0) {
      wx.showModal({
        title: "请填写奖励积分数额",
        content: "",
        showCancel: false,
        confirmText: "确定"
      })
      return false
    }
    if ('coupon' == award_type && !award_value) {
      wx.showModal({
        title: "请选择奖励优惠券",
        content: "",
        showCancel: false,
        confirmText: "确定"
      })
      return false
    }
    wx.request({
      url: host + 'ssh_mch.php?action=update_research_config',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
        is_open:is_open,
        formid:formid,
        award_type:award_type,
        award_value:award_value,
        coupon_name:that.data.award_coupon_name
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        wx.showToast({
          title: '操作成功',
          icon: 'success',
          duration: 2000,
          success: function () {
            wx.navigateBack({
              delta: 1
            })
          }
        })
      }
    })
  },
  exchangeOpenSwitch: function (e) {
    var isChecked = e.detail.value
    if (isChecked) {
      this.setData({
        research_display: ''
      })
    } else {
      this.setData({
        research_display: 'none'
      })
    }
  },
  bindCampaignChange: function(e) {
    var campaigns = this.data.campaigns
    this.setData({
      campaignIndex: e.detail.value,
      campaignType: campaigns[e.detail.value].type,
      award_coupon_name:''
    })
  },
  bindCouponChange: function (e) {
    var that = this
    this.setData({
      couponIndex: e.detail.value,
      award_coupon_name:that.data.coupons[e.detail.value].name
    })
  },
  getqrcode:function(){
    if (!this.data.research_config.is_open) {
      wx.showModal({
        title: "请先打开调研有礼功能",
        content: "",
        showCancel: false,
        confirmText: "确定"
      })
      return false
    }
    var that = this
    var merchant = wx.getStorageSync('mch')
    wx.request({
      url: host + 'ssh_mch.php?action=get_research_form_qrcode',
      data: {
        id: that.data.research_config.id,
        mch_id:merchant.mch_id,
        appid:merchant.appid
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        that.previewQrCode(res.data.qrcode_url)
      }
    })
  },
  previewQrCode:function(url){
    wx.previewImage({
      current: url,
      urls: [url]
    })
  },
  back: function () {
    wx.navigateBack({
      delta: 1
    })
  }
})