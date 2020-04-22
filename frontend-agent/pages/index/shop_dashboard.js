// pages/index/shop_dashboard.js
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
    var user = wx.getStorageSync('user')
    var mch_id = options.mch_id
    wx.setStorageSync('mch_id', mch_id)

    this.getMchDetail(mch_id)
  },
  getMchDetail: function (mch_id) {
    var that = this
    wx.request({
      url: host + 'ssh_mch.php?action=get_detail',
      data: {
        mch_id: mch_id,
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        wx.setStorageSync('merchant_name', res.data.merchant_name)
        wx.setStorageSync('mch', res.data)
        wx.setStorageSync('mch_id', res.data.mch_id)
        that.setData({
          merchant: res.data
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
  shops:function(){
    wx.navigateTo({
      url: '../shop/list',
    })
  },
  coupons: function () {
    wx.navigateTo({
      url: '../coupon/list',
    })
  },
  bind_tenpay:function(){
    wx.navigateTo({
      url: '../shop/bind_tenpay',
    })
  },
  memberqrcode:function(){
    wx.navigateTo({
      url: '../marketing/membercard',
    })
  },
  addalipay: function () {
    if (this.data.merchant.alipay_app_id) {
      wx.showModal({
        title: '商户已开通支付宝收款',
        content: '',
        showCancel: false
      })
    } else {
      wx.navigateTo({
        url: 'bind_alipay'
      })
    }
  },
  addwechatpay: function () {
    if (this.data.merchant.mch_type != 'general') {
      wx.showModal({
        title: '商户已有微信收款功能',
        content: '',
        showCancel: false
      })
    } else {
      wx.showModal({
        title: '暂未开通此功能',
        content: '',
        showCancel: false
      })
    }
  },
  bindqrcode: function () {
    if ('general' == this.data.merchant.mch_type) {
      wx.showModal({
        title: '普通商户无收款功能',
        content: '请先将商户升级为小微或特约商户',
        showCancel: false
      })
      return
    }
    var merchant = wx.getStorageSync('mch')
    if (parseInt(merchant.shops) > 1) {
      wx.showModal({
        title: '多门店请至分店管理里绑定收款码',
        content: '',
        showCancel: false,
        success:function(){
          wx.navigateTo({
            url: '../shop/list',
          })
        }
      })
      return
    }
    if ('1' == this.data.merchant.is_bind_payqrcode) {
      wx.showModal({
        title: '商户已绑定收款码',
        content: '',
        showCancel: false
      })
    } else {
      wx.showModal({
        title: '操作提示',
        content: '点击确定扫一扫商户收款码完成绑定',
        success(res) {
          if (res.confirm) {
            wx.scanCode({
              onlyFromCamera: true,
              success(res) {
                that.bindPayQrCode(res.result)
              }
            })
          }
        }
      })
    }
  },
  marketing: function () {
    wx.navigateTo({
      url: '../marketing/index',
    })
  },
  groupon: function () {
    wx.navigateTo({
      url: '../buy/list?coupon_type=groupon',
    })
  },
  campaigns: function () {
    wx.navigateTo({
      url: '../campaign/list',
    })
  },
  list: function () {
    wx.navigateTo({
      url: '../trade/list',
    })
  },
  pay_qrcode:function(){
    wx.request({
      url: host + 'tt_mch.php?action=getPayQrCode',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        var url = res.data.url
        wx.previewImage({
          current: url,
          urls: [url]
        })
      }
    })
  },
  report: function () {
    wx.navigateTo({
      url: '../stat/merchant',
    })
  },
  detail: function () {
    wx.navigateTo({
      url: '../shop/preview',
    })
  },
  coupon_consumed:function(){
    wx.navigateTo({
      url: '../trade/coupon_consumed',
    })
  },
  revenue_config:function(){
    wx.navigateTo({
      url: '../shop/revenue_config',
    })
  },
  bindPayQrCode: function (qrcode_url) {
    var url = decodeURIComponent(qrcode_url)
    var params = url.split('=')
    var counter = params[1]
    if (!counter) {
      wx.showModal({
        title: '操作失败',
        content: '不正确的收款二维码',
        showCancel: false,
      })
      return
    }
    wx.request({
      url: host + 'tt_mch.php?action=bindPayQrCode',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
        counter: counter
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        that.getMchDetail()
        wx.showModal({
          title: '绑定成功',
          content: '',
          showCancel: false
        })
      }
    })
  },
  reg_mini:function(){
    var mch = wx.getStorageSync('mch')
    if ('xiaowei' == mch.mch_type) {
      wx.showModal({
        title:'小微商户无法创建会员独立小程序',
        content:'',
        showCancel:false
      })
      return
    } else if ('wxaa02c1c97542b1e4' != mch.appid) {
      wx.showModal({
        title:'该商户已申请会员独立小程序',
        content:'appid:'+mch.appid,
        showCancel:false
      })
      return
    } else {
      wx.navigateTo({
        url: '../shop/reg_mini',
      })
    }
  },
  ability:function(){
    wx.navigateTo({
      url: '../shop/ability',
    })
  }
})