// pages/home/home.js
Page({

  /**
   * 页面的初始数据
   */
  data: {
    list: [{
        "id": 12,
        "content": "内哦你如果内哦你如果内哦你如果内哦你如果内哦你如果",
        urls: [
          'https://zaaap-1254235226.cos.ap-guangzhou.myqcloud.com/video_cover/2020/03/03/5ffe3a5d97845893e4521e23f8dad58d.png?imageMogr2/format/gif/quality/30/w/500/h/400',
          'http://dn-odum9helk.qbox.me/resource/gogopher.jpg?imageView2/1/w/500/h/400/format/gif',
          'https://zaaap-test-1254235226.cos.ap-guangzhou.myqcloud.com/video_cover/2020/06/16/8615f7ccede30fc62b6102d9a185c3a0.png',
          'https://zaaap-1254235226.cos.ap-guangzhou.myqcloud.com/video_cover/2020/06/26/4474598990790799-1593152079679.png?imageView2/format/gif/1/w/500/h/400',
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
          "intro": "赚钱赚钱赚钱赚钱赚钱赚钱赚钱赚钱赚钱赚钱赚钱赚钱赚钱赚钱赚钱赚钱赚钱赚钱赚钱赚钱赚钱赚钱赚钱"
        }
      },
      {
        "id": 13,
        "content": "内哦你如果内哦你如果内哦你如果内哦你如果内哦你如果",
        urls: [
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
    ]
  },


  previewImage(e) {
    const current = e.target.dataset.src //获取当前点击的 图片 url
    const contentId = e.target.dataset.id
    wx.previewImage({
      current,
      urls: this.data.list[contentId].urls
    })
  },

  toDetail(e) {
    console.log(e)
    const cid = e.currentTarget.dataset.cid
    wx.navigateTo({
      url: '../detail/detail?cid=' + cid,
    })
  },

  getList() {
    var that = this
    wx.showNavigationBarLoading(); //在标题栏中显示加载图标
    wx.request({
      url: 'http://test.app.zaaap.cn/main/index',
      method: 'POST',
      data: {},
      success: function (res) {
        console.log(res) //完成调用后执行的函数
        let data = [{
            "id": 12,
            "content": "内哦你如果内哦你如果内哦你如果内哦你如果内哦你如果",
            urls: [
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
          {
            "id": 13,
            "content": "内哦你如果内哦你如果内哦你如果内哦你如果内哦你如果",
            urls: [
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
        ]

        that.setData({
          list: that.data.list.concat(data)
        })
      },
      fail: function (res) {
        console.log(res) //调用失败后执行的函数
      },
      complete: function (res) { //调用失败或结束都会执行的函数
        wx.hideNavigationBarLoading(); //完成停止加载图标
        wx.stopPullDownRefresh();
      }
    })
  },
  tabChange(e) {
    console.log('tab change', e)
  },
  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    wx.startPullDownRefresh()
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
    this.getList()
  },

  /**
   * 页面上拉触底事件的处理函数
   */
  onReachBottom: function () {
    // this.getList()
  },

  /**
   * 用户点击右上角分享
   */
  onShareAppMessage: function () {

  }
})