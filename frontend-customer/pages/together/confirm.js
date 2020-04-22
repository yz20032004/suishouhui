
const host = require('../../config').host
Page({

  /**
   * 页面的初始数据
   */
  data: {
    image_reduce: 'reduce_disable',
    image_add: 'add',
    buy_total: 1,
    pay_disabled: false,
    from_detail:false
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    var id = options.id
    var expire_times = options.expire_times
    var together_no = options.together_no
    var that = this
    var shop = wx.getStorageSync('shop')
    wx.request({
      url: host + 'huipay/together.php?action=get_detail',
      data: {
        id: id
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        var member = wx.getStorageSync('member')
        that.setData({
          together: res.data,
          consume: res.data.price,
          is_follow: member.id ? true : false,
          together_no:together_no,
          expire_times:expire_times
        })
      }
    })
  },
  reduce_total: function () {
    var buy_total = this.data.buy_total - 1
    var image_reduce = 'reduce'
    if (buy_total == 1) {
      image_reduce = 'reduce_disable'
    } else if (buy_total < 1) {
      return
    }
    var price = this.data.together.price
    var consume = buy_total * price
    this.setData({
      buy_total: buy_total,
      image_add: 'add',
      image_reduce: image_reduce,
      consume: consume.toFixed(2)
    })
  },
  add_total: function () {
    var buy_total = this.data.buy_total + 1
    var image_add = 'add'
    if (this.data.together.single_limit != '0') {
      if (buy_total == this.data.together.single_limit) {
        image_add = 'add_disable'
      } else if (buy_total > this.data.together.single_limit) {
        wx.showToast({
          icon: 'none',
          title: '最多还能购买' + this.data.together.single_limit + '份',
        })
        return;
      }
    }
    var balance = this.data.together.total_limit - this.data.together.sold
    if (buy_total > balance) {
      wx.showToast({
        icon: 'none',
        title: '最多还能购买' + balance + '份',
      })
      return;
    }
    var price = this.data.together.price
    var consume = buy_total * price
    this.setData({
      buy_total: buy_total,
      image_add: image_add,
      image_reduce: 'reduce',
      consume: consume.toFixed(2)
    })
  },
  getUser: function (e) {
    var that = this
    var user = e.detail.userInfo
    var encryptedData = e.detail.encryptedData
    var iv = e.detail.iv
    wx.request({
      url: host + 'huipay/user.php?action=update_user_info',
      data: {
        key: 'placeholder',
        openid: wx.getStorageSync('openid'),
        mch_id: that.data.together.mch_id,
        encryptedData: encryptedData,
        iv: iv,
        session_key: wx.getStorageSync('session_key')
      },
      success: function (res) {
        that.setData({
          is_follow: true
        })
        if ('fail' != res.data) {
          if (wx.getStorageSync('mch_id') != that.data.together.mch_id) {
            var member_multiple_cards = parseInt(wx.getStorageSync('member_multiple_cards')) + 1
            wx.setStorageSync('member_multiple_cards', member_multiple_cards)
            wx.setStorageSync('mch_id', that.data.together.mch_id)
          }
          that.pay()
        }
      }
    })
  },
  pay: function () {
    var that = this
    var member = wx.getStorageSync('member')
    this.setData({
      pay_disabled: true
    })
    var id = this.data.together.id
    var buy_total = this.data.buy_total
    var coupon_id = this.data.together.coupon_id
    var consume = this.data.consume
    var that = this

    wx.request({
      url: host + 'huipay/together.php?action=getPrepay',
      data: {
        openid: wx.getStorageSync('openid'),
        together_id: that.data.together.id,
        coupon_id: coupon_id,
        coupon_name: that.data.together.coupon_data.name,
        buy_total: buy_total,
        consume: consume,
        title: that.data.together.title,
        mch_id: that.data.together.mch_id,
        single_limit: that.data.together.single_limit,
        is_head:!that.data.together_no ? true :false,
        together_no:that.data.together_no
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        if ('fail' == res.data.result) {
          wx.showToast({
            icon: 'none',
            title: res.data.msg,
          })
          return;
        }
        var payargs = res.data.payargs
        var out_trade_no = res.data.out_trade_no
        wx.requestPayment({
          'timeStamp': payargs.timeStamp,
          'nonceStr': payargs.nonceStr,
          'package': payargs.package,
          'signType': payargs.signType,
          'paySign': payargs.paySign,
          'success': function (res) {
            if (wx.getStorageSync('mch_id') != that.data.together.mch_id) {
              var member_multiple_cards = parseInt(wx.getStorageSync('member_multiple_cards')) + 1
              wx.setStorageSync('member_multiple_cards', member_multiple_cards)
              wx.setStorageSync('mch_id', that.data.together.mch_id)
            }
            var get_total = buy_total * that.data.together.coupon_total
            wx.navigateTo({
              url: '../vip/groupon_detail?out_trade_no=' + out_trade_no,
              success:function(){
                that.setData({
                  'from_detail':true
                })
              }
            })
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
  /**
   * 生命周期函数--监听页面初次渲染完成
   */
  onReady: function () {

  },

  /**
   * 生命周期函数--监听页面显示
   */
  onShow: function () {
    if (this.data.from_detail) {
      wx.switchTab({
        url: '../index/index',
      })
    }
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
})
