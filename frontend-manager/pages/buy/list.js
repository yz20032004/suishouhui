const host = require('../../config').host
var sliderWidth = 96; // 需要设置slider的宽度，用于计算中间位置
Page({

  /**
   * 页面的初始数据
   */
  data: {
    tabs: ["进行中", "未开始", "已结束"],
    activeIndex: 0,
    sliderOffset: 0,
    sliderLeft: 0,
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    var coupon_type = options.coupon_type
    var that = this;
    wx.getSystemInfo({
      success: function (res) {
        that.setData({
          sliderLeft: (res.windowWidth / that.data.tabs.length - sliderWidth) / 2,
          sliderOffset: res.windowWidth / that.data.tabs.length * that.data.activeIndex,
          coupon_type:coupon_type
        });
      }
    });
    this.get_list(coupon_type)
  },
  tabClick: function (e) {
    this.setData({
      sliderOffset: e.currentTarget.offsetLeft,
      activeIndex: e.currentTarget.id
    });
  },
  /**
   * 生命周期函数--监听页面初次渲染完成
   */
  onReady: function () {

  },
  onShow:function(){
    if (!this.data.coupon_type) {
      return
    } else {
      this.get_list(this.data.coupon_type)
    }
  },
  /**
   * 生命周期函数--监听页面显示
   */
  get_list: function (coupon_type) {
    var that = this
    wx.request({
      url: host + 'groupon.php?action=get_list',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
        coupon_type:coupon_type
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        that.setData({
          grouponData: res.data
        })
      }
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
  add:function(){
    var coupon_type = this.data.coupon_type
    wx.navigateTo({
      url: 'add?coupon_type='+coupon_type,
    })
  },
  back: function () {
    wx.navigateBack({
      delta: 1
    })
  }
})
