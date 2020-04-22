// pages/trade/mall_list.js
var sliderWidth = 96; // 需要设置slider的宽度，用于计算中间位置
const host = require('../../config').host
Page({
  data: {
    tabs: ["未快递", "未收货", "已收货"],
    activeIndex: 0,
    sliderOffset: 0,
    sliderLeft: 0,
    date_start: '',//默认起始时间  
    date_end: '',//默认结束时间 
  },
  onLoad: function (options) {
    // 页面初始化 options为页面跳转所带来的参数
    var that = this
    var myDate = new Date()
    var date = myDate.getDate()
    var hour = myDate.getHours()
    var date_start = myDate.getFullYear() + '-' + (myDate.getMonth() + 1) + '-' + date
    var tmpDate = myDate.getDate()-7
    var date_start_select = myDate.getFullYear() + '-' + (myDate.getMonth() + 1) + '-' + tmpDate
    wx.getSystemInfo({
      success: function (res) {
        that.setData({
          sliderLeft: (res.windowWidth / that.data.tabs.length - sliderWidth) / 2,
          sliderOffset: res.windowWidth / that.data.tabs.length * that.data.activeIndex,
          date_start_select:date_start_select,
          date_start: date_start,
          date_end: date_start
        });
      }
    });
  },
  onReady: function () {
 
    // 页面渲染完成
  },
  get_list:function(){
    var that = this
    wx.request({
      url: host + 'trade.php?action=get_mall_list',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
        date_start:that.data.date_start,
        date_end:that.data.date_end,
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        var unaccept_data = []
        var undelivery_data = []
        var unclosed_data = []
        var closed_data = []
        var tmpData = res.data
        var a=0,b=0,c=0,d=0
        for(var i=0;i<tmpData.length;i++) {
          if  ('0000-00-00 00:00:00' == tmpData[i].delivery_at) {
            undelivery_data[b] = tmpData[i]
            b++
          } else if ('0000-00-00 00:00:00' == tmpData[i].closed_at) {
            unclosed_data[c] = tmpData[i]
            c++
          } else {
            closed_data[d] = tmpData[i]
            d++
          }
        }
        that.setData({
          undelivery_data:undelivery_data,
          unclosed_data:unclosed_data,
          closed_data:closed_data
        })
      }
    })
  },
  bindDateStartChange:function(e){
    this.setData({
      page:1,
      date_start:e.detail.value
    })
    this.get_list()
  },
  bindDateEndChange: function (e) {
    this.setData({
      page: 1,
      date_end: e.detail.value
    })
    this.get_list()
  },
  onShow: function () {
    this.get_list()
  },
  onHide: function () {
    // 页面隐藏
  },
  onUnload: function () {
    // 页面关闭
  },
  tabClick: function (e) {
    this.setData({
      sliderOffset: e.currentTarget.offsetLeft,
      activeIndex: e.currentTarget.id
    });
  },
})