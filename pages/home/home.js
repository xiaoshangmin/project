// pages/home/home.js
const api = require("../../utils/api");
const config = require('../../config');
const util = require('../../utils/util');
const app = getApp()

Page({

  /**
   * 页面的初始数据
   */
  data: {
    contentImages: [],
    likedList: [],
    list: [],
    imageSize: [],
    showAuth: false,
    seeMore:false,
    p: 1,
    ps: 10,
    finish: false,
  },
  toggleHandler(){
    this.setData({
      seeMore:true
    })
  },
  toggleContent(){
    this.setData({
      seeMore:false
    })
  },
  onChange(event) {
    this.setData({
      active: event.detail
    });
  },

  previewImage(e) {
    const current = e.target.dataset.src //获取当前点击的 图片 url
    const cid = e.target.dataset.cid
    // console.log(this.data.contentImages[cid], cid)
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
    if (this.data.p == 1) {
      list = [];
      contentImages = [];
      likedList = [];
      this.setData({
        finish: false
      })
    }
    api.post(config.api.recommend, data, true).then(res => {
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
        showAuth: true
      })
    }
  },
  like(e) {
    const cid = e.currentTarget.dataset.cid
    if (app.globalData.isAuthUserInfo) {
      let data = {
        cid
      }
      let likedList = this.data.likedList;
      likedList[cid].liked = true;
      likedList[cid].like_count = 1 + Number(likedList[cid].like_count);
      api.post(config.api.contentLike, data).then(res => {
        console.log(res)
        if (0 == res.status) {
          this.setData({
            likedList
          })
          wx.vibrateShort()
        }

      })
    } else {
      this.setData({
        showAuth: true
      })
    }
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
    const cid = e.currentTarget.dataset.cid
    wx.navigateTo({
      url: '../detail/detail?comment=1&cid=' + cid,
    })
  },

  toPublish() {
    if (app.globalData.isAuthUserInfo) {
      wx.navigateTo({
        url: '../publish/publish',
      })
    } else {
      this.setData({
        showAuth: true
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

  imgLoad(e) {
    console.log(e)
    e.currentTarget.dataset.src = 'https://img.yzcdn.cn/vant/cat.jpeg';
  },

  poi() {
    wx.showToast({
      title: 'poi'
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
    // app.userInfoReadyCallback = res => {
    //   // console.log(res.userInfo)
    // }
    this.setData({
      p: 1,
      ps: 10,
    })
    this.getList();
    util.sleep(2000)
    app.showAd()
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
    this.setData({
      p: 1,
      ps: 10,
    })
    this.getList()
  },

  /**
   * 页面上拉触底事件的处理函数
   */
  onReachBottom: function () {
    this.setData({
      p: this.data.p + 1,
    })
    this.getList()
  },

  /**
   * 用户点击右上角分享
   */
  onShareAppMessage: function (res) {
    console.log(res)
    if (res.from === 'button') {
      return {
        path: '/pages/detail/detail?cid=' + res.target.dataset.cid
      }
    }
  }
})