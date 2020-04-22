// pages/buy/add.js
const host = require('../../config').host + 'ssh_'
var myDate = new Date()
Page({

  /**
   * 页面的初始数据
   */
  data: {
    distribute_display:'none',
    distribute_grade_title:'所有人',
    display: 'none',
    amount_display: 'none',
    amount:0,
    single_limit: '不限制',
    totalIndex: 0,
    counts: ['不限制', 1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
    countIndex: 0,
    icon_url: '',
    image_url: new Array
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    this.getGrades()
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
    // 页面显示
    this.setData({
      merchant:wx.getStorageSync('mch')
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
  getGrades:function(){
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
        that.setData({
          grades: res.data,
          gradeIndex: 0,
        })
      }
    })
  },
  submit:function(e){
    if ('admin' != wx.getStorageSync('user_role')) {
      wx.showModal({
        title: '温馨提示',
        content: '体验者或非管理员身份不可执行此操作',
        showCancel: false
      })
      return
    }
    var that = this
    var title     = e.detail.value.title
    var amount    = e.detail.value.amount
    var price     = e.detail.value.price
    var detail    = e.detail.value.detail
    var total_limit = e.detail.value.total_limit
    var single_limit = this.data.countIndex != 0 ? e.detail.value.single_limit : 0
    var is_member_limit = e.detail.value.hasOwnProperty('is_member_limit') ? e.detail.value.is_member_limit : 0
    var is_distribute = e.detail.value.hasOwnProperty('is_distribute') ? e.detail.value.is_distribute : 0
    var grade = e.detail.value.hasOwnProperty('grade') ? e.detail.value.grade : 0
    var bonus = e.detail.value.hasOwnProperty('bonus') ? e.detail.value.bonus : 0
    var image_url_str = ''
    var length = this.data.image_url.length
    if (length > 0) {
      image_url_str = this.data.image_url.toString()
    }
    if (!title) {
      wx.showToast({
        title: '请填写商品名称',
        icon: 'none',
        duration: 2000
      })
      return false
    }
    if (!price) {
      wx.showToast({
        title: '请填写售卖价格',
        icon: 'none',
        duration: 2000
      })
      return false
    }
    if (parseInt(price) >= parseInt(amount)) {
      wx.showToast({
        title: '售卖价格必须低于原价',
        icon: 'none',
        duration: 2000
      })
      return false
    }
    if (!this.data.icon_url) {
      wx.showModal({
        title: "请上传商品封面照片",
        content: "",
        showCancel: false,
        confirmText: "确定"
      })
      return false
    }
    if (!total_limit) {
      wx.showToast({
        title: '请填写库存数',
        icon: 'none',
        duration: 2000
      })
      return false
    }
    if (is_distribute) {
      if (!bonus) {
        wx.showToast({
          title: '请填写分销提成金额',
          icon: 'none',
          duration: 2000
        })
        return false
      }
      var d = bonus / price
      if (d > 0.3) {
        wx.showToast({
          title: '分销提成金额不能高于售价的30%',
          icon: 'none',
          duration: 2000
        })
        return false
      }
    }
    wx.request({
      url: host + 'mall.php?action=create',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
        title:title,
        amount:amount,
        price:price,
        detail:detail,
        total_limit: total_limit,
        single_limit: single_limit,
        is_member_limit: is_member_limit,
        is_distribute:is_distribute,
        grade:grade,
        bonus:bonus,
        icon_url:that.data.icon_url,
        image_url:image_url_str
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        if ('fail' == res.data) {
          wx.showToast({
            title: '已经有此产品了',
            icon:'none',
            duration: 2000
          })
          return false
        } else {
          wx.showToast({
            title: '创建成功',
            icon:'success',
            duration: 2000,
            success(res){
              wx.navigateBack({
                url:'list'
              })
            }
          })
        }
      }
    })
  },
  bindGradeChange: function (e) {
    var that = this
    this.setData({
      gradeIndex: e.detail.value,
      distribute_grade_title: that.data.grades[e.detail.value].name
    })
  },
  bindCountChange: function (e) {
    var that = this
    var single_limit = that.data.counts[e.detail.value] + '份'
    if (e.detail.value == 0) {
      var single_limit = '不限制'
    }
    this.setData({
      countIndex: e.detail.value,
      single_limit: single_limit
    })
  },
  distributeSwitch: function (e) {
    var isChecked = e.detail.value
    if (isChecked) {
      this.setData({
        distribute_display: ''
      })
    } else {
      this.setData({
        distribute_display: 'none',
      })
    }
  },
  chooseImage: function () {
    var that = this
    wx.chooseImage({
      sourceType: ['camera', 'album'],
      sizeType: ['compressed'],
      count: 3,
      success: function (res) {
        that.setData({
          imageList: res.tempFilePaths
        })
        wx.uploadFile({
          url: host + 'mall.php?action=upload_photo',
          filePath: res.tempFilePaths[0],
          name: 'file',
          formData: {
          },
          header: {
            'content-type': 'application/json'
          },
          success: function (res) {
            var result = JSON.parse(res.data)
            that.setData({
              'icon_url':result.pic_url
            })
          }
        })
      }
    })
  },
  chooseTextImage: function () {
    var that = this
    var length = 0
    wx.chooseImage({
      sourceType: ['camera', 'album'],
      sizeType: ['compressed'],
      count: 9,
      success: function (res) {
        that.setData({
          textImageList: res.tempFilePaths
        })
        for(var i=0;i<res.tempFilePaths.length;i++){
          wx.uploadFile({
            url: host + 'mall.php?action=upload_photo',
            filePath: res.tempFilePaths[i],
            name: 'file',
            formData: {
              'coupon_id': that.data.coupon_id,
            },
            header: {
              'content-type': 'application/json'
            },
            success: function (res) {
              var result = JSON.parse(res.data)
              length = that.data.image_url.length
              that.data.image_url[length] = result.pic_url
            }
          })
        }
      }
    })
  },
  previewImage: function (e) {
    var current = e.target.dataset.src
    wx.previewImage({
      current: current,
      urls: this.data.imageList
    })
  },
  previewDetailImage: function (e) {
    var current = e.target.dataset.src
    wx.previewImage({
      current: current,
      urls: this.data.textImageList
    })
  },
  back: function () {
    wx.navigateBack({
      delta: 1
    })
  }
})
