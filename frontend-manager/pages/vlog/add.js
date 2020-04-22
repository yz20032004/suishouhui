const host = require('../../config').host
Page({
  data: {
    vedio_url:'',
    src: ''
  },
  onLoad:function(){
    var that = this
    wx.request({
      url: host + 'vlog.php?action=get_enable_groupons',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        var length = res.data.length
        res.data[length] = { id: 0, title: '请选择' }
        that.setData({
          groupons: res.data,
          grouponIndex: res.data.length - 1
        })
      }
    })
  },
  chooseVideo() {
    var that = this
    wx.chooseVideo({
      sourceType: ['album', 'camera'],
      compressed:true,
      success(res) {
        if (res.size > 10 * 1024 * 1024) {
          wx.showToast({
            title: '视频文件太大，请重新选择',
            icon:'none'
          })
          return
        }
        if (res.duration > 60) {
          wx.showToast({
            title: '视频不能超过60秒',
            icon:'none'
          })
          return
        }
        console.log(res)
        that.setData({
          src: res.tempFilePath
        })
        var width = res.width
        var height= res.height
        wx.uploadFile({
          url: host + 'vlog.php?action=upload_vedio',
          filePath: res.tempFilePath,
          name: 'file',
          formData: {
            'mch_id': wx.getStorageSync('mch_id'),
          },
          header: {
            'content-type': 'multipart/form-data'
          },
          success: function (res) {
            var result = JSON.parse(res.data)
            that.setData({
              'vedio_url':result.vedio_url,
              'width':width,
              'height':height
            })
          }
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
    var groupon_id = e.detail.value.groupon_id
    var detail     = e.detail.value.detail
    if (!this.data.vedio_url) {
      wx.showToast({
        title: '请拍摄或选择一段视频',
        icon:'none',
        duration: 2000
      })
      return false
    }
    
    var groupon_name = this.data.groupons[this.data.grouponIndex].title
    wx.request({
      url: host + 'vlog.php?action=create',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
        vedio_url:that.data.vedio_url,
        width:that.data.width,
        height:that.data.height,
        detail:detail,
        groupon_id:groupon_id,
        groupon_name:groupon_name,
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        var insert_id = res.data.id
        that.get_share_url(insert_id)
      }
    })
  },
  get_share_url:function(id){
    wx.showLoading({
      title: '生成海报中...',
    })
    var that = this
    var mch = wx.getStorageSync('mch')
    var appid = mch.appid
    wx.request({
      url: host + 'vlog.php?action=share',
      data: {
        id:id,
        appid:appid,
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        wx.hideLoading()
        var url = res.data
        wx.previewImage({
          current: url,
          urls: [url],
          success(){
            that.back()
          }
        })
      }
    })
  },
  bindGrouponChange: function (e) {
    var that = this
    this.setData({
      grouponIndex: e.detail.value,
    })
  },
  back: function () {
    wx.navigateBack({
      delta: 1
    })
  }
})
