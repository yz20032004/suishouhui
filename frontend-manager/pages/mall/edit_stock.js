// pages/mall/edit_stock.js
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
    var product_id = options.id
    this.setData({
      product_id:product_id
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
  setStock: function (e) {
    if ('admin' != wx.getStorageSync('user_role')) {
      wx.showModal({
        title: '温馨提示',
        content: '体验者或非管理员身份不可执行此操作',
        showCancel: false
      })
      return
    }
    var that = this
    var stock = e.detail.value.stock
    if (!stock) {
      wx.showModal({
        title: "请填写库存数",
        content: "",
        showCancel: false,
        confirmText: "确定"
      })
      return false
    }
    wx.showModal({
      title: '你确定要给该商品调整库存数为' + stock+'份吗？',
      content: '',
      showCancel:true,
      success(res){
        if (res.confirm) {
          wx.request({
            url: host + 'mall.php?action=set_stock',
            data: {
              mch_id: wx.getStorageSync('mch_id'),
              id: that.data.product_id,
              stock:stock
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