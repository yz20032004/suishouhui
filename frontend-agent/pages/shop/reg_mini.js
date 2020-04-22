// pages/shop/reg_mini.js
const host = require('../../config').host + 'tt_'
Page({

  /**
   * 页面的初始数据
   */
  data: {
    childs:[],
    childIndex:0,
    fatherIndex:0,
    btn_disabled:true
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    var that = this
    wx.request({
      url: host + 'mch.php?action=get_father_categories',
      data: {
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        that.setData({
          fathers:res.data
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
    var mch = wx.getStorageSync('mch')
    this.setData({
      'mch_type':mch.mch_type
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

  submit: function (e) {
    var legal_persona_wechat = e.detail.value.legal_persona_wechat
    var name = this.data.name
    var code = this.data.code
    var legal_persona_name = this.data.legal_persona_name
    var fatherIndex = this.data.fatherIndex
    var childIndex  = this.data.childIndex
    if (!legal_persona_wechat) {
      wx.showModal({
        title: '请填写法人个人微信号',
        content: '',
        showCancel: false
      })
      return
    }
    if ('0' == fatherIndex) {
      wx.showModal({
        title: '请设置一级类目',
        content: '',
        showCancel: false
      })
      return
    }
    if ('0' == childIndex) {
      wx.showModal({
        title: '请设置二级类目',
        content: '',
        showCancel: false
      })
      return
    }
    var cate_first = this.data.fathers[this.data.fatherIndex].id
    var cate_second= this.data.childs[this.data.childIndex].id
    var first_class = this.data.fathers[this.data.fatherIndex].name
    var second_class= this.data.childs[this.data.childIndex].name
    wx.request({
      url: host + 'mch.php?action=reg_mini',
      data: {
        uid: wx.getStorageSync('uid'),
        mch_id:wx.getStorageSync('mch_id'),
        merchant_name:wx.getStorageSync('merchant_name'),
        name:name,
        code:code,
        legal_persona_name:legal_persona_name,
        legal_persona_wechat:legal_persona_wechat,
        cate_first:cate_first,
        cate_second:cate_second,
        first_class:first_class,
        second_class:second_class
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        if ('0' == res.data.errcode) {
          wx.showModal({
            title: '提交成功',
            content: '请提醒商户留意微信消息，及时完成小程序注册验证',
            showCancel: false,
            success(res) {
              wx.switchTab({
                url: '../index/index',
              })
            }
          })
        } else {
          wx.showModal({
            title: '提交信息有误',
            content: '错误代码'+res.data.errmsg,
            showCancel: false
          })
          return
        }
      }
    })
  },
  chooseLicenseImage: function () {
    var that = this
    wx.chooseImage({
      sourceType: ['camera', 'album'],
      sizeType: ['compressed'],
      count: 1,
      success: function (res) {
        that.setData({ 
          licenseImageList: res.tempFilePaths 
        }) 
        wx.serviceMarket.invokeService({
          service: 'wx79ac3de8be320b71', // '固定为服务商OCR的appid，非小程序appid',
          api: 'OcrAllInOne',
          data: {
          // 用 CDN 方法标记要上传并转换成 HTTP URL 的文件
          img_url: new wx.serviceMarket.CDN({
            type: 'filePath',
            filePath: res.tempFilePaths[0],
          }),
            data_type: 3,
            ocr_type: 7,
          },
        }).then(res => {
          var company_data = res.data.biz_license_res
          if ('' == company_data.enterprise_name.text) {
            wx.showModal({
              title: '无法识别企业名称',
              content: '请上传清晰的营业执照图片',
              showCancel: false
            })
            return
          } else if ('' == company_data.legal_representative.text) {
            wx.showModal({
              title: '无法识别企业法人姓名',
              content: '请上传清晰的营业执照图片',
              showCancel: false
            })
            return
          } else if ('' == company_data.reg_num.text) {
            wx.showModal({
              title: '无法识别企业代码',
              content: '请上传清晰的营业执照图片',
              showCancel: false
            })
            return
          }
          that.setData({
            name:company_data.enterprise_name.text,
            code:company_data.reg_num.text,
            legal_persona_name:company_data.legal_representative.text,
            btn_disabled:false
          })
        }).catch(err => {
          wx.showModal({
            title: 'fail',
            content: err + '',
          })
        })
      }
    })
  },
  previewLicenseImage: function (e) {
    var current = e.target.dataset.src

    wx.previewImage({
      current: current,
      urls: this.data.imageList
    })
  },
  previewImage: function (e) {
    var current = 'http://keyoucrmcard.oss-cn-hangzhou.aliyuncs.com/images/20200113/1578903669.jpg'
    wx.previewImage({
      current: current,
      urls: [current]
    })
  },
  bindFatherChange: function (e) {
    var that = this
    this.setData({
      fatherIndex: e.detail.value
    })
    var father = this.data.fathers[e.detail.value].id
    wx.request({
      url: host + 'mch.php?action=get_child_categories',
      data: {
        father:father
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        that.setData({
          childs:res.data
        })
      }
    })
  },
  bindChildChange: function (e) {
    this.setData({
      childIndex: e.detail.value
    })
  },
  back: function () {
    wx.navigateBack({
      delta: -1
    })
  }
})