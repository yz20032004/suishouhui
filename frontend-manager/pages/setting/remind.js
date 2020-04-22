// pages/setting/remind.js
const host = require('../../config').host
Page({

  /**
   * 页面的初始数据
   */
  data: {
    wechat_number: 'keyouxinxi'
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    var that = this
    wx.request({
      url: host + 'user.php?action=get_reminds',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
        openid:wx.getStorageSync('openid')
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        if (!res.data || !res.data.hasOwnProperty('unionid')) {
          that.checkUnionId()
        }
        that.setData({
          remind:res.data,
          unionid:res.data.hasOwnProperty('unionid') ? res.data.unionid : ''
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
  exchangePaySwitch:function(e){
    this.update_remind('is_pay', e.detail.value)
  },
  exchangeWaimaiSwitch:function(e){
    this.update_remind('is_waimai', e.detail.value)
  },
  exchangeMallSwitch:function(e){
    this.update_remind('is_mall', e.detail.value)
  },
  exchangeRechargeSwitch:function(e){
    this.update_remind('is_recharge', e.detail.value)
  },
  exchangeGrouponSwitch:function(e){
    this.update_remind('is_groupon', e.detail.value)
  },
  exchangeWechatGroupSwitch:function(e){
    this.update_remind('is_wechat_group', e.detail.value)
  },
  exchangeVipcardSwitch:function(e){
    this.update_remind('is_vipcard', e.detail.value)
  },
  exchangeMemberSwitch:function(e){
    this.update_remind('is_member', e.detail.value)
  },
  exchangeDaySwitch:function(e){
    this.update_remind('is_day', e.detail.value)
  },
  exchangeWeekSwitch:function(e){
    this.update_remind('is_week', e.detail.value)
  },
  exchangeMonthSwitch:function(e){
    this.update_remind('is_month', e.detail.value)
  },
  copy:function(){
    var that = this
    wx.setClipboardData({
      data: that.data.wechat_number,
      success(res) {
        wx.getClipboardData({
          success(res) {
          }
        })
      }
    })
  },
  subscribe:function(){
    var that = this
    wx.requestSubscribeMessage({
      tmplIds: [that.data.tmpId],
      success (res) {
        if ('accept' == res.xVUXORt1ioAsvXFvDuusLv3CrJDFNNMT8I2QZ7zK_Jg) {
          that.update_remind('is_waimai', 1)
        }
      },
      fail(res) {
        console.log(res)
      }
    })
  },
  update_remind:function(remind_function, is_remind) {
    var that = this
    if (!this.data.unionid) {
      wx.showModal({
        cancelColor: 'cancelColor',
        title:'请先添加微信公众号'+that.data.wechat_number+'接收提醒消息',
        content:'',
        showCancel:false
      })
      return
    }
    wx.request({
      url: host + 'user.php?action=update_remind',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
        openid:wx.getStorageSync('openid'),
        unionid:that.data.unionid,
        remind_function:remind_function,
        is_remind:is_remind
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        wx.showModal({
          title: is_remind ? '新增提醒成功' : '已取消提醒',
          showCancel:false
        })
      }
    })
  },
  checkUnionId:function(){
    var that = this
    wx.login({
      success: function (data) {
        wx.request({
          url: host + 'user.php?action=login',
          data: {
            js_code: data.code
          },
          success: function (res) {
            wx.setStorageSync('session_key', res.data.session_key)
            if (!res.data.hasOwnProperty('unionid')) {
              wx.showModal({
                cancelColor: 'cancelColor',
                title:'请先添加公众号'+that.data.wechat_number+'接收提醒消息',
                content:'',
                showCancel:false
              })
            } else {
              that.setData({
                unionid:res.data.unionid
              })
            }
          }
        })
      }
    })
  }

})