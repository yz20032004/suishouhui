// pages/coupon/preview.js
const host = require('../../config').host
Page({
  data: {
    showBalanceBox:false
  },
  onLoad: function (options) {
    // 页面初始化 options为页面跳转所带来的参数
    var that = this
    var id = options.id
    wx.request({
      url: host + 'ssh_coupon.php?action=get_detail',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
        id: id
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        that.setData({
          coupon: res.data,
          balance:res.data.balance,
          user:wx.getStorageSync('user')
        })
      }
    })
    wx.request({
      url: host + 'ssh_coupon.php?action=get_icon_url',
      data: {
        id: id,
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        that.setData({
          icon_url: res.data.image_url
        })
      }
    })
  },
  onShow: function () {
  },
  open_image_list:function(){
    var that = this
    wx.navigateTo({
      url: 'image_list?id='+that.data.coupon.id,
    })
  },
  back: function () {
    wx.navigateBack({
      delta: 1
    })
  },
  publish: function (e) {
    if (this.data.coupon.balance <= 0) {
      wx.showModal({
        title: '投放失败',
        content: '该券库存为0，无法投放',
        showCancel:false
      })
      return
    }
    var coupon_id = this.data.coupon.id
    wx.navigateTo({
      url: 'publish?id=' + coupon_id,
    })
  },
  submit:function(e){
    var balance = parseInt(e.detail.value.balance)
    var that = this
    if (!balance) {
      wx.showModal({
        title: "请填写券库存",
        content: "",
        showCancel: false,
        confirmText: "确定"
      })
      return false
    }
    var new_balance = parseInt(that.data.coupon.balance) + balance
    if (new_balance > 10000) {
      wx.showModal({
        title: "增加后券库存不能超过1万张",
        content: "",
        showCancel: false,
        confirmText: "确定"
      })
      return false
    }
    wx.request({
      url: host + 'ssh_coupon.php?action=update_balance',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
        id: that.data.coupon.id,
        balance:balance
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        that.setData({
          balance: new_balance,
          showBalanceBox:false
        })
        wx.showToast({
          title: '增加库存成功',
          duration: 1500,
        })
      }
    })
  },
  edit:function(){
    var that = this
    wx.showActionSheet({
      itemList: ['增加库存', '添加图片', '作废'],
      success(res) {
        if (0 == res.tapIndex) {
          that.add_balance()
        } else if (1 == res.tapIndex) {
          wx.navigateTo({
            url: 'add_pic?id='+that.data.coupon.id+'&name='+that.data.coupon.name,
          })
        } else if ('2' == res.tapIndex) {
          wx.showModal({
            title: '提示',
            content: '作废后已发出的券仍可正常使用',
            success(res) {
              if (res.confirm) {
                that.disableCoupon()
              } else if (res.cancel) {
              }
            }
          })
        }
      },
      fail(res) {
      }
    })
  },
  disableCoupon: function () {
    var coupon_id = this.data.coupon.id
    wx.request({
      url: host + 'ssh_coupon.php?action=disable',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
        id: coupon_id,
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        if ('fail' == res.data.code) {
          wx.showModal({
            title: '操作失败',
            content: res.data.campaign + '活动依赖此券，请先停止该活动再执行此操作',
            showCancel: false,
          })
          return
        } else {
          wx.showToast({
            title: '已成功作废该券',
            duration: 1500,
            success: function () {
              wx.navigateBack({
                delta: 1
              })
            }
          })
        }
      }
    })
  },
  add_balance:function(){
    this.setData({
      showBalanceBox:true
    })
  },
  closeBalance:function(){
    this.setData({
      showBalanceBox: false
    })
  },
  onReady: function () {
    // 页面渲染完成
  },
  onHide: function () {
    // 页面隐藏
  },
  onUnload: function () {
    // 页面关闭
  }
})
