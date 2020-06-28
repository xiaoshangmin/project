// pages/publish/publish.js
Page({

  /**
   * 页面的初始数据
   */
  data: {
    files: [],
    fileList: [],
    btn: 0
  },
  afterRead(event) {
    const {
      file,
      name
    } = event.detail;
    const fileList = this.data[`fileList${name}`];
   
    this.setData({
      [`fileList${name}`]: fileList.concat(file)
    });
  },
  beforeRead(event) {
    const { file, callback = () => {} } = event.detail;
    // console.log(file)
    // if (file.path.indexOf('jpg') < 0) {
    //   wx.showToast({ title: '请选择jpg图片上传', icon: 'none' });
    //   callback(false);
    //   return;
    // }
    callback(true);
  },
  oversize() {
    wx.showToast({ title: '文件超出大小限制', icon: 'none' });
  },
  delete(event) {
    const { index, name } = event.detail;
    const fileList = this.data[`fileList${name}`];
    fileList.splice(index, 1);
    this.setData({ [`fileList${name}`]: fileList });
  },
  f(e) {
    console.log(e.detail.height)
    this.setData({
      btn: e.detail.height
    })
  },
  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {},

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