// pages/setting/add_function.js
Page({

  /**
   * 页面的初始数据
   */
  data: {
    showBuyBoxTip:false
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    this.setData({
      merchant:wx.getStorageSync('mch')
    })
  },
  buy:function(e){
    var cost = e.currentTarget.dataset.cost
    this.setData({
      cost:cost,
      showBuyBoxTip: true
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
    this.setData({
      showBuyBoxTip: false,
    })
  },

  /**
   * 生命周期函数--监听页面卸载
   */
  onUnload: function () {

  },
  closeBuyBox: function() {
    this.setData({
      showBuyBoxTip: false
    })
  },
})