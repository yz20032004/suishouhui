// pages/setting/staff-edit.js
const host = require('../../config').host
Page({

  /**
   * 页面的初始数据
   */
  data: {
    staff_id: 0,
    disabled:false
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    var staff_id = options.id
    this.setData({
      staff_id: staff_id
    })
    var that = this

    wx.request({
      url: host + 'mch.php?action=get_staff_detail',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
        id: staff_id
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        that.setData({
          staffData: res.data,
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
    this.setData({
      disabled: 'admin' != wx.getStorageSync('user_role') ? true : false
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

  /**
   * 页面上拉触底事件的处理函数
   */
  onReachBottom: function () {

  },

  /**
   * 页面相关事件处理函数--监听用户下拉动作
   */
  onPullDownRefresh: function () {

  },
  submit: function (e) {
    var staff_id = e.detail.value.id
    var name = e.detail.value.name
    var mobile = e.detail.value.mobile
    if (!name) {
      wx.showModal({
        title: "请填写员工姓名",
        content: "",
        showCancel: false,
        confirmText: "确定"
      })
      return false
    }
    wx.request({
      url: host + 'mch.php?action=update_staff',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
        id: staff_id,
        name: name,
        mobile: mobile
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        wx.showToast({
          title: '操作成功',
          icon: 'success',
          duration: 2000,
          success: function () {
            wx.navigateBack({
              delta: 1
            })
          }
        })
      }
    })
  },
  disable: function (e) {
    var staff_id = e.currentTarget.dataset.id
    wx.showModal({
      title: "您确定要禁用此员工吗？",
      content: "",
      showCancel: true,
      confirmText: "确定",
      success: function (res) {
        if (res.confirm) {
          wx.request({
            url: host + 'mch.php?action=disable_staff',
            data: {
              mch_id: wx.getStorageSync('mch_id'),
              id: staff_id
            },
            header: {
              'content-type': 'application/json'
            },
            success: function (res) {
              wx.showToast({
                title: '操作成功',
                icon: 'success',
                duration: 2000,
                success: function () {
                  wx.navigateBack({
                    delta: 1
                  })
                }
              })
            }
          })
        } else if (res.cancel) {

        }
      }
    })
  },
  enable: function (e) {
    var staff_id = e.currentTarget.dataset.id
    wx.showModal({
      title: "您确定要恢复此员工吗？",
      content: "",
      showCancel: true,
      confirmText: "确定",
      success: function (res) {
        if (res.confirm) {
          wx.request({
            url: host + 'mch.php?action=enable_staff',
            data: {
              mch_id: wx.getStorageSync('mch_id'),
              id: staff_id
            },
            header: {
              'content-type': 'application/json'
            },
            success: function (res) {
              wx.showToast({
                title: '操作成功',
                icon: 'success',
                duration: 2000,
                success: function () {
                  wx.navigateBack({
                    delta: 1
                  })
                }
              })
            }
          })
        } else if (res.cancel) {

        }
      }
    })
  },
  delete: function (e) {
    var staff_id = e.currentTarget.dataset.id
    wx.showModal({
      title: "您确定要删除此员工吗？",
      content: "",
      showCancel: true,
      confirmText: "确定",
      success: function (res) {
        if (res.confirm) {
          wx.request({
            url: host + 'mch.php?action=delete_staff',
            data: {
              mch_id: wx.getStorageSync('mch_id'),
              id: staff_id
            },
            header: {
              'content-type': 'application/json'
            },
            success: function (res) {
              wx.showToast({
                title: '操作成功',
                icon: 'success',
                duration: 2000,
                success: function () {
                  wx.navigateBack({
                    delta: 1
                  })
                }
              })
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
  }
})
