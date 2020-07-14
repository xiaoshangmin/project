// pages/groups/groups.js
const api = require('../../utils/api');
const config = require('../../config');
Page({

  /**
   * 页面的初始数据
   */
  data: {
    searchGroupKeyWords: '',
    selectGroupInfo: {
      "id": 0,
      "title": '未选择圈子',
      "tips": "合适的[圈子]会有更多的赞",
    },
  },

  onChange(e) {
    this.setData({
      searchGroupKeyWords: e.detail,
    });
  },
  onSearch() {
    console.log(this.data.searchGroupKeyWords)
    api.post(config.api.submit).then(res => {

    })
  },

  onSelectGroup(e) {
    let selectGroupInfo = this.data.selectGroupInfo
    selectGroupInfo.title = e.currentTarget.dataset.title;
    selectGroupInfo.id = e.currentTarget.dataset.id;
    selectGroupInfo.tips = "更换圈子";
    var pages = getCurrentPages();
    var currPage = pages[pages.length - 1]; // 当前页
    var prevPage = pages[pages.length - 2]; // 上一个页面
    var data = prevPage.data // 获取上一页data里的数据
    console.log(data)
    prevPage.setData({
      selectGroupInfo
    })
    wx.navigateBack()
    // this.setData({
    //   selectGroupInfo
    // })
  },
  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {

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