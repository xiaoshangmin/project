// pages/detail/detail.js
const app = getApp()
Page({

  /**
   * 页面的初始数据
   */
  data: {
    content: {
      "id": 12,
      "content": "内哦你如果内哦你如果内哦你如果内哦你如果内哦你如果",
      pictures: [
        'https://zaaap-test-1254235226.cos.ap-guangzhou.myqcloud.com/video_cover/2020/06/16/8615f7ccede30fc62b6102d9a185c3a0.png',
        'https://zaaap-test-1254235226.cos.ap-guangzhou.myqcloud.com/video_cover/2020/06/15/06ffdca82d21b8bd8f89ab3b745c92fb.png',
        'https://zaaap-test-1254235226.cos.ap-guangzhou.myqcloud.com/video_cover/2020/06/16/8615f7ccede30fc62b6102d9a185c3a0.png',
        'https://zaaap-test-1254235226.cos.ap-guangzhou.myqcloud.com/avatar/user-avatar-20200515150841.png',
        'https://zaaap-test-1254235226.cos.ap-guangzhou.myqcloud.com/video_cover/2020/06/16/8615f7ccede30fc62b6102d9a185c3a0.png',
        'https://zaaap-test-1254235226.cos.ap-guangzhou.myqcloud.com/video_cover/2020/06/15/06ffdca82d21b8bd8f89ab3b745c92fb.png',
        'https://zaaap-test-1254235226.cos.ap-guangzhou.myqcloud.com/video_cover/2020/06/16/8615f7ccede30fc62b6102d9a185c3a0.png',
        'https://zaaap-test-1254235226.cos.ap-guangzhou.myqcloud.com/video_cover/2020/06/15/06ffdca82d21b8bd8f89ab3b745c92fb.png',
        'https://zaaap-test-1254235226.cos.ap-guangzhou.myqcloud.com/video_cover/2020/06/16/8615f7ccede30fc62b6102d9a185c3a0.png'
      ],
      "like": 88,
      "comment": 99,
      "collect": 13,
      "share": 45,
      "poi": {
        "address": "梅州市",
        "loc": "021",
        "lng": "23"
      },
      "user": {
        "id": "1",
        "avatar": 'https://zaaap-test-1254235226.cos.ap-guangzhou.myqcloud.com/video_cover/2020/06/16/8615f7ccede30fc62b6102d9a185c3a0.png',
        "username": "不知道取什么名字",
        "intro": "赚钱赚钱赚钱赚钱赚钱赚钱赚钱赚钱赚钱赚钱赚钱"
      }
    },
    comments: [{
      "id": 12,
      "content": "内哦你如果内哦你如果内哦你如果内哦你如果内哦你如果",
      "pictures": 'https://zaaap-test-1254235226.cos.ap-guangzhou.myqcloud.com/video_cover/2020/06/16/8615f7ccede30fc62b6102d9a185c3a0.png',
      "like": 88,
      "user": {
        "id": "1",
        "avatar": 'https://zaaap-test-1254235226.cos.ap-guangzhou.myqcloud.com/video_cover/2020/06/16/8615f7ccede30fc62b6102d9a185c3a0.png',
        "username": "赚钱赚钱赚钱赚钱赚钱赚钱赚钱赚钱赚钱赚钱赚钱",
        "intro": "赚钱赚钱赚钱赚钱赚钱赚钱赚钱赚钱赚钱赚钱赚钱"
      }
    }, {
      "id": 13,
      "content": "内哦你如果内哦你如果内哦你如果内哦你如果内哦你如果",
      "pictures": 'https://zaaap-test-1254235226.cos.ap-guangzhou.myqcloud.com/video_cover/2020/06/16/8615f7ccede30fc62b6102d9a185c3a0.png',
      "like": 88,
      "user": {
        "id": "1",
        "avatar": 'https://zaaap-test-1254235226.cos.ap-guangzhou.myqcloud.com/video_cover/2020/06/16/8615f7ccede30fc62b6102d9a185c3a0.png',
        "username": "不知道取什么名字",
        "intro": "赚钱赚钱赚钱赚钱赚钱赚钱赚钱赚钱赚钱赚钱赚钱"
      }
    }],
    hotComments: [{
      "id": 12,
      "content": "内哦你如果内哦你如果内哦你如果内哦你如果内哦你如果",
      "pictures": 'https://img0.zealer.com/video_list_cover/2019/9/30/1569816791121.jpeg',
      "like": 88,
      "user": {
        "id": "1",
        "avatar": 'https://zaaap-test-1254235226.cos.ap-guangzhou.myqcloud.com/video_cover/2020/06/16/8615f7ccede30fc62b6102d9a185c3a0.png',
        "username": "赚钱赚钱赚钱赚钱赚钱赚钱赚钱赚钱赚钱赚钱赚钱",
        "intro": "赚钱赚钱赚钱赚钱赚钱赚钱赚钱赚钱赚钱赚钱赚钱"
      }
    }, {
      "id": 12,
      "content": "内哦你如果内哦你如果内哦你如果内哦你如果内哦你如果",
      "pictures": 'https://i.loli.net/2020/06/30/EubZkAHr51xJqnU.png',
      "like": 999,
      "user": {
        "id": "1",
        "avatar": 'https://zaaap-test-1254235226.cos.ap-guangzhou.myqcloud.com/video_cover/2020/06/16/8615f7ccede30fc62b6102d9a185c3a0.png',
        "username": "不知道取什么名字",
        "intro": "赚钱赚钱赚钱赚钱赚钱赚钱赚钱赚钱赚钱赚钱赚钱"
      }
    }]
  },

  previewImage(e) {
    const current = e.target.dataset.src //获取当前点击的 图片 url
    wx.previewImage({
      current,
      urls: this.data.content.pictures
    })
  },

  previewCommentImage(e) {
    const current = e.target.dataset.src //获取当前点击的 图片 url
    wx.previewImage({
      current,
      urls: [current]
    })
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    console.log(options)
    wx.pageScrollTo({
      // scrollTop: 450,
      selector: '#comment',
      duration: 300
    })
    console.log(app.globalData.isAuthUserInfo)
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

  /**
   * 用户点击右上角分享
   */
  onShareAppMessage: function () {

  }
})