// pages/member/adjust_grade.js
const host = require('../../config').host
Page({

  /**
   * 页面的初始数据
   */
  data: {
    gradeIndex:0
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    var openid = options.openid
    var grade   = options.grade
    var that = this
    wx.request({
      url: host + 'mch.php?action=get_grades',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
      },
      header: {
        'content-type': 'application/json'
      },
      success: function(res) {
        that.setData({
          openid:openid,
          grades:res.data,
          member_grade:grade
        })
      }
    })
  },
  submit:function(e){
    var that = this
    if ('admin' != wx.getStorageSync('user_role')) {
      wx.showModal({
        title: '温馨提示',
        content: '体验者或非管理员身份不可执行此操作',
        showCancel: false
      })
      return
    }
    var grade = e.detail.value.grade
    if (this.data.member_grade == grade) {
      wx.showModal({
        title: '等级没有变动',
        showCancel:false
      })
      return
    }
    wx.request({
      url: host + 'member.php?action=update_grade',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
        openid:that.data.openid,
        grade:grade
      },
      header: {
        'content-type': 'application/json'
      },
      success: function(res) {
        wx.showModal({
          title: '操作成功',
          content: '',
          showCancel:false,
          success:function(){
            wx.navigateBack({
              delta: 1
            })
          }
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
  bindGradeChange: function(e) {
    this.setData({
      gradeIndex: e.detail.value
    })
  },
  back: function() {
    wx.navigateBack({
      delta: 1
    })
  }
})