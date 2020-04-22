// pages/order/preview.js
const host = require('../../config').host
Page({

  /**
   * 页面的初始数据
   */
  data: {
    is_loaded:false
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    var that = this
    wx.request({
      url: host + 'huipay/form.php?action=get_detail',
      data: {
        form_id:wx.getStorageSync('form_id'),
        mch_id:wx.getStorageSync('mch_id'),
        openid:wx.getStorageSync('openid'),
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        if ('point' == res.data.award_type) {
          wx.showModal({
            title: '恭喜你获得'+res.data.award_value+'积分',
            showCancel:false
          })
        } else if ('coupon' == res.data.award_type) {
          var coupon_id = res.data.award_value
          wx.showModal({
            title: '恭喜你获得'+res.data.coupon_name+'1张',
            content:'现在去领取',
            showCancel:false,
            success(res){
              wx.redirectTo({
                url: '../coupon/get?coupon_id='+coupon_id+'&total=1',
              })
            }
          })
        }
        that.setData({
          is_loaded:true,
          member:wx.getStorageSync('member')
        })
      }
    })
  },
  opencard:function(){
    wx.navigateTo({
      url: '../index/get_membercard?mch_id='+wx.getStorageSync('mch_id'),
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
    if (this.data.is_loaded) {
      wx.navigateBack({
        delta:-1
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