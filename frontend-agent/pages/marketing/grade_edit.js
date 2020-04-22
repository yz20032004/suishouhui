// pages/marketing/privilege.js
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
      { speed: '2.0', title: "2倍" },
      { speed: '2.5', title: "2.5倍" },
      { speed: '3.0', title: "3倍" },
      { speed: '3.5', title: "3.5倍" },
      { speed: '4.0', title: "4倍" },
      { speed: '4.5', title: "4.5倍" },
      { speed: '5.0', title: "5倍" },
      { speed: '6.0', title: "6倍" },
      { speed: '7.0', title: "7倍" },
      { speed: '8.0', title: "8倍" },
      { speed: '9.0', title: "9倍" },
      { speed: '10.0', title: "10倍" },
    ],
    speedIndex:0,
    daysIndex: 0,
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    var id = options.id
    var that = this
    var display = 'none'
    wx.request({
      url: host + 'ssh_mch.php?action=get_grade&id=' + id,
      data: {
        mch_id: wx.getStorageSync('mch_id'),
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        if (res.data) {
          if ('frequency' == res.data.catch_type) {
            var catch_title = '次数';
          } else {
            var catch_title = '金额';
          }
          var daysIndex
          if (res.data.valid_days == 0) {
            daysIndex = 0
          } else if (res.data.valid_days == 30) {
            daysIndex = 1
          } else if (res.data.valid_days == 60) {
            daysIndex = 2
          } else if (res.data.valid_days == 90) {
            daysIndex = 3
          } else if (res.data.valid_days == 180) {
            daysIndex = 4
          } else if (res.data.valid_days == 365) {
            daysIndex = 5
          }

          if ('1' == res.data.grade) {
            that.data.valid_days = [{ days: '0', title: "永久" }]
          }
          var speedIndex = 0
          for(var i=0;i<that.data.point_speed_range.length;i++) {
            var obj = that.data.point_speed_range[i]
            if (res.data.point_speed == obj.speed) {
              speedIndex = i
              break
            }
          }
          that.setData({
            discount: '0.0' == res.data.discount ? '' : res.data.discount,
            point_speed:res.data.point_speed,
            grade: res.data,
            display: res.data.grade == '1' ? 'none' : '',
            catch_title: catch_title,
            user: wx.getStorageSync('user'),
            daysIndex: daysIndex,
            speedIndex: speedIndex,
            valid_days: that.data.valid_days
          })
        }
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
      catch_title: catch_title
    })
  },
  submit: function (e) {
    var id = e.detail.value.id
    var grade = e.detail.value.grade
    var name = e.detail.value.name
    var discount = e.detail.value.discount
    var point_speed = e.detail.value.point_speed
    var detail = e.detail.value.detail
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
      url: host + 'ssh_mch.php?action=update_grade',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
        id: id,
        grade: grade,
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
          title: "修改成功",
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
  delete: function (e) {
    var id = e.currentTarget.dataset.id
    wx.showModal({
      title: '删除此等级后该等级会员将会自动降一级，确定要删除吗？',
      content: '',
      success: function (res) {
        if (res.confirm) {
          wx.request({
            url: host + 'ssh_mch.php?action=delete_grade',
            data: {
              mch_id: wx.getStorageSync('mch_id'),
              id: id
            },
            header: {
              'content-type': 'application/json'
            },
            success: function (res) {
              app.loadMerchantGrades()
              wx.showToast({
                title: "删除成功",
                content: "",
                icon: 'success',
                duration: 2000,
                success: function (res) {
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
