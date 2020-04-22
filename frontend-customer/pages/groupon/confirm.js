// pages/groupon/confirm.js
const host = require('../../config').host
Page({

  /**
   * 页面的初始数据
   */
  data: {
    image_reduce: 'reduce_disable',
    image_add: 'add',
    buy_total: 1,
    pay_disabled: false
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function(options) {
    var id = options.id
    var that = this
    var shop = wx.getStorageSync('shop')
    wx.request({
      url: host + 'huipay/groupon.php?action=get_detail',
      data: {
        id: id
      },
      header: {
        'content-type': 'application/json'
      },
      success: function(res) {
        var member = wx.getStorageSync('member')
        that.setData({
          groupon: res.data,
          consume: res.data.price,
          is_follow: member.id ? true : false
        })
      }
    })
  },
  reduce_total: function() {
    var buy_total = this.data.buy_total - 1
    var image_reduce = 'reduce'
    if (buy_total == 1) {
      image_reduce = 'reduce_disable'
    } else if (buy_total < 1) {
      return
    }
    var price = this.data.groupon.price
    var consume = buy_total * price
    this.setData({
      buy_total: buy_total,
      image_add: 'add',
      image_reduce: image_reduce,
      consume: consume.toFixed(2)
    })
  },
  add_total: function() {
    var buy_total = this.data.buy_total + 1
    var image_add = 'add'
    if (this.data.groupon.single_limit != '0') {
      if (buy_total == this.data.groupon.single_limit) {
        image_add = 'add_disable'
      } else if (buy_total > this.data.groupon.single_limit) {
        wx.showToast({
          icon:'none',
          title: '最多还能购买'+this.data.groupon.single_limit+'份',
        })
        return;
      }
    }
    var balance = this.data.groupon.total_limit - this.data.groupon.sold
    if (buy_total > balance) {
      wx.showToast({
        icon: 'none',
        title: '最多还能购买' + balance + '份',
      })
      return;
    }
    var price = this.data.groupon.price
    var consume = buy_total * price
    this.setData({
      buy_total: buy_total,
      image_add: image_add,
      image_reduce: 'reduce',
      consume: consume.toFixed(2)
    })
  },
  getUser: function(e) {
    var that = this
    var user = e.detail.userInfo
    var encryptedData = e.detail.encryptedData
    var iv = e.detail.iv
    wx.request({
      url: host + 'huipay/user.php?action=update_user_info',
      data: {
        key: 'placeholder',
        openid: wx.getStorageSync('openid'),
        mch_id: that.data.groupon.mch_id,
        encryptedData: encryptedData,
        iv: iv,
        session_key: wx.getStorageSync('session_key')
      },
      success: function(res) {
        that.setData({
          is_follow:true
        })
        if ('fail' != res.data) {
          if (wx.getStorageSync('mch_id') != that.data.groupon.mch_id) {
            var member_multiple_cards = parseInt(wx.getStorageSync('member_multiple_cards')) + 1
            wx.setStorageSync('member_multiple_cards', member_multiple_cards)
            wx.setStorageSync('mch_id', that.data.groupon.mch_id)
          }
          if (that.data.groupon.is_member_limit == '1' && !wx.getStorageSync('is_member')) {
            wx.showModal({
              title: '本优惠仅限会员购买',
              content: '现在去加入会员享受更多优惠',
              success(res){
                if (res.confirm) {
                  wx.navigateTo({
                    url: '../index/get_membercard?mch_id='+that.data.groupon.mch_id,
                  })
                }
              }
            })
          } else {
            that.pay()
          }
        }
      }
    })
  },
  pay: function() {
    var that = this
    var member = wx.getStorageSync('member')
    if (this.data.groupon.is_member_limit == '1' && !member.cardnum) {
      wx.showModal({
        title: '本优惠仅限会员购买',
        content: '现在去加入会员享受更多优惠',
        success(res) {
          if (res.confirm) {
            wx.navigateTo({
              url: '../index/get_membercard?mch_id=' + that.data.groupon.mch_id,
            })
          }
        }
      })
      return
    }
    this.setData({
      pay_disabled: true
    })
    var id = this.data.groupon.id
    var buy_total = this.data.buy_total
    var coupon_id = this.data.groupon.coupon_id
    var consume = this.data.consume
    var that = this

    wx.request({
      url: host + 'huipay/groupon.php?action=getPrepay',
      data: {
        openid: wx.getStorageSync('openid'),
        groupon_id: that.data.groupon.id,
        coupon_id: coupon_id,
        coupon_total: that.data.groupon.coupon_total,
        coupon_name: that.data.groupon.coupon_data.name,
        buy_total: buy_total,
        consume: consume,
        title: that.data.groupon.title,
        mch_id: that.data.groupon.mch_id,
        single_limit:that.data.groupon.single_limit
      },
      header: {
        'content-type': 'application/json'
      },
      success: function(res) {
        if ('fail' == res.data.result) {
          wx.showToast({
            icon: 'none',
            title: res.data.msg,
          })
          return;
        }
        var payargs = res.data.payargs
        wx.requestPayment({
          'timeStamp': payargs.timeStamp,
          'nonceStr': payargs.nonceStr,
          'package': payargs.package,
          'signType': payargs.signType,
          'paySign': payargs.paySign,
          'success': function(res) {
            if (wx.getStorageSync('mch_id') != that.data.groupon.mch_id) {
              var member_multiple_cards = parseInt(wx.getStorageSync('member_multiple_cards')) + 1
              wx.setStorageSync('member_multiple_cards', member_multiple_cards)
              wx.setStorageSync('mch_id', that.data.groupon.mch_id)
            }
            wx.reLaunch({
              url: '../index/index',
            })
          }
        })
      },
      'complete': function(res) {
        that.setData({
          pay_disabled: false
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
  onShow: function() {},

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

  }
})
