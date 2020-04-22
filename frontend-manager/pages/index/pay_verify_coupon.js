const host = require('../../config').host
Page({

  /**
   * 页面的初始数据
   */
  data: {
    is_checked:false
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    var message = options.message
    var result_data = JSON.parse(message);
    this.setData({
      key:result_data.key,
      coupon_name: result_data.coupon_name,
      coupon_amount:'0' != result_data.coupon_amount ? result_data.coupon_amount : ''
    })
  },
  submit:function(e){
    var can_use = e.detail.value.can_use
    var coupon_amount = e.detail.value.coupon_amount
    if ('1' == can_use && !coupon_amount) {
      wx.showModal({
        title: '请输入券优惠金额',
        content: '',
        showCancel:false
      })
      return;
    }
    if (!this.data.is_checked) {
      wx.showModal({
        title: '请选择券是否可用',
        content: '',
        showCancel: false
      })
      return;
    }
    this.data.pay = 'verify_completed'
    this.data.verify_confirmed = '1' == can_use ? true : false
    this.data.coupon_amount = coupon_amount
    var message = JSON.stringify(this.data)
    this.websocket_sendmessage(message)
    wx.navigateBack({
      delta: 1
    })
  },
  websocket_sendmessage(message) {
    var that = this;
    wx.request({
      url: host + 'websocket.php?action=send_message',
      data: {
        channel: that.data.key,
        message: message
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
      }
    })
  },
  radioGroupChange:function(e){
    this.data.is_checked = true
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

  /**
   * 页面相关事件处理函数--监听用户下拉动作
   */
  onPullDownRefresh: function () {

  },

  /**
   * 页面上拉触底事件的处理函数
   */
  onReachBottom: function () {

  },

  /**
   * 用户点击右上角分享
   */
  onShareAppMessage: function () {

  }
})
