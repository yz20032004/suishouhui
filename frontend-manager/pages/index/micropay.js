const host = require('../../config').host
Page({
  data: {
    idc: "clear",
    idb: "back",
    idt: "toggle",
    ido: ".",
    id0: "0",
    id1: "1",
    id2: "2",
    id3: "3",
    id4: "4",
    id5: "5",
    id6: "6",
    id7: "7",
    id8: "8",
    id9: "9",
    screenData: "0",
    arr: [],
    logs: [],
    times: 0
  },

  onLoad: function (options) {
  },
  onShow: function () {
    var merchant = wx.getStorageSync('mch')
    this.setData({
      screenData: 0,
      merchant_name:merchant.merchant_name,
      branch_name:wx.getStorageSync('branch_name')
    })
  },
  clickBtn: function (event) {
    var id = event.target.id;
    if (id == this.data.idb) {  //退格←
      var data = this.data.screenData;
      if (data == "0") {
        return;
      }
      data = data.substring(0, data.length - 1);
      if (data == "" || data == "-") {
        data = 0;
      }
      this.setData({ "screenData": data });
      this.data.arr.pop();
    } else if (id == this.data.idc) {  //清屏C
      this.setData({ "screenData": "0" });
      this.data.arr.length = 0;
    } else {
      var sd = this.data.screenData;
      var data;
      if (sd == '0') {
        if (id == this.data.ido) {
          data = '0.';
        } else {
          data = id;
        }
      } else {
        var length = (sd.split('.')).length - 1;
        if (id == this.data.ido && length == 1) {
          return false
        }
        var y = String(sd).indexOf(".");//获取小数点的位置
        if (y > 0) { //有小数点
          var count = String(sd).length - y;//获取小数点后的个数
          if (count == 3) {
            //两位小数则不再输入数字
            return;
          }
        }
        data = sd + id;
      }
      if (parseInt(data) > 9999) {
        return;
      }
      this.setData({ "screenData": data });
      this.data.arr.push(id);
    }
  },
  pay: function () {
    var amount = this.data.screenData
    this.setData({
      trade: amount
    })
    if ('0' == amount) {
      wx.showModal({
        title: "请输入金额",
        content: "",
        showCancel: false,
        confirmText: "确定"
      })
      return false
    }
    var that = this
    wx.scanCode({
      success: function (res) {
        wx.showToast({
          title: "正在支付",
          icon: "loading",
          duration: 20000,
          success: function (e) {
            var code = res.result
            var params = {
              code: code,
              trade: amount
            }
            var tag = parseInt(code.substr(0, 2))
            if (tag >= 10 && tag <= 15){
            //微信支付
              that.micropay_wechat(params)
            } else {
            //支付宝支付
              that.micropay_alipay(params)
            }
          }
        })
      },
      fail: function (res) {
      }
    })
  },
  micropay_wechat: function (params) {
    var that = this
    var user = wx.getStorageSync('user')
    wx.request({
      url: host + 'trade.php?action=micropay_wechat',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
        trade: params.trade,
        userid: user.id,
        username: wx.getStorageSync('current_shop_name') + user.name,
        merchant_name: user.merchant_name,
        code: params.code,
        shop_id:wx.getStorageSync('current_shop_id')
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        wx.hideToast()
        if ('SUCCESS' == res.data.result_code) {
          wx.redirectTo({
            url: 'pay_result?key=' + res.data.key,
          })
        } else if ('USERPAYING' == res.data.err_code) {
          wx.showLoading({
            title: '顾客输入密码中...',
          })
          var out_trade_no = res.data.out_trade_no
          setTimeout(function () { that.query_wechat(out_trade_no, res.data.key) }, 5000)
        } else if (undefined != res.data.err_code_des) {
          if (typeof (res.data.err_code_des) == 'string') {
            var des = res.data.err_code_des
          } else {
            var des = res.data.err_code_des.msg
          }
          wx.showModal({
            title: "收款失败",
            content: des,
            showCancel: false,
            confirmText: "确定"
          })
          return false
        } else {
          wx.showModal({
            title: "收款失败",
            content: res.data.return_msg,
            showCancel: false,
            confirmText: "确定"
          })
          return false
        }
      }
    })
  },
  micropay_alipay: function (params) {
    var that = this
    var user = wx.getStorageSync('user')
    wx.request({
      url: host + 'trade.php?action=micropay_alipay',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
        trade: params.trade,
        userid: user.id,
        username: wx.getStorageSync('current_shop_name') + user.name,
        merchant_name: user.merchant_name,
        code: params.code,
        shop_id: wx.getStorageSync('current_shop_id')
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        wx.hideToast()
        if ('10000' == res.data.code) {
          wx.redirectTo({
            url: 'pay_result?key=' + res.data.key,
          })
        } else if ('USERPAYING' == res.data.err_code) {
          wx.showLoading({
            title: '顾客输入密码中...',
          })
          var out_trade_no = res.data.out_trade_no
          setTimeout(function () { that.query(out_trade_no, res.data.key) }, 5000)
        } else {
          var content = undefined == res.data.sub_msg ? res.data.msg : res.data.sub_msg
          wx.showModal({
            title: "收款失败",
            content: content,
            showCancel: false,
            confirmText: "确定"
          })
          return false
        }
      }
    })
  },
  /**
   * 生命周期函数--监听页面隐藏
   */
  onHide: function () {
  },
  query_wechat: function (out_trade_no, key) {
    var that = this
    that.data.times = that.data.times + 1
    if (that.data.times > 3) {
      wx.hideLoading()
      wx.showModal({
        title: "收款失败",
        content: '顾客支付超时',
        showCancel: false,
        confirmText: "确定",
        success(res) {
          wx.switchTab({
            url: 'index',
          })
        }
      })
    }
    wx.request({
      url: host + 'trade.php?action=micropay_query',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
        trade: that.data.trade,
        key: key,
        out_trade_no: out_trade_no
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        if ('SUCCESS' == res.data.return_code) {
          if ('SUCCESS' == res.data.trade_state) {
            wx.hideLoading()
            wx.redirectTo({
              url: 'pay_result?key=' + key,
            })
          } else if ('USERPAYING' == res.data.trade_state) {
            var out_trade_no = res.data.out_trade_no
            setTimeout(function () { that.query_wechat(out_trade_no, key) }, 5000)
          } else {
            wx.hideLoading()
            wx.showModal({
              title: '收款失败',
              content: '顾客支付失败',
              success(res) {
                wx.switchTab({
                  url: 'index',
                })
              }
            })
          }
        } else {
          wx.hideLoading()
          var des = res.data.err_code_des
          wx.showModal({
            title: "收款失败",
            content: des,
            showCancel: false,
            confirmText: "确定",
            success(res) {
              wx.switchTab({
                url: 'index',
              })
            }
          })
        }
      }
    })
  }
})
