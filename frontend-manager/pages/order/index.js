// pages/order/index.js
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
    var that = this
    wx.request({
      url: host + 'tables.php?action=get_list',
      data: {
        mch_id:wx.getStorageSync('mch_id'),
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        that.setData({
          tables:res.data
        })
      }
    })
  },
  add_table:function(){
    wx.navigateTo({
      url: 'add_table',
    })
  },
  touch_used_table:function(e){
    var that = this
    var table_id = e.currentTarget.dataset.id
    wx.showActionSheet({
      itemList: ['点单详情', '清台', '点单记录', '删除此桌台'],
        success(e) {
          if (0 == e.tapIndex) {
            wx.navigateTo({
              url: 'detail?table_id='+table_id,
            })
          } else if (1 == e.tapIndex) {
            that.clear_table(table_id)
          } else if (2 == e.tapIndex) {
            wx.navigateTo({
              url: '../trade/ordering_list?table_id='+table_id,
            })
          } else {
            wx.navigateTo({
              url: '../trade/waimai_list',
            })
          }
        }
    })
  },
  touch_free_table:function(e){
    var that = this
    var table_id = e.currentTarget.dataset.id
    wx.showActionSheet({
      itemList: ['订单记录', '导出点餐码','删除此桌台'],
        success(e) {
          if (0 == e.tapIndex) {
            wx.navigateTo({
              url: '../trade/ordering_list?table_id='+table_id,
            })
          } else if (1 == e.tapIndex) {
            that.get_qrcode(table_id)
          } else {
            that.delete_table(table_id)
          }
        }
    })
  },
  clear_table:function(table_id) {
    var that = this
    wx.showModal({
      title:'确定要对此桌清台吗？',
      success(res){
        if (res.confirm) {
          wx.request({
            url: host + 'tables.php?action=clear',
            data: {
              mch_id: wx.getStorageSync('mch_id'),
              table_id:table_id
            },
            header: {
              'content-type': 'application/json'
            },
            success: function (res) {
              wx.showToast({
                title: '成功清台',
                icon: 'success',
                duration: 2000,
                success(res) {
                  that.onShow()
                }
              })
            }
          })
        }
      }
    })
  },
  get_qrcode:function(table_id){
    var that = this
    wx.request({
      url: host + 'tables.php?action=get_detail',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
        table_id:table_id
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        var url = res.data.qrcode_url
        wx.previewImage({
          current: url,
          urls: [url]
        })
      }
    })
  },
  delete_table:function(table_id) {
    var that = this
    wx.showModal({
      title:'确定要删除此桌台吗？',
      success(res){
        if (res.confirm) {

          wx.request({
            url: host + 'tables.php?action=delete',
            data: {
              mch_id: wx.getStorageSync('mch_id'),
              table_id:table_id
            },
            header: {
              'content-type': 'application/json'
            },
            success: function (res) {
              wx.showToast({
                title: '成功删除',
                icon: 'success',
                duration: 2000,
                success(res) {
                  that.onShow()
                }
              })
            }
          })
        }
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
})