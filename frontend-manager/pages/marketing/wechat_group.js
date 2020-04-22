// pages/marketing/wechat_group.js
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
  onLoad: function(options) {

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
    var that = this
    wx.request({
      url: host + 'mch.php?action=get_wechat_group',
      data: {
        mch_id: wx.getStorageSync('mch_id')
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        var expire_at =  '未添加微信群'
        var guide =  '本群不定期发放福利，禁止在群里发广告，违者踢出群'
        if (res.data) {
          expire_at = res.data.expire_at
          guide     = res.data.guide
        }
        that.setData({
          expire_at : expire_at,
          guide : guide
        })
      }
    })
  },

  chooseGroupImage: function() {
    var that = this
    wx.chooseImage({
      sourceType: ['camera', 'album'],
      sizeType: ['original'],
      count: 1,
      success: function(res) {
        that.setData({
          groupImageList: res.tempFilePaths
        })
        wx.uploadFile({
          url: host + 'mch.php?action=upload_wechat_group_photo',
          filePath: res.tempFilePaths[0],
          name: 'file',
          formData: {
            'openid': wx.getStorageSync('openid'),
            'mch_id':wx.getStorageSync('mch_id')
          },
          header: {
            'content-type': 'application/json'
          },
          success: function(res) {
            var result = JSON.parse(res.data)
            that.setData({
              group_photo_media:result.media_id
            })
          }
        })
      }
    })
  },
  previewGroupImage: function (e) {
    var current = e.target.dataset.src
    wx.previewImage({
      current: current,
      urls: this.data.imageList
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
    var media_id = e.detail.value.group_photo_media
    var guide    = e.detail.value.guide
    if (!media_id) {
      wx.showModal({
        title: "请上传群二维码",
        content: "",
        showCancel: false,
        confirmText: "确定"
      })
      return false;
    }
    wx.request({
      url: host + 'mch.php?action=update_wechat_group',
      data: {
        mch_id: wx.getStorageSync('mch_id'),
        media_id: media_id,
        guide:guide
      },
      header: {
        'content-type': 'application/json'
      },
      success: function (res) {
        wx.showModal({
          title: "设置微信群二维码成功",
          content: "",
          showCancel: false,
          confirmText: "确定",
          success:function(){
            wx.switchTab({
              url: '../marketing/index',
            })
          }
        })
      }
    })
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

  },

  /**
   * 用户点击右上角分享
   */
  onShareAppMessage: function() {

  }
})