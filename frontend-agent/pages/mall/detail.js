// pages/buy/detail.js
const host = require('../../config').host + 'ssh_'
Page({

  /**
   * 页面的初始数据
   */
  data: {
    doIndex:0,
    do_options: ["下架","增加库存"]
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    var id = options.id
    var that = this
    wx.request({
      url: host + 'mall.php?action=get_detail',
      data: {
        id: id
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        that.setData({
          mallData:res.data,
          do_options:'0' == res.data.is_selling ? ["上架","更改库存"] : ["下架","更改库存"]
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
  open: function (e) {
    var that = this
    if ('admin' != wx.getStorageSync('user_role')) {
      wx.showModal({
        title: '温馨提示',
        content: '体验者或非管理员身份不可执行此操作',
        showCancel: false
      })
      return
    }
    wx.showModal({
      title: '确认要上架此商品吗？',
      content: '',
      success(res){
        if (res.cancel){
          return
        } else {
          wx.request({
            url: host + 'mall.php?action=open',
            data: {
              id: that.data.mallData.id
            },
            header: {
              'content-type': 'application/json'
            },
            success: function (res) {
              wx.showModal({
                title: "商品已上架",
                content: "",
                showCancel: false,
                confirmText: "确定",
                success: function () {
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
  stop: function (e) {
    var that = this
    if ('admin' != wx.getStorageSync('user_role')) {
      wx.showModal({
        title: '温馨提示',
        content: '体验者或非管理员身份不可执行此操作',
        showCancel: false
      })
      return
    }
    wx.showModal({
      title: '确认要下架此商品吗？',
      content: '',
      success(res){
        if (res.cancel){
          return
        } else {
          wx.request({
            url: host + 'mall.php?action=stop',
            data: {
              id: that.data.mallData.id
            },
            header: {
              'content-type': 'application/json'
            },
            success: function (res) {
              wx.showModal({
                title: "商品已下架",
                content: "",
                showCancel: false,
                confirmText: "确定",
                success: function () {
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
  open_sold_list:function(){
    var that = this
    wx.navigateTo({
      url: 'sold_list?product_id='+that.data.mallData.id,
    })
  },
  copydata:function(){
    var that = this
    wx.setClipboardData({
      data: 'pages/mall/detail?id='+that.data.mallData.id,
      success(res) {
        wx.getClipboardData({
          success(res) {
          }
        })
      }
    })
  },
  getqrcode:function(){
    var that = this
    if (this.data.mallData.qrcode_url) {
      this.previewGrouponQrCode(this.data.mallData.qrcode_url)
      return
    }
    wx.request({
      url: host + 'mall.php?action=get_qrcode',
      data: {
        id: that.data.mallData.id
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        that.previewMallQrCode(res.data.qrcode_url)
      }
    })
  },
  previewMallQrCode:function(url){
    wx.previewImage({
      current: url,
      urls: [url]
    })
  },
  bindOptionChange: function (e) {
    if ('0' == e.detail.value) {
      if ('0' == this.data.mallData.is_selling) {
        this.open()
      } else {
        this.stop()
      }
    } else if ('1' == e.detail.value) {
      wx.navigateTo({
        url: 'edit_stock?id='+this.data.mallData.id,
      })
    }
  },
  back: function () {
    wx.navigateBack({
      delta: 1
    })
  }
})
