// pages/together/detail.js
const host = require('../../config').host
Page({

  /**
   * 页面的初始数据
   */
  data: {
    buy_disabled: false,
    buy_title: '发起拼单',
    together_no: ''
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    wx.showLoading({
      title: '加载中',
    })
    var id = options.id
    var together_no = options.hasOwnProperty('together_no') ? options.together_no : ''
    var that = this
    wx.request({
      url: host + 'huipay/together.php?action=get_detail',
      data: {
        id: id
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        var now = new Date()
        var date_start = new Date(res.data.date_start)
        var date_end = new Date(res.data.date_end)
        var dateTime = date_end.setDate(date_end.getDate() + 1);
        date_end = new Date(dateTime)
        if (now < date_start) {
          var buy_disable = true
          var buy_title = '活动未开始'
        } else if (now > date_end || '1' == res.data.is_stop) {
          var buy_disable = true
          var buy_title = '活动已结束'
        } else {
          buy_disable = false
          buy_title = '发起拼团'
        }
        if (res.data.is_limit == '1' && parseInt(res.data.total_limit) <= 0) {
          buy_disable = true
          buy_title = '已售完'
        }
        wx.setNavigationBarTitle({
          title: res.data.shop.business_name
        })
        that.setData({
          together: res.data,
          shop: res.data.shop,
          buy_disable: buy_disable,
          buy_title: buy_title,
          balance: res.data.total_limit - res.data.sold > 0 ? res.data.total_limit - res.data.sold : 0
        })
        if (together_no) {
          that.updateTogetherInfo(together_no)
          that.getTogetherList(together_no)
        }
      }
    })
  },
  buy: function () {
    if (this.data.buy_disable) {
      return
    }
    wx.navigateTo({
      url: 'confirm?id=' + this.data.together.id+'&together_no='+this.data.together_no+'&expire_times='+this.data.together.expire_times,
    })
  },
  call: function (e) {
    var phone = e.target.dataset.phone
    wx.makePhoneCall({
      phoneNumber: phone //仅为示例，并非真实的电话号码
    })
  },
  more: function () {
    wx.switchTab({
      url: '../campaign/index',
    })
  },
  /**
   * 生命周期函数--监听页面初次渲染完成
   */
  onReady: function () {
    wx.hideLoading()
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
  updateTogetherInfo(together_no) {
    var that = this
    wx.request({
      url: host + 'huipay/together.php?action=get_detail_by_no',
      data: {
        together_no: together_no
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        var buy_disable = false
        var buy_title = '参与拼单'
        if ('success' == res.data.together_status) {
          buy_title = '拼单已满'
          buy_disable = true
        } else if ('expire' == res.data.together_status) {
          var buy_title = '拼单已过期'
          buy_disable = true
        } else if (res.data.openid == wx.getStorageSync('openid')) {
          var buy_title = '拼单进行中'
          buy_disable = true
        }
        
        that.setData({
          buy_disable: buy_disable,
          buy_title: buy_title,
          together_no: together_no,
          head_created_at: res.data.created_at.replace(/\-/g, "/")
        })
        var timerInterval = setInterval(that.countDown, 1000);
        that.setData({
          timerInterval: timerInterval
        })
      }
    })
  },
  getTogetherList(together_no) {
    var that = this
    wx.request({
      url: host + 'huipay/together.php?action=get_list',
      data: {
        together_no: together_no
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        that.setData({
          together_list: res.data,
          need_people: that.data.together.people - res.data.length
        })
      }
    })
  },
  //定时触发方法
  countDown() {
    var that = this
    var pastDate = new Date(that.data.head_created_at);
    pastDate = new Date(pastDate.getTime() + that.data.together.expire_times * 3600 * 1000);
    var nowPastTime = pastDate.getTime();

    var now = new Date().getTime();
    var secs = (nowPastTime - now) / 1000;
    if (secs < 0) {
      clearInterval(this.data.timerInterval);
      this.setData({
        timer: 0,
        buy_title : '拼单已过期',
        buy_disable : true
      })
      return
    }
    var mesc = this.dateformate(secs);
    if (mesc == "00:00:00") {
      clearInterval(this.data.timerInterval);
    } else {
      this.setData({
        timer: mesc
      })
    }
  },
  // 时间格式化输出，将时间戳转为 倒计时时间
  dateformate(micro_second) {
    var second = micro_second; //总的秒数
    // 天数位   
    var day = Math.floor(second / 3600 / 24);
    var dayStr = day.toString();
    if (dayStr.length == 1) dayStr = '0' + dayStr;
    // 小时位   
    //var hr = Math.floor(second / 3600 % 24);
    var hr = Math.floor(second / 3600); //直接转为小时 没有天 超过1天为24小时以上
    var hrStr = hr.toString();
    if (hrStr.length == 1) hrStr = '0' + hrStr;
    // 分钟位  
    var min = Math.floor(second / 60 % 60);
    var minStr = min.toString();
    if (minStr.length == 1) minStr = '0' + minStr;
    // 秒位  
    var sec = Math.floor(second % 60);
    var secStr = sec.toString();
    if (secStr.length == 1) secStr = '0' + secStr;
    return hrStr + ":" + minStr + ":" + secStr;
  },
  /**
 * 用户点击右上角分享
 */
  onShareAppMessage: function () {
  },
  backtoindex: function () {
    wx.switchTab({
      url: '../index/index',
    })
  }
})