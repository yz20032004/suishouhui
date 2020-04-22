// pages/shop/bind_tenpay.js
const host = require('../../config').host
Page({

  /**
   * 页面的初始数据
   */
  data: {},

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {},

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
  mch_input: function (e) {
    var mch_id = e.detail.value
    this.setData({
      mch_id: mch_id
    })
  },
  scan: function () {
    var that = this
    var mch_id = that.data.mch_id
    if (!mch_id) {
      wx.showModal({
        title: "请填写微信支付商户号",
        content: "",
        showCancel: false,
        confirmText: "确定"
      })
      return false
    }
    if (mch_id.length != 10) {
      wx.showModal({
        title: "请填写正确的微信支付商户号",
        content: "",
        showCancel: false,
        confirmText: "确定"
      })
      return false
    }
    wx.scanCode({
      success: function (res) {
        var bind_url = res.result + '&sn=SSH_V100_'+wx.getStorageSync('mch_id')
        wx.request({
          url: bind_url,
          data: {},
          header: {
            'content-type': 'application/json'
          },
          success: function (res) {
            if ('0' == res.data.status) {
              var authen_key = res.data.use_device_setting.authen_key
              var out_mch_id = res.data.use_device_setting.out_mch_id
              var out_sub_mch_id = res.data.use_device_setting.out_sub_mch_id
              var out_shop_id = res.data.use_device_setting.out_shop_id
              var cloud_cashier_id = res.data.use_device_setting.cloud_cashier_id
              wx.request({
                url: host + 'ssh_mch.php?action=update_mchid',
                data: {
                  mch_id: wx.getStorageSync('mch_id'),
                  new_mch_id: mch_id,
                },
                header: {
                  'content-type': 'application/json'
                },
                success: function (res) {
                  wx.setStorageSync('mch', res.data)
                  wx.setStorageSync('mch_id', mch_id)
                  wx.request({
                    url: host + 'ssh_mch.php?action=bind_tenpay',
                    data: {
                      mch_id: mch_id,
                      authen_key: authen_key,
                      out_shop_id: out_shop_id,
                      out_mch_id:out_mch_id,
                      out_sub_mch_id: out_sub_mch_id,
                      cloud_cashier_id:cloud_cashier_id
                    },
                    header: {
                      'content-type': 'application/json'
                    },
                    success: function (res) {
                      wx.showModal({
                        cancelColor: 'cancelColor',
                        title: '绑定成功',
                        showCancel: false,
                        success(res) {
                          that.back()
                        }
                      })
                    }
                  })
                }
              })
            } else {
              wx.showToast({
                title: res.data.description,
                icon:"none"
              })
            }
          }
        })
      }
    })
  },
  back: function () {
    wx.navigateBack({
      delta: 1
    })
  }
})