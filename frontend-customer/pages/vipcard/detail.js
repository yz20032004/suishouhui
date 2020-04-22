// pages/vipcard/detail.js
const host = require('../../config').host
Page({

  /**
   * 页面的初始数据
   */
  data: {
    show_share_box:false
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    wx.showLoading({
      title: '加载中',
    })
    var that = this
    this.data.interval = setInterval(
      function () {
        if (wx.getStorageSync('is_load_member')) {
          clearInterval(that.data.interval)
          that.get_detail(options.id)
        }
      }, 200)
  },
  get_detail(id){
    var buy_disable = false
    var buy_title = '立即购买'
    var that = this
    wx.request({
      url: host + 'huipay/vipcard.php?action=get_detail',
      data: {
        id: id
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        if (res.data.is_limit == '1' && (parseInt(res.data.total_limit) - parseInt(res.data.sold) <= 0)) {
          buy_disable = true
          buy_title = '已售完'
        }
        var member = wx.getStorageSync('member')
        wx.setNavigationBarTitle({
          title: res.data.merchant_name
        })
        that.setData({
          vipcard: res.data,
          grade_data:res.data.grade_data,
          opengifts:res.data.opengifts,
          buy_disable: buy_disable,
          buy_title: buy_title,
          member:member,
          is_follow: member.id ? true : false
        })
      }
    })
  },
  getUserFromBuy: function (e) {
    var that = this
    var user = e.detail.userInfo
    var encryptedData = e.detail.encryptedData
    var iv = e.detail.iv
    wx.request({
      url: host + 'huipay/user.php?action=update_user_info',
      data: {
        key: 'placeholder',
        openid: wx.getStorageSync('openid'),
        mch_id: that.data.vipcard.mch_id,
        encryptedData: encryptedData,
        iv: iv,
        session_key: wx.getStorageSync('session_key')
      },
      success: function (res) {
        that.setData({
          is_follow: true
        })
        if ('success' == res.data) {
          wx.setStorageSync('member_multiple_cards', 1)
          wx.setStorageSync('mch_id', that.data.vipcard.mch_id)
          that.pay()
        }
      }
    })
  },
  getUserFromShare: function (e) {
    var that = this
    var user = e.detail.userInfo
    var encryptedData = e.detail.encryptedData
    var iv = e.detail.iv
    wx.request({
      url: host + 'huipay/user.php?action=update_user_info',
      data: {
        appid: wx.getStorageSync('appid'),
        key: 'placeholder',
        openid: wx.getStorageSync('openid'),
        mch_id: that.data.vipcard.mch_id,
        encryptedData: encryptedData,
        iv: iv,
        session_key: wx.getStorageSync('session_key')
      },
      success: function (res) {
        that.setData({
          is_follow: true
        })
        if ('success' == res.data) {
          that.setData({
            show_share_box: true
          })
        }
      }
    })
  },
  pay: function () {
    var that = this
    var member = wx.getStorageSync('member')
    if (member.grade == this.data.vipcard.grade) {
      wx.showModal({
        title: '您已经是'+that.data.vipcard.grade_name+'会员了',
        content: '',
        showCancel:false
      })
      return
    }
    this.setData({
      pay_disabled: true
    })
    var id = this.data.vipcard.id
    var consume = this.data.vipcard.price
    var that = this
    wx.request({
      url: host + 'huipay/vipcard.php?action=getPrepay',
      data: {
        openid: wx.getStorageSync('openid'),
        grade:that.data.vipcard.grade,
        grade_name:that.data.vipcard.grade_name,
        consume: consume,
        mch_id: that.data.vipcard.mch_id,
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        var payargs = res.data
        wx.requestPayment({
          'timeStamp': payargs.timeStamp,
          'nonceStr': payargs.nonceStr,
          'package': payargs.package,
          'signType': payargs.signType,
          'paySign': payargs.paySign,
          'success': function (res) {
            if (member.cardnum) {
              wx.showModal({
                title: '购买成功',
                content: '恭喜您升级为'+that.data.vipcard.grade_name+'会员',
                showCancel:false,
                success:function(){
                  wx.reLaunch({
                    url: '../index/index',
                  })
                }
              })
            } else {
              that.opencard()
            }
          }
        })
      },
      'complete': function (res) {
        that.setData({
          pay_disabled: false
        })
      }
    })
  },
  opencard: function () {
    var that = this
    wx.request({
      url: host + 'card.php?action=get_membercard_extradata',
      data: {
        mch_id: that.data.vipcard.mch_id
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        var encrypt_card_id = decodeURIComponent(res.data.encrypt_card_id)
        var biz = decodeURIComponent(res.data.biz)
        var extraData = {
          encrypt_card_id: encrypt_card_id,
          out_str: 'mini',
          biz: biz
        }
        wx.navigateToMiniProgram({
          appId: 'wxeb490c6f9b154ef9', //固定为此 appid，不可改动
          extraData: extraData,
          success: function (res) {
            wx.reLaunch({
              url: '../index/index',
            })
          },
          fail: function (res) {
          },
          complete: function () {
          }
        })
      }
    })
  },
  /**
   * 生命周期函数--监听页面初次渲染完成
   */
  onReady: function () {
    wx.hideLoading()
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
    this.setData({
      show_share_box:false
    })
  },

  /**
   * 生命周期函数--监听页面卸载
   */
  onUnload: function () {

  },
  share:function(){
    this.setData({
      show_share_box:true
    })
  },
  shareimage:function(){
    wx.showLoading({
      title: '生成海报中...',
    })
    var that = this
    wx.request({
      url: host + 'huipay/vipcard.php?action=get_share_image',
      data: {
        id: that.data.vipcard.id,
        mch_id: wx.getStorageSync('mch_id'),
        openid: wx.getStorageSync('openid'),
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        var distribute_id = res.data
        wx.request({
          url: host + 'huipay/vipcard.php?action=get_share_image',
          data: {
            id: that.data.vipcard.id,
            openid: wx.getStorageSync('openid'),
          },
          header: {
            'content-type': 'application/json'
          },
          success: function (res) {
            wx.hideLoading()
            var url = res.data
            wx.previewImage({
              current: url,
              urls: [url]
            })
          }
        })
      }
    })
  },
  /**
   * 用户点击右上角分享
   */
  onShareAppMessage: function () {
  },
  backtoindex: function () {
    wx.switchTab({
      url: '../index/index',
    })
  }
})