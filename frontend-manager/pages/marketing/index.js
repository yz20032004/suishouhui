// pages/marketing/index.js
var sliderWidth = 96;
Page({

  /**
   * 页面的初始数据
   */
  data: {
    activeIndex: 0,
    sliderOffset: 0,
    sliderLeft: 0,
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    var that = this;
    var merchant = wx.getStorageSync('mch')
    if ('pay' != merchant.marketing_type) {
      var tabs = ["设置", "会员营销", '支付营销']
      wx.getSystemInfo({
        success: function (res) {
          that.setData({
            sliderLeft: (res.windowWidth / tabs.length - sliderWidth) / 2,
            sliderOffset: res.windowWidth / tabs.length * that.data.activeIndex,
            tabs:tabs,
            marketing_type:merchant.marketing_type,
            merchant:wx.getStorageSync('mch')
          });
        }
      });
    }
    this.setData({
      marketing_type:merchant.marketing_type,
      merchant: wx.getStorageSync('mch')
    })
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
  rebate:function(){
    wx.navigateTo({
      url: 'rebate',
    })
  },
  reduce: function () {
    wx.navigateTo({
      url: 'reduce',
    })
  },
  discount: function () {
    wx.navigateTo({
      url: 'discount',
    })
  },
  coupon: function () {
    wx.navigateTo({
      url: '../coupon/list',
    })
  },
  opengift: function () {
    wx.navigateTo({
      url: 'opengift',
    })
  },
  grade: function () {
    if ('1' == this.data.merchant.is_grade) {
      wx.navigateTo({
        url: 'grade',
      })
    } else {
      wx.navigateTo({
        url: 'grade_edit?id=1',
      })
    }
  },
  point: function () {
    wx.navigateTo({
      url: 'point',
    })
  },
  exchange: function () {
    wx.navigateTo({
      url: 'point_exchange',
    })
  },
  sendcoupon: function () {
    wx.navigateTo({
      url: 'sendcoupon',
    })
  },
  sendsms: function () {
    wx.navigateTo({
      url: 'send_msg',
    })
  },
  recharge: function () {
    wx.navigateTo({
      url: 'recharge_preview',
    })
  },
  campaigns:function(){
    wx.navigateTo({
      url: 'campaigns',
    })
  },
  group:function(){
    wx.navigateTo({
      url: 'wechat_group',
    })
  },
  waimai:function(){
    wx.navigateTo({
      url: 'waimai',
    })
  },
  ordering:function(){
    wx.navigateTo({
      url: 'ordering',
    })
  },
  decorate:function(){
    wx.navigateTo({
      url: 'decorate',
    })
  },
  member_day:function(){
    wx.navigateTo({
      url: 'member_day',
    })
  },
  membercard:function(){
    wx.navigateTo({
      url: 'membercard',
    })
  },
  wakeup:function(){
    wx.navigateTo({
      url: 'wakeup',
    })
  },
  paybuycoupon:function(){
    wx.navigateTo({
      url: 'paybuycoupon',
    })
  },
  groupon:function(){
    wx.navigateTo({
      url: '../buy/list?coupon_type=groupon',
    })
  },
  groupon_together:function(){
    wx.navigateTo({
      url: '../buy/together_list',
    })
  },
  mall:function(){
    wx.navigateTo({
      url: '../mall/index',
    })
  },
  mall_config:function(){
    wx.navigateTo({
      url: '../mall/config',
    })
  },
  buy:function(){
    wx.navigateTo({
      url: '../vipmember/index',
    })
  },
  recommend:function(){
    wx.navigateTo({
      url: 'recommend',
    })
  },
  vlog:function(){
    wx.navigateTo({
      url: '../vlog/add',
    })
  },
  paygift:function(){
    wx.navigateTo({
      url: 'paygift',
    })
  },
  payed_share:function(){
    wx.navigateTo({
      url: 'payed_share',
    })
  },
  lbs_coupon: function () {
    wx.navigateTo({
      url: 'lbs_coupon',
    })
  },
  rechargenopay:function(){
    wx.navigateTo({
      url: 'rechargenopay',
    })
  },
  timing:function(){
    wx.navigateTo({
      url: '../buy/list?coupon_type=timing',
    })
  },
  sharecoupon:function(){
    wx.navigateTo({
      url: 'sharecoupon',
    })
  }
})