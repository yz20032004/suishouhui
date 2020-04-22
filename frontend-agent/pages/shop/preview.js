// pages/index/preview_shop.js
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
  onLoad: function(options) {
    var shop_id = options.hasOwnProperty('id') ? options.id : 0
    var mch_id = wx.getStorageSync('mch_id')
    this.getMchDetail(mch_id)
    this.getMchSubmitInfo(mch_id)
    this.getPayCounter(mch_id, shop_id)
  },
  getMchDetail: function(mch_id) {
    var that = this
    wx.request({
      url: host + 'ssh_mch.php?action=get_detail',
      data: {
        mch_id: mch_id,
      },
      header: {
        'content-type': 'application/json'
      },
      success: function(res) {
        wx.setStorageSync('merchant_name', res.data.merchant_name)
        wx.setStorageSync('mch', res.data)
        wx.setStorageSync('mch_id', res.data.mch_id)
        that.setData({
          merchant: res.data
        })
      }
    })
  },
  getShopDetail: function(mch_id, shop_id) {
    var that = this
    wx.request({
      url: host + 'tt_shop.php?action=get_detail',
      data: {
        mch_id: mch_id,
        shop_id:shop_id
      },
      header: {
        'content-type': 'application/json'
      },
      success: function(res) {
        that.setData({
          shop: res.data
        })
      }
    })
  },
  getMchSubmitInfo: function(mch_id) {
    var that = this
    wx.request({
      url: host + 'ssh_mch.php?action=get_mch_submit_info',
      data: {
        mch_id: mch_id,
      },
      header: {
        'content-type': 'application/json'
      },
      success: function(res) {
        that.setData({
          mch: res.data
        })
      }
    })
  },

  getPayCounter: function (mch_id, shop_id) {
    var that = this
    wx.request({
      url: host + 'tt_mch.php?action=get_pay_counter',
      data: {
        mch_id: mch_id,
        shop_id:shop_id
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        that.setData({
          paycounter: res.data
        })
      }
    })
  },
  bindPay:function(){
    var that = this
    if (this.data.paycounter.qrcode_url) {
      wx.showModal({
        title: '该门店已绑定收款码',
        content: '',
        showCancel:false
      })
      return
    }
    wx.showModal({
      title: '操作提示',
      content: '点击确定扫一扫收款码牌完成绑定',
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
  },
  bindPayQrCode: function (qrcode_url) {
    var shop_id = this.data.shop.id
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
      url: host + 'tt_shop.php?action=bindPayQrCode',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
        shop_id:shop_id,
        counter: counter
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        wx.showModal({
          title: '绑定成功',
          content: '',
          showCancel: false,
          success:function(){
            wx.navigateBack({
              delta: 1
            })
          }
        })
      }
    })
  },
  config:function(){
    var that = this
    wx.showActionSheet({
      itemList: ['绑定微信支付商户号', '绑定收款码牌', '修改门店信息'],
        success(e) {
          if (0 == e.tapIndex) {
            var mch_id = wx.getStorageSync('mch_id')
            if (10 == mch_id.length) {
              wx.showModal({
                title: '该商户已绑定微信支付',
                showCancel:false
              })
              return
            }
            wx.navigateTo({
              url: 'bind_mchid',
            })
          } else if (1 == e.tapIndex) {
            that.bindPay()
          } else if (2 == e.tapIndex) {
            wx.navigateTo({
              url: 'edit',
            })
          }
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
    var shop_id = 0
    this.getShopDetail(wx.getStorageSync('mch_id'), shop_id)
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
  back: function () {
    wx.navigateBack({
      delta: 1
    })
  }
})