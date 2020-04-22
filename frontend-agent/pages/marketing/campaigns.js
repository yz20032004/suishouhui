const host = require('../../config').host
//index.js
//获取应用实例
const app = getApp()

Page({
  data: {
    new_active: 'primary',
    right_active: 'default',
    open_active: 'default',
    recharge_active: 'default',
    campaign_active: 'default',
    marketing_active: 'default',
    cate: 0,
    campaigns: '',
    refreshs: 0,
    nomore: false
  },
  onload: function (options) {
  },
  onReady: function () {
    this.setData({
      openid: wx.getStorageSync('openid')
    })
    var that = this
    wx.request({
      url: host + 'ssh_campaigns.php?action=get_index',
      data: {},
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        that.setData({
          campaigns: res.data,
        })
      }
    })
  },
  getlist: function (e) {
    this.data.refreshs = 0
    this.data.nomore = false
    var cate = e.target.dataset.cate
    this.data.new_active = 'default'
    this.data.right_active = 'default'
    this.data.open_active = 'default'
    this.data.recharge_active = 'default'
    this.data.campaign_active = 'default'
    this.data.marketing_active = 'default'
    if (0 == cate) {
      this.data.new_active = 'primary'
    } else if ('1' == cate) {
      this.data.right_active = 'primary'
    } else if ('2' == cate) {
      this.data.open_active = 'primary'
    } else if ('3' == cate) {
      this.data.recharge_active = 'primary'
    } else if ('4' == cate) {
      this.data.campaign_active = 'primary'
    } else if ('5' == cate) {
      this.data.marketing_active = 'primary'
    }
    this.data.cate = cate
    var that = this
    wx.request({
      url: host + 'ssh_campaigns.php?action=get_index',
      data: {
        cate: cate,
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        that.setData({
          campaigns: res.data,
          new_active: that.data.new_active,
          right_active: that.data.right_active,
          open_active: that.data.open_active,
          recharge_active: that.data.recharge_active,
          campaign_active: that.data.campaign_active,
          marketing_active: that.data.marketing_active,
        })
      }
    })
  },
  previewDetail: function (e) {
    var id = e.target.dataset.id
    wx.navigateTo({ url: 'campaigns_detail?id=' + id })
  },
  onReachBottom: function () {
    this.data.refreshs++;
    wx.showToast({
      title: '请稍后...',
      icon: 'loading'
    })
    var that = this
    wx.request({
      url: host + 'ssh_campaigns.php?action=get_index',
      data: {
        refresh: that.data.refreshs,
        cate: that.data.cate
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        if (res.data.length == 0) {
          that.data.nomore = true
          wx.showModal({
            title: "",
            content: "没有更多了",
            showCancel: false,
            confirmText: "确定"
          })
        }
        wx.hideToast()
        for (var i = 0; i < res.data.length; i++) {
          that.data.campaigns.push(res.data[i])
        }
        that.setData({
          campaigns: that.data.campaigns
        })
      }
    })
  }
})