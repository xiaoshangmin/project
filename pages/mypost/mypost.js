// pages/home/home.js
// pages/home/home.js
const api = require("../../utils/api");
const config = require('../../config');
const app = getApp()

Page({

  /**
   * 页面的初始数据
   */
  data: {
    likedList: [],
    contentImages: [],
    list: [],
    show: false,
    p: 1,
    ps: 10,
    finish: false,
    show: false,
    moreId: 0,
    actions: [{
      name: '删除',
      color: 'red'
    }],
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
    let data = {
      p: this.data.p,
      ps: this.data.ps
    }
    let contentImages = this.data.contentImages;
    let likedList = this.data.likedList;
    let list = this.data.list;
    api.post(config.api.mypost, data).then(res => {
      console.log(res)
      let data = res.data
      data.forEach(item => {
        likedList[item.id] = {
          "liked": item.liked,
          "like_count": item.like_count
        };
        contentImages[item.id] = []
        item.pictures.forEach(pic => {
          contentImages[item.id].push(pic.middlePicUrl)
        })
      })
      list = list.concat(data)
      this.setData({
        list,
        likedList,
        contentImages
      })
      if (data.length < this.data.ps) {
        this.setData({
          finish: true
        })
      }
      wx.hideNavigationBarLoading(); //完成停止加载图标
      wx.stopPullDownRefresh();
    })
  },

  more(e) {
    const cid = e.currentTarget.dataset.cid
    this.setData({
      show: !this.data.show,
      moreId: cid
    })

  },
  onCancel() {
    this.setData({
      show: false
    });
  },

  onSelect(event) {
    console.log(event.detail);

    if ('删除' == event.detail.name) {
      let data = {
        cid: this.data.moreId
      }
      api.post(config.api.destroy, data).then(res => {
        console.log(res)
        if (0 == res.status) {
          this.setData({
            list: [],
            contentImages: [],
            likedList: [],
            show:false,
          })
          this.getList()
        }
      })
    }
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
    let data = {
      cid
    }
    let likedList = this.data.likedList;
    likedList[cid].liked = true;
    likedList[cid].like_count += 1;
    api.post(config.api.contentLike, data).then(res => {
      console.log(res)
      if (0 == res.status) {
        this.setData({
          likedList
        })
        wx.vibrateShort()
      }

    })
  },
  unlike(e) {
    const cid = e.currentTarget.dataset.cid
    let data = {
      cid
    }
    let likedList = this.data.likedList;
    likedList[cid].liked = false;
    likedList[cid].like_count -= 1;
    api.post(config.api.contentUnLike, data).then(res => {
      console.log(res)
      if (0 == res.status) {
        this.setData({
          likedList
        })
      }
    })
  },
  comment(e) {
    console.log(e)
    const cid = e.currentTarget.dataset.cid
    wx.navigateTo({
      url: '../detail/detail?comment=1&cid=' + cid,
    })
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
  toGroup(e) {
    console.log(e)
    wx.navigateTo({
      url: '../groupContent/groupContent?gid=' + e.currentTarget.dataset.gid + '&name=' + e.currentTarget.dataset.name,
    })
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