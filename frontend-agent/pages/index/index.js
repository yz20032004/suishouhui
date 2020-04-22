//index.js
//获取应用实例
const app = getApp()
const host = require('../../config').host
Page({
  data: {
    page: 1,
    // 总页数
    totalPage: 0,
    pageCount: 20,
    mch_type: 'xiaowei',
    xiaowei_back_color: 'green',
    xiaowei_color: 'white',
    teyue_back_color: 'white',
    teyue_color: 'green',
    general_back_color: 'white',
    general_color: 'green'
  },
  onLoad: function() {
    var that = this
    this.data.interval = setInterval(
      function() {
        if (wx.getStorageSync('openid')) {
          clearInterval(that.data.interval)
          wx.request({
            url: host + 'tt_user.php?action=get_detail',
            data: {
              openid: wx.getStorageSync('openid')
            },
            header: {
              'content-type': 'application/json'
            },
            success: function(res) {
              if (res.data) {
                wx.setStorageSync('user', res.data)
                wx.setStorageSync('uid', res.data.id)
                wx.setStorageSync('is_leader', res.data.is_leader)
                that.get_list()
              } else {
                wx.redirectTo({
                  url: 'register',
                })
              }
            }
          })
        }
      }, 200);
  },
  get_list: function() {
    var that = this
    wx.request({
      url: host + 'tt_user.php?action=get_mch_list',
      data: {
        uid: wx.getStorageSync('uid'),
        mch_type: that.data.mch_type,
        page: that.data.page,
        page_count: that.data.pageCount
      },
      header: {
        'content-type': 'application/json'
      },
      success: function(res) {
        that.setData({
          list: res.data.list,
          totalPage: res.data.page_total,
          total: res.data.total
        })
      }
    })
  },
  showMerchantList: function(e) {
    var mch_type = e.currentTarget.dataset.type
    if (this.data.mch_type == mch_type) {
      return
    }
    if ('xiaowei' == mch_type) {
      var xiaowei_back_color = 'green'
      var xiaowei_color = 'white'
      var teyue_back_color = 'white'
      var teyue_color = 'green'
      var general_back_color = 'white'
      var general_color = 'green'
    } else if ('teyue' == mch_type) {
      var xiaowei_back_color = 'white'
      var xiaowei_color = 'green'
      var teyue_back_color = 'green'
      var teyue_color = 'white'
      var general_back_color = 'white'
      var general_color = 'green'
    } else {
      var xiaowei_back_color = 'white'
      var xiaowei_color = 'green'
      var teyue_back_color = 'white'
      var teyue_color = 'green'
      var general_back_color = 'green'
      var general_color = 'white'
    }
    this.setData({
      mch_type: mch_type,
      xiaowei_back_color: xiaowei_back_color,
      xiaowei_color: xiaowei_color,
      teyue_back_color: teyue_back_color,
      teyue_color: teyue_color,
      general_back_color: general_back_color,
      general_color: general_color
    })
    this.get_list()
  },
  expand: function() {
    /*var user = wx.getStorageSync('user')
    if ('xiaowei' == this.data.mch_type) {
      wx.showModal({
        title: '微信已停止对小微商户的申请',
        showCancel:false
      })
      return
      wx.navigateTo({
        url: '../expand/xiaowei_readme',
      })
    } else if ('teyue' == this.data.mch_type) {
      wx.navigateTo({
        url: '../expand/teyue_readme',
      })
    } else {*/
      wx.navigateTo({
        url: '../expand/general_readme',
      })
    //}
  },
  preview_shop: function(e) {
    var mch_id = e.currentTarget.dataset.id
    wx.navigateTo({
      url: 'shop_dashboard?mch_id=' + mch_id,
    })
  },
  onReachBottom: function() {
    var that = this;
    // 当前页+1
    var page = that.data.page + 1;
    if (page <= that.data.totalPage) {
      wx.showLoading({
        title: '加载中',
      })
      // 请求后台，获取下一页的数据。
      wx.request({
        url: host + 'tt_user.php?action=get_mch_list',
        data: {
          openid: wx.getStorageSync('openid'),
          mch_type: that.data.mch_type,
          page_count: that.data.pageCount,
          page: page
        },
        success: function(res) {
          wx.hideLoading()
          wx.stopPullDownRefresh()
          // 将新获取的数据 res.data.list，concat到前台显示的showlist中即可。
          that.setData({
            list: that.data.list.concat(res.data.list)
          })
        },
        fail: function(res) {
          wx.hideLoading()
        }
      })
    } else {
      var page = that.data.page;
    }
    that.setData({
      page: page,
    })
  }
})