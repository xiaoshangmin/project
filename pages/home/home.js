// pages/home/home.js
const api = require("../../utils/api");
const config = require('../../config');
const app = getApp()

Page({

  /**
   * 页面的初始数据
   */
  data: {
    contentImages: [],
    list: [],
    show: false
  },

  onChange(event) {
    this.setData({
      active: event.detail
    });
  },

  previewImage(e) {
    const current = e.target.dataset.src //获取当前点击的 图片 url
    const cid = e.target.dataset.cid
    console.log(this.data.contentImages[cid], cid)
    wx.previewImage({
      current,
      urls: this.data.contentImages[cid]
    })
  },

  toDetail(e) {
    const cid = e.currentTarget.dataset.cid
    wx.navigateTo({
      url: '../detail/detail?cid=' + cid,
    })
  },
  getList() {
    wx.showNavigationBarLoading(); //在标题栏中显示加载图标

    api.post(config.api.recommend).then(res => {
      // console.log(res)
      let list = res.data
      var contentImages = [];
      list.forEach(item => {
        contentImages[item.id] = []
        item.pictures.forEach(pic => {
          contentImages[item.id].push(pic.middlePicUrl)
        })
      })
      this.setData({
        list,
        contentImages
      })
      wx.hideNavigationBarLoading(); //完成停止加载图标
      wx.stopPullDownRefresh();
    })
  },

  more(e) {
    const cid = e.currentTarget.dataset.cid
    console.log(cid)
    wx.showToast({
      title: 'more',
    })
  },
  share(e) {
    const cid = e.currentTarget.dataset.cid
    console.log(cid, 8)
  },
  collect(e) {
    const cid = e.currentTarget.dataset.cid
    if (app.globalData.isAuthUserInfo) {
      wx.showToast({
        title: 'collect',
      })
    } else {
      this.setData({
        show: true
      })
    }

  },
  like(e) {
    const cid = e.currentTarget.dataset.cid
    if (app.globalData.isAuthUserInfo) {
      wx.showToast({
        title: 'like',
      })
      wx.vibrateShort()
    } else {
      this.setData({
        show: true
      })
    }
  },
  comment(e) {
    console.log(e)
    const cid = e.currentTarget.dataset.cid
    wx.navigateTo({
      url: '../detail/detail?cid=' + cid,
    })
  },

  toPublish() {
    if (app.globalData.isAuthUserInfo) {
      wx.navigateTo({
        url: '../publish/publish',
      })
    } else {
      this.setData({
        show: true
      })
    }
  },
  getUserInfo(event) {
    console.log(event.detail)
    app.getUserInfo()
  },

  onClose() {
    this.setData({
      close: false
    });
  },
  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    // wx.startPullDownRefresh()
    app.userInfoReadyCallback = res => {
      // console.log(res.userInfo)
    }
    this.getList();
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
    this.getTabBar().init();
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
  onShareAppMessage: function (res) {
    console.log(res)
    if (res.from === 'button') {
      return {
        title: '自定义转发标题',
        path: '/pages/detail/detail?id=123'
      }
    }
  }
})