// pages/mall/cart.js
const host = require('../../config').host
Page({

  /**
   * 页面的初始数据
   */
  data: {
    consume:0,
    cart_box:new Array,
    image_reduces: new Array,
    image_adds: new Array,
    image_reduce: 'reduce_disable',
    image_add: 'add',
    buy_totals: new Array,
    pay_disabled: false
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function(options) {
    var id = options.id
    var that = this
    var my_cart = wx.getStorageSync('my_cart')
    this.setData({
      member:wx.getStorageSync('member')
    })
    for(var i=0;i<my_cart.length;i++){
      that.get_detail(my_cart[i])
    }
  },
  get_detail:function(product_id){
    var that = this
    wx.request({
      url: host + 'huipay/mall.php?action=get_detail',
      data: {
        id: product_id
      },
      header: {
        'content-type': 'application/json'
      },
      success: function(res) {
        var consume = parseFloat(that.data.consume) + parseFloat(res.data.price)
        var cart_box = that.data.cart_box
        var buy_totals = that.data.buy_totals
        var image_adds = that.data.image_adds
        var image_reduces = that.data.image_reduces
        cart_box.push(res.data)
        buy_totals.push({id:res.data.id,total:1})
        image_adds.push('add')
        image_reduces.push('reduce_disable')
        that.setData({
          cart_box: cart_box,
          buy_totals:buy_totals,
          image_adds:image_adds,
          image_reduces:image_reduces,
          consume:consume
        })
      }
    })
  },
  delete_product:function(id){
    var my_cart = wx.getStorageSync('my_cart')
    my_cart.splice(id,1)
    wx.setStorageSync('my_cart', my_cart)
    if (my_cart.length > 0) {
      this.setData({
        cart_box:new Array,
        buy_totals: new Array,
        image_adds: new Array,
        image_reduces: new Array,
        consume: 0
      })
      for(var i=0;i<my_cart.length;i++){
        this.get_detail(my_cart[i])
      }
    } else {
      wx.navigateBack({
        delta:-1
      })
    }
  },
  reduce_total: function(e) {
    var id = e.target.dataset.id
    var buy_totals = this.data.buy_totals
    var image_adds = this.data.image_adds
    var image_reduces = this.data.image_reduces
    var buy_total = buy_totals[id].total - 1
    var product = this.data.cart_box[id]
    var image_reduce = 'reduce'
    var that = this
    if (buy_total == 0) {
      wx.showModal({
        title: '您要从购物车中删除该商品吗？',
        showCancel:true,
        success(res){
          if (res.confirm) {
            that.delete_product(id)
          } else {
            return
          }
        }
      })
    } else {
      if (buy_total == 1) {
        image_reduce = 'reduce_disable'
      }
      var consume = parseFloat(this.data.consume) - parseFloat(product.price)
      for(var i = 0;i<buy_totals.length;i++) {
        if (id == i) {
          buy_totals[i] = {id:buy_totals[i].id, total:buy_total}
          image_adds[i] = 'add'
          image_reduces[i] = image_reduce
        }
      }
      this.setData({
        buy_totals: buy_totals,
        image_adds: image_adds,
        image_reduces: image_reduces,
        consume: consume.toFixed(2)
      })
    }
  },
  add_total: function(e) {
    var id = e.target.dataset.id
    var buy_totals = this.data.buy_totals
    var image_adds = this.data.image_adds
    var image_reduces = this.data.image_reduces
    var buy_total = buy_totals[id].total + 1
    var image_add = 'add'
    var product = this.data.cart_box[id]
    if (product.single_limit != '0') {
      if (buy_total == product.single_limit) {
        image_add = 'add_disable'
      } else if (buy_total > product.single_limit) {
        wx.showToast({
          icon:'none',
          title: '该商品最多购买'+product.single_limit+'份',
        })
        return;
      }
    }
    var balance = product.total_limit - product.sold
    if (buy_total > balance) {
      wx.showToast({
        icon: 'none',
        title: '该商品最购买' + balance + '份',
      })
      return;
    }
    var consume = parseFloat(this.data.consume) + parseFloat(product.price)
    for(var i = 0;i<buy_totals.length;i++) {
      if (id == i) {
        buy_totals[i] = {id:buy_totals[i].id, total:buy_total}
        image_adds[i] = image_add
        image_reduces[i] = 'reduce'
      }
    }
    this.setData({
      buy_totals: buy_totals,
      image_adds: image_adds,
      image_reduces: image_reduces,
      consume: consume.toFixed(2)
    })
  },
  pay: function() {
    var consume = this.data.consume
    var that = this
    var my_cart = wx.getStorageSync('my_cart')
    var buy_totals = this.data.buy_totals
    var total_data = new Array
    for(var i=0;i< buy_totals.length;i++) {
      total_data.push(buy_totals[i].total)
    }
    wx.navigateTo({
      url: 'pay?amount='+consume+'&cart='+my_cart.join(',')+'&buy_totals='+total_data.join(','),
    })
  },
  getUser: function (e) {
    var that = this
    var user = e.detail.userInfo
    var encryptedData = e.detail.encryptedData
    var iv = e.detail.iv
    wx.request({
      url: host + 'huipay/user.php?action=update_user_info',
      data: {
        appid: wx.getStorageSync('appid'),
        key: 'placeholder',
        openid: wx.getStorageSync('openid'),
        mch_id: wx.getStorageSync('mch_id'),
        encryptedData: encryptedData,
        iv: iv,
        session_key: wx.getStorageSync('session_key')
      },
      success: function (res) {
        that.pay()
      }
    })
  },
  /**
   * 生命周期函数--监听页面初次渲染完成
   */
  onReady: function() {

  },

  /**
   * 生命周期函数--监听页面显示
   */
  onShow: function() {
  },

  /**
   * 生命周期函数--监听页面隐藏
   */
  onHide: function() {

  },

  /**
   * 生命周期函数--监听页面卸载
   */
  onUnload: function() {

  },

  /**
   * 页面相关事件处理函数--监听用户下拉动作
   */
  onPullDownRefresh: function() {

  },

  /**
   * 页面上拉触底事件的处理函数
   */
  onReachBottom: function() {

  }
})