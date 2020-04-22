// pages/coupon/list.js
const host = require('../../config').host
var sliderWidth = 96; // 需要设置slider的宽度，用于计算中间位置
Page({

  /**
   * 页面的初始数据
   */
  data: {
    tabs: ["可用卡券", "未生效", "已过期", "已使用"],
    activeIndex: 0,
    sliderOffset: 0,
    sliderLeft: 0,
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function(options) {
    wx.showLoading({
      title: '加载中',
    })
    var that = this
    var give_mchid = options.hasOwnProperty('mch_id')
    this.data.interval = setInterval(
      function () {
        if (wx.getStorageSync('is_load_member')) {
          clearInterval(that.data.interval)
          if (wx.getStorageSync('mch_id')) {
            that.get_list()
          } else if (give_mchid) {
            wx.navigateTo({
              url: '../index/get_membercard?mch_id=' + options.mch_id,
            })
          } else {
            wx.redirectTo({
              url: '../index/no_shop',
            })
          }
        }
      }, 200);
  },
  onShow:function(){
    var that = this
    wx.getSystemInfo({
      success: function (res) {
        that.setData({
          sliderLeft: (res.windowWidth / that.data.tabs.length - sliderWidth) / 2,
          sliderOffset: res.windowWidth / that.data.tabs.length * that.data.activeIndex
        });
      }
    });
  },
  tabClick: function (e) {
    this.setData({
      sliderOffset: e.currentTarget.offsetLeft,
      activeIndex: e.currentTarget.id
    });
  },
  /**
   * 生命周期函数--监听页面初次渲染完成
   */
  onReady: function() {
    wx.hideLoading()
  },

  /**
   * 生命周期函数--监听页面显示
   */
  get_list: function() {
    var that = this
    var member = wx.getStorageSync('member')
    wx.request({
      url: host + 'huipay/user.php?action=get_coupons',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
        openid: wx.getStorageSync('openid')
      },
      header: {
        'content-type': 'application/json'
      },
      success: function(res) {
        var enableData = res.data.enable
        if (enableData.length > 0) {
          //领取开卡礼
          var coupon_ids = ''
          var totals = ''
          for(var i = 0; i < enableData.length;i++){
            if (enableData[i].in_wechat == '0') {
              coupon_ids += enableData[i].coupon_id +'#'
              totals     += '1#'
            }
          }
          if (coupon_ids) {
            wx.navigateTo({
              url: '../coupon/get?coupon_id=' + coupon_ids + '&total=' + totals,
            })
          }
          var timing_cards = []
          for(var i = 0; i < enableData.length;i++){
            if (enableData[i].coupon_type == 'timing') {
              timing_cards[enableData.coupon_id] = {'coupon_name':enableData.coupon_name}
            }
          }
        }
        that.setData({
          cards: res.data
        })
      }
    })
  },
  open_card(e){
    var id = e.currentTarget.dataset.id
    var coupon_type = e.currentTarget.dataset.type
    var that = this
    if ('wechat_cash' == coupon_type) {
      wx.request({
        url: host + 'pay.php?action=get_self_counter',
        data: {
          mch_id: wx.getStorageSync('mch_id')
        },
        header: {
          'content-type': 'application/json'
        },
        success: function (res) {
          var url = 'counter=' + res.data.counter
          wx.navigateTo({
            url: '../index/selfpay?q=' + encodeURIComponent(url)
          })
        }
      })
    } else if ('groupon' == coupon_type || 'timing' == coupon_type) {
      wx.navigateTo({
        url: 'detail?id='+id,
      })
    } else {
      wx.request({
        url: host + 'card.php?action=get_detail',
        data: {
          id: id
        },
        header: {
          'content-type': 'application/json'
        },
        success: function (res) {
          if ('1' == res.data.in_wechat) {
            wx.openCard({
              cardList: [{
                cardId: res.data.card_id,
                code: res.data.code
              }],
              success(res) {

              }
            })
          } else {
            wx.navigateTo({
              url: 'detail?id=' + id,
            })
          }
        }
      })
    }
  },
  open_detail(e){
    var id = this.data.id
    var coupon_type = this.data.coupon_type
    wx.navigateTo({
      url: 'detail?id='+id,
    })
  },
  /**
   * 生命周期函数--监听页面隐藏
   */
  onHide: function() {},

  /**
   * 生命周期函数--监听页面卸载
   */
  onUnload: function() {},

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
  backtoindex: function () {
    wx.switchTab({
      url: '../index/index',
    })
  }
})