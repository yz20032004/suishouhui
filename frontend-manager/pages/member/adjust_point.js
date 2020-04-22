// pages/member/adjust_point.js
const host = require('../../config').host
Page({

  /**
   * 页面的初始数据
   */
  data: {
    operats: ["增加", "扣减"],
    operateIndex: 0,
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    var openid = options.openid
    var name = options.name
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
  bindOperateChange: function (e) {
    this.setData({
      operateIndex: e.detail.value
    })
  },
  setPoint: function (e) {
    if ('admin' != wx.getStorageSync('user_role')) {
      wx.showModal({
        title: '温馨提示',
        content: '体验者或非管理员身份不可执行此操作',
        showCancel: false
      })
      return
    }
    var that = this
    var operate = e.detail.value.operate
    var amount = e.detail.value.amount
    var userInfo = wx.getStorageSync('current_search_member')
    if (!amount) {
      wx.showModal({
        title: "请填写调整积分数量",
        content: "",
        showCancel: false,
        confirmText: "确定"
      })
      return false
    }
    if ('1' == operate && parseInt(amount) > parseInt(userInfo.point)) {
      wx.showModal({
        title: "扣减积分不能多于会员剩余积分",
        content: "",
        showCancel: false,
        confirmText: "确定"
      })
      return false
    }
    if ('1' == operate) {
      var operation = '扣减'
    } else {
      var operation = '增加'
    }
    wx.showModal({
      title: '你确定要给该会员' + operation + amount+'积分吗？',
      content: '',
      showCancel:true,
      success(res){
        if (res.confirm) {
          var user = wx.getStorageSync('user')
          wx.request({
            url: host + 'member.php?action=set_point',
            data: {
              mch_id: wx.getStorageSync('mch_id'),
              openid: userInfo.sub_openid,
              operate: operate,
              amount: amount,
              username: user.name
            },
            header: {
              'content-type': 'application/json'
            },
            success: function (res) {
              wx.showToast({
                title: "操作成功",
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