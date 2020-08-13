// pages/groupContent/groupContent.js
const api = require("../../utils/api");
const config = require('../../config');
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
    p: 1,
    ps: 10,
    finish: false,
    groupinfo: {},
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    this.setData({
      p: 1,
      ps: 10,
    })
    wx.setNavigationBarTitle({
      title: options.name
    })
    this.getGroupInfo(options.gid)
    this.getList(options.gid);
    app.showAd()

  },

  getGroupInfo(gid) {
    let data = {
      gid
    }
    api.get(config.api.groupinfo, data).then(res => {
      console.log(res)
      if (0 != res.status) {
        wx.showToast({
          title: res.msg,
        })
        return;
      }
      this.setData({
        groupinfo: res.data
      })

    })
  },


  getList(gid) {
    wx.showNavigationBarLoading(); //在标题栏中显示加载图标

    let data = {
      p: this.data.p,
      ps: this.data.ps,
      gid
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
    api.post(config.api.groupPostsList, data, true).then(res => {
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