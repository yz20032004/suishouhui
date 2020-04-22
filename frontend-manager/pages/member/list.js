// pages/member/list.js
const host = require('../../config').host
Page({
  data: {
    // 前台显示list
    memberList: [],
    // 当前页
    page: 1,
    // 总页数
    totalPage: null,
    pageCount: 10,
    grade: 0,
    keyword: '',
    grades: null
  },
  onLoad: function (options) {
    // 页面初始化 options为页面跳转所带来的参数
    var grades = ''
    var that = this
    wx.request({
      url: host + 'mch.php?action=get_grades',
      data: {
        mch_id: wx.getStorageSync('mch_id')
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        grades = res.data
        grades[grades.length] = { grade: 0, name: '全部等级' }
        that.setData({
          grades: grades,
          gradeIndex: grades.length - 1,
          user:wx.getStorageSync('user')
        })
      }
    })
    wx.request({
      url: host + 'mch.php?action=search_member',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
        grade: that.data.grade != 0 ? that.data.grade : 0,
        keyword: that.data.keyword != '' ? that.data.keyword : '',
        page_count: that.data.pageCount,
        page: that.data.page
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        that.setData({
          memberList: res.data.list,
          totalPage: res.data.page_total,
          total: res.data.total
        })
      }
    })
  },
  onReady: function () {
    // 页面渲染完成
  },
  onShow: function () {
  },
  onHide: function () {
    // 页面隐藏
  },
  onUnload: function () {
    // 页面关闭
  },
  submit: function (e) {
    this.data.page = 1
    var grade = e.detail.value.grade
    var keyword = e.detail.value.keyword
    if (grade == 0 && keyword == '') {
      wx.showModal({
        title: "请选择一个查询条件",
        content: "",
        showCancel: false,
        confirmText: "确定"
      })
      return false
    }
    var that = this
    wx.request({
      url: host + 'mch.php?action=search_member',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
        grade: grade,
        keyword: keyword,
        page_count: that.data.pageCount,
        page: that.data.page
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        that.setData({
          memberList: res.data.list,
          grade: grade,
          keyword: keyword,
          totalPage: res.data.page_total,
          total: res.data.total,
        })
      }
    })
  },
  /**
 * 页面上拉触底事件的处理函数
 */
  onReachBottom: function () {
    var that = this;
    // 当前页+1
    var page = that.data.page + 1;
    if (page <= that.data.totalPage) {
      wx.showLoading({
        title: '加载中',
      })
      // 请求后台，获取下一页的数据。
      wx.request({
        url: host + 'mch.php?action=search_member',
        data: {
          mch_id: wx.getStorageSync('mch_id'),
          grade: that.data.grade,
          keyword: that.data.keyword,
          page_count: that.data.pageCount,
          page: page
        },
        success: function (res) {
          wx.hideLoading()
          wx.stopPullDownRefresh()
          // 将新获取的数据 res.data.list，concat到前台显示的showlist中即可。
          that.setData({
            memberList: that.data.memberList.concat(res.data.list)
          })
        },
        fail: function (res) {
          wx.hideLoading()
        }
      })
    } else {
      var page = that.data.page;
    }
    that.setData({
      page: page,
    })
  },
  bindGradeChange: function (e) {
    this.setData({
      gradeIndex: e.detail.value
    })
  },
  previewMember: function (e) {
    var openid = e.currentTarget.dataset.openid
    wx.navigateTo({ url: 'detail?openid='+openid })
  },
  back: function () {
    wx.navigateBack({
      delta: 1
    })
  }
})
