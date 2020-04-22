// pages/order/add_table.js
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
  submit: function (e) {
    if ('admin' != wx.getStorageSync('user_role')) {
      wx.showModal({
        title: '温馨提示',
        content: '体验者或非管理员身份不可执行此操作',
        showCancel: false
      })
      return
    }
    var table_name = e.detail.value.table_name
    var seats = e.detail.value.seats
    if (!table_name) {
      wx.showModal({
        title: '温馨提示',
        content: '请填写桌台名称',
        showCancel: false
      })
      return
    }
    if (!seats) {
      wx.showModal({
        title: '温馨提示',
        content: '请填写桌台座位数',
        showCancel: false
      })
      return
    }
    wx.request({
      url: host + 'tables.php?action=add_table',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
        table_name: table_name,
        seats:seats
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        if ('fail' == res.data) {
          wx.showModal({
            title: "桌台名称不能重复",
            content: "",
            showCancel: false,
            confirmText: "确定"
          })
          return false
        } else {
          wx.showToast({
            title: '操作成功',
            icon: 'success',
            duration: 2000,
            success: function () {
              wx.navigateBack({ delta:-1 })
            }
          })
        }
      }
    })
  },
  back: function () {
    wx.navigateBack({
      delta: 1
    })
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