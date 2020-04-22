// pages/trade/list.js
const host = require('../../config').host
Page({
  data: {
    tables:[{id:0, table_name:'请选择', table_id:0}],
    tableIndex:0,
    date_start: '',//默认起始时间  
    date_end: '',//默认结束时间 
  },
  onLoad: function (options) {
    // 页面初始化 options为页面跳转所带来的参数
    var myDate = new Date()
    var date = myDate.getDate()
    var hour = myDate.getHours()
    var date_start = myDate.getFullYear() + '-' + (myDate.getMonth() + 1) + '-' + date
    var tmpDate = myDate.getDate()-7
    var date_start_select = myDate.getFullYear() + '-' + (myDate.getMonth() + 1) + '-' + tmpDate
    this.setData({
      date_start: date_start,
      date_end: date_start,
      date_start_select:date_start_select,
      table_id:options.hasOwnProperty('table_id') ? options.table_id : 0
    })
    this.get_list()
  },
  onReady: function () {
 
    // 页面渲染完成
  },
  get_list:function(){
    var that = this
    wx.request({
      url: host + 'trade.php?action=get_ordering_list',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
        table_id:that.data.table_id,
        date_start:that.data.date_start,
        date_end:that.data.date_end,
        shop_id:wx.getStorageSync('current_shop_id')
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        that.setData({
          tradeData: res.data
        })
      }
    })
  },
  bindDateStartChange:function(e){
    this.setData({
      date_start:e.detail.value
    })
    this.get_list()
  },
  bindDateEndChange: function (e) {
    this.setData({
      date_end: e.detail.value
    })
    this.get_list()
  },
  bindTableChange: function(e) {
    var that = this
    this.setData({
      tableIndex: e.detail.value,
      table_id:that.data.tables[e.detail.value].table_id
    })
    this.get_list()
  },
  getTables:function(){
    var that = this
    wx.request({
      url: host + 'tables.php?action=get_list',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
      },
      success: function (res) {
        for(var i=0;i<res.data.length;i++) {
          if (that.data.table_id == res.data[i].table_id) {
            that.data.tableIndex = i+1
          }
        }
        that.setData({
          tables:that.data.tables.concat(res.data),
          tableIndex:that.data.tableIndex
        })
      }
    })
  },
  onShow: function () {
    this.getTables()
  },
  onHide: function () {
    // 页面隐藏
  },
  onUnload: function () {
    // 页面关闭
  }
})
