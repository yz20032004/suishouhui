// pages/marketing/grade_add.js
const host = require('../../config').host
var app = getApp()
Page({

  /**
   * 页面的初始数据
   */
  data: {
    valid_days: [
      { days: '0', title: "永久" },
      { days: '30', title: "一个月" },
      { days: '60', title: "两个月" },
      { days: '90', title: "三个月" },
      { days: '180', title: "六个月" },
      { days: '365', title: "一年" },
    ], 
    point_speed_range: [
      { speed: '1', title: "不加速返积分" },
      { speed: '1.1', title: "1.1倍" },
      { speed: '1.2', title: "1.2倍" },
      { speed: '1.5', title: "1.5倍" },
      { speed: '2', title: "2倍" },
      { speed: '2.5', title: "2.5倍" },
      { speed: '3', title: "3倍" },
      { speed: '3.5', title: "3.5倍" },
      { speed: '4', title: "4倍" },
      { speed: '4.5', title: "4.5倍" },
      { speed: '5', title: "5倍" },
      { speed: '6', title: "6倍" },
      { speed: '7', title: "7倍" },
      { speed: '8', title: "8倍" },
      { speed: '9', title: "9倍" },
      { speed: '10', title: "10倍" },
    ],
    daysIndex: 0,
    speedIndex:0
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    this.setData({
      display: 'none'
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

  },
  conditionSwitch: function (e) {
    var condition = e.detail.value
    if ('frequency' == condition) {
      var catch_title = '次数';
    } else {
      var catch_title = '金额';
    }
    this.setData({
      display: '',
      catch_title: catch_title
    })
  },
  submit: function (e) {
    var name = e.detail.value.name
    var detail = e.detail.value.detail
    var discount = e.detail.value.discount
    var point_speed = e.detail.value.point_speed
    var condition = e.detail.value.condition
    var catch_value = e.detail.value.catch
    var valid_days = e.detail.value.valid_days
    if (!name) {
      wx.showModal({
        title: "请填写等级名称",
        content: "",
        showCancel: false,
        confirmText: "确定"
      })
      return false
    }
    if (discount) {
      if (isNaN(discount)) {
        wx.showModal({
          title: "折扣请填写数字",
          content: "",
          showCancel: false,
          confirmText: "确定"
        })
        return false
      } else if (discount >= 100 || discount < 1) {
        wx.showModal({
          title: "折扣超出范围",
          content: "",
          showCancel: false,
          confirmText: "确定"
        })
        return false
      }
      if (discount.length == 1) {
        discount = discount * 10
      }
    }
    if (!condition) {
      wx.showModal({
        title: "请选择升级方式",
        content: "",
        showCancel: false,
        confirmText: "确定"
      })
      return false
    }
    if (!catch_value) {
      wx.showModal({
        title: "请填写升级达到的条件",
        content: "",
        showCancel: false,
        confirmText: "确定"
      })
      return false
    }
    if (name.length > 4) {
      wx.showModal({
        title: "等级名称不能超过4个汉字",
        content: "",
        showCancel: false,
        confirmText: "确定"
      })
      return false
    }
    wx.request({
      url: host + 'ssh_mch.php?action=add_grade',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
        name: name,
        discount: discount,
        point_speed:point_speed,
        privilege: detail,
        condition: condition,
        catch_value: catch_value,
        valid_days: valid_days
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        wx.showModal({
          title: "创建成功",
          content: "",
          showCancel: false,
          confirmText: "确定",
          success: function () {
            wx.navigateBack({
              delta: 1
            })
          }
        })
      }
    })
  },
  bindDaysChange: function (e) {
    this.setData({
      daysIndex: e.detail.value
    })
  },
  bindPointSpeedChange: function (e) {
    this.setData({
      speedIndex: e.detail.value
    })
  },
  back: function () {
    wx.navigateBack({
      delta: 1
    })
  }
})
