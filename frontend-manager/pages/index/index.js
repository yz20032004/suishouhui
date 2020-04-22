// pages/count/count.js
const host = require('../../config').host
const app = getApp()
Page({
  data: {
    merchant:{},
    search_member_display: false,
    search_disabled: true,
    shopIndex: 0
  },
  onLoad: function(options) {
    var that = this
    this.data.interval = setInterval(
      function() {
        if (wx.getStorageSync('user')) {
          clearInterval(that.data.interval)
          that.setData({
            merchant: wx.getStorageSync('mch'),
            user:wx.getStorageSync('user')
          })
          that.initIndex()
        }
      }, 200);
  },
  initIndex: function() {
    var user = wx.getStorageSync('user')
    wx.setNavigationBarTitle({
      title: user.merchant_name
    })
    this.setData({
      merchant_name: user.merchant_name
    })
    if (user.is_demo) {
      wx.showModal({
        title: '温馨提示',
        content: '您将以体验者身份进入系统',
        showCancel: false,
        success: function() {}
      })
    } else if ('0' == user.mch_id) {
      //微信商户号还在审核中
      wx.request({
        url: host + 'user.php?action=get_detail',
        data: {
          openid: wx.getStorageSync('openid')
        },
        header: {
          'content-type': 'application/json'
        },
        success: function(res) {
          wx.setStorageSync('user', res.data)
          wx.setStorageSync('mch_id', res.data.mch_id)
          if ('0' == res.data.mch_id) {
            wx.redirectTo({
              url: '../mini/verifying',
            })
            return
          }
        }
      })
    } else if ('0' == user.status) {
      wx.redirectTo({
        url: '../mini/disabled',
      })
      return
    }
    this.loadShops()
    app.loadMerchantPointRules()
    app.loadMerchantRechargeRules()
    app.loadMerchantExchangeRules()
    app.loadRebateCampaign()
    app.loadMerchantGrades()
  },
  onShow: function() {
    wx.removeStorageSync('current_search_member')
    this.setData({
      merchant: wx.getStorageSync('mch'),
      user:wx.getStorageSync('user')
    })
  },
  micropay: function() {
    var merchant = wx.getStorageSync('mch')
    if ('general' == merchant.mch_type) {
      wx.showModal({
        title: '无权限',
        content: '您暂未开通微信和支付宝收款，请联系您的业务员进行升级',
        showCancel: false
      })
      return
    }
    wx.navigateTo({
      url: 'micropay',
    })
  },
  consume: function() {
    var that = this
    this.setData({
      search_member_display: that.data.search_member_display ? false : true,
      todo: 'member_consume'
    })
  },
  search_member: function() {
    var that = this
    this.setData({
      todo: 'search',
      search_member_display: that.data.search_member_display ? false : true
    })
  },
  recharge: function() {
    var merchant = this.data.merchant
    if ('0' == merchant.is_recharge) {
      wx.showModal({
        title: '功能未开通',
        content: '请联系您的销售人员',
        showCancel:false
      })
      return
    }
    var that = this
    this.setData({
      todo: 'recharge',
      search_member_display: that.data.search_member_display ? false : true
    })
  },
  validate_coupon: function() {
    var expired_at = new Date(this.data.merchant.expired_at)
    var now = new Date()
    if (now > expired_at) {
      wx.showModal({
        title: '您的账号已到期，请及时续费',
        showCancel:false
      })
      return
    }
    wx.showModal({
      title: '请顾客出示优惠券二维码',
      content: '',
      showCancel: false,
      success: function() {
        wx.scanCode({
          success: function(res) {
            var code = res.result
            if ('8' == code.length || '10' == code.length || '12' == code.length) {
              wx.request({
                url: host + 'coupon.php?action=get_code_detail',
                data: {
                  mch_id: wx.getStorageSync('mch_id'),
                  code: code
                },
                header: {
                  'content-type': 'application/json'
                },
                success: function(res) {
                  if (res.data.id) {
                    wx.navigateTo({
                      url: '../coupon/consume?params=' + JSON.stringify(res.data)
                    })
                  } else {
                    wx.showModal({
                      title: "优惠券号码不存在",
                      content: "",
                      showCancel: false,
                      confirmText: "确定"
                    })
                    return false
                  }
                }
              })
            } else {
              wx.showModal({
                title: "无效的优惠券码",
                content: "",
                showCancel: false,
                confirmText: "确定"
              })
              return false
            }
          },
          fail: function(res) {}
        })
      }
    })
  },
  campaigns: function() {
    wx.navigateTo({
      url: '../campaign/list',
    })
  },
  groupon_trade:function(){
    wx.navigateTo({
      url: '../trade/groupon_list',
    })
  },
  coupon_consumed:function(){
    wx.navigateTo({
      url: '../trade/coupon_consumed',
    })
  },
  trade: function() {
    if ('0' == this.data.merchant.is_waimai && '0' == this.data.merchant.is_ordering) {
      wx.navigateTo({
        url: '../trade/list',
      })
    } else {
      wx.showActionSheet({
      itemList: ['所有订单', '外送订单管理', '在线点单管理', '商城订单管理'],
        success(e) {
          if (0 == e.tapIndex) {
            wx.navigateTo({
              url: '../trade/list',
            })
          } else if (1 == e.tapIndex) {
            wx.navigateTo({
              url: '../trade/waimai_list',
            })
          } else if (2 == e.tapIndex) {
            wx.navigateTo({
              url: '../trade/ordering_list',
            })
          } else {
            wx.navigateTo({
              url: '../trade/mall_list',
            })
          }
        }
      })
    }
  },
  bindInput: function(e) {
    this.setData({
      search_disabled: e.detail.value.length > 10 ? false : true
    })
  },
  scanSearch: function() {
    var that = this
    wx.scanCode({
      success: function(res) {
        var code = res.result
        that.getMember(code)
      },
      fail: function(res) {}
    })
  },
  inputSearch: function(e) {
    var mobile = e.detail.value.mobile
    this.getMember(mobile)
  },
  getMember: function(mobile) {
    var that = this
    wx.request({
      url: host + 'member.php?action=get_info',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
        mobile: mobile
      },
      header: {
        'content-type': 'application/json'
      },
      success: function(res) {
        if ('fail' === res.data) {
          wx.showModal({
            title: "该手机号查不到会员",
            content: "",
            showCancel: false,
            confirmText: "确定"
          })
          return false
        } else {
          wx.setStorageSync('current_search_member', res.data)
          if ('member_consume' == that.data.todo) {
            wx.navigateTo({
              url: 'cashpay'
            })
          } else if ('recharge' == that.data.todo) {
            wx.navigateTo({
              url: 'recharge_list'
            })
          } else {
            wx.navigateTo({
              url: '../member/detail?openid=' + res.data.sub_openid
            })
          }
        }
      }
    })
  },
  order_tables:function(){
    wx.navigateTo({
      url: '../order/index',
    })
  },
  huishenghuo: function() {
    var merchant = this.data.merchant
    if ('wxaa02c1c97542b1e4' == merchant.appid) {
      var mch_id = wx.getStorageSync('mch_id')
      wx.navigateToMiniProgram({
        appId: 'wxaa02c1c97542b1e4',
        path: 'pages/index/get_membercard?mch_id=' + mch_id,
        success(res) {
          // 打开成功
        }
      })
    } else {
      var shop = wx.getStorageSync('current_shop')
      var current = shop.card_url
      wx.previewImage({
        current: current,
        urls: [current]
      })
    }
  },
  members: function() {
    wx.navigateTo({
      url: '../member/list',
    })
  },
  help: function() {
    wx.showModal({
      title: '请加客服微信yangzhao717',
      content: '',
      showCancel: false
    })
  },
  onHide: function() {
    this.setData({
      search_member_display: false,
      search_disabled: true
    })
  },
  loadShops: function() {
    var that = this
    wx.request({
      url: host + 'shop.php?action=get_list',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
      },
      header: {
        'content-type': 'application/json'
      },
      success: function(res) {
        var shopIndex = 0
        var user = wx.getStorageSync('user')
        if ('0' != user.shop_id) {
          for (var i = 0; i < res.data.length; i++) {
            if (res.data[i].id == user.shop_id) {
              shopIndex = i
              break
            }
          }
          wx.setStorageSync('current_shop_id', user.shop_id)
          wx.setStorageSync('current_shop_name', user.branch_name)
        } else {
          wx.setStorageSync('current_shop_id', res.data[0].id)
          wx.setStorageSync('current_shop_name', res.data[0].branch_name)
        }
        wx.setStorageSync('current_shop', res.data[0])
        that.setData({
          shops: res.data,
          shopIndex: shopIndex
        })
      }
    })
  },
  bindShopChange: function(e) {
    var shopIndex = e.detail.value
    this.setData({
      shopIndex: shopIndex
    })
    wx.setStorageSync('current_shop_id', this.data.shops[shopIndex].id)
    wx.setStorageSync('current_shop_name', this.data.shops[shopIndex].branch_name)
  },
  getPhoneNumber(e) {
    var iv = e.detail.iv
    var encryptedData = e.detail.encryptedData
    var that = this
    if (undefined == iv) {
      return;
    }
    wx.checkSession({
      success: function () {
        var session_key = wx.getStorageSync('session_key')
        that.decryptPhoneNumber(session_key, iv, encryptedData)
      },
      fail: function () {
        wx.login({
          success: function (data) {
            wx.request({
              url: host + 'user.php?action=login',
              data: {
                js_code: data.code
              },
              success: function (res) {
                var session_key = res.data.session_key
                that.decryptPhoneNumber(session_key, iv, encryptedData)
              }
            })
          }
        })
      }
    })
  },
  decryptPhoneNumber: function (session_key, iv, encryptedData) {
    var that = this
    var openid = wx.getStorageSync('openid')
    wx.request({
      url: host + 'user.php?action=getphonenumber',
      data: {
        session_key: session_key,
        iv: iv,
        encryptedData: encryptedData,
        openid: openid
      },
      success: function (res) {
        var mobile = res.data
        wx.request({
          url: host + 'user.php?action=get_user',
          data: {
            mobile:mobile,
            openid:openid
          },
          success:function(res) {
            if (res.data == 'fail') {
              wx.setStorageSync('mobile', mobile)
              wx.navigateTo({
                url: '../mini/pay',
              })
            } else {
              res.data.is_demo = false
              wx.setStorageSync('user', res.data)
              wx.setStorageSync('mch_id', res.data.mch_id)
              wx.setStorageSync('user_role', 'admin')
              app.getMch(res.data.mch_id)
              wx.reLaunch({
                url: '../index/index',
              })
            }
          }
        })
      }
    })
  }
})
