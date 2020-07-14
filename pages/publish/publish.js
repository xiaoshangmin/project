// pages/publish/publish.js
const qiniu = require("../../utils/qiniuUploader");
const config = require('../../config');
const api = require('../../utils/api');
import Toast from '../../miniprogram_npm/@vant/weapp/toast/toast';
Page({

  /**
   * 页面的初始数据
   */
  data: {
    fileList: [],
    qiniuFileList: [],
    bottom: 0,
    show: false,
    autosize: {
      maxHeight: 180,
      minHeight: 100
    },
    selectGroupInfo: {
      "id": 0,
      "title": '未选择圈子',
      "tips": "合适的[圈子]会有更多的赞",
    },
    sizeType: ['compressed'],
    content: '',
    // 图片上传（从相册）返回对象。上传完成后，此属性被赋值
    imageObject: {},
    // 图片上传（从相册）进度对象。开始上传后，此属性被赋值
    imageProgress: {},
    // 此属性在qiniuUploader.upload()中被赋值，用于中断上传
    cancelTask: function () {}
  },

  afterRead(event) {
    const {
      file,
      name
    } = event.detail;
    this.didPressChooesImage(file)
  },

  oversize() {
    wx.showToast({
      title: '文件超出大小限制',
      icon: 'none'
    });
  },
  delete(event) {
    const {
      index,
      name
    } = event.detail;

    const fileList = this.data.fileList;
    const qiniuFileList = this.data.qiniuFileList;
    fileList.splice(index, 1);
    qiniuFileList.splice(index, 1);
    this.setData({
      fileList,
      qiniuFileList
    });

  },

  chooseImage() {
    wx.chooseImage({
      count: 9,
      sizeType: ['compressed'],
      success: (result) => {
        // console.log(result)
        this.didPressChooesImage(result.tempFiles)
      },
      fail: (res) => {},
      complete: (res) => {},
    })
  },
  post() {
    let data = {
      pictures: this.data.qiniuFileList,
      content: this.data.content,
      groupInfo: this.data.selectGroupInfo
    }
    console.log(data)
    api.post(config.api.create, data).then(res => {
      console.log(res.data.group,99)
    });
  },
  focus(e) {
    console.log(e)
    this.setData({
      bottom: e.detail.height
    })
  },
  blur(e) {
    this.setData({
      bottom: 0
    })
  },
  group() {
    // this.setData({
    //   show: !this.data.show,
    //   setPage: 'setPage'
    // })
    wx.navigateTo({
      url: '../groups/groups',
    })
  },

  onCancel() {
    this.setData({
      show: !this.data.show
    })
  },

  contentOnChange(e) {
    this.setData({
      content: e.detail
    })
  },
  /**
   * 
   * @param {*} tempFiles 新增的图片数据
   */
  didPressChooesImage(tempFiles) {

    const fileList = this.data.fileList;

    Toast.loading({
      duration: 0, // 持续展示 toast
      forbidClick: true, // 禁用背景点击
      message: '上传中',
      loadingType: 'spinner',
      selector: '#custom-toast',
    });

    this.setData({
      fileList: fileList.concat(tempFiles)
    });

    let len = fileList.length;

    tempFiles.forEach((res) => {
      this.doDidPressChooesImage(len, res.path)
      len++;
    })
    Toast.clear();
  },
  /**
   * 上传图片
   * @param {*} index 数组下标
   * @param {*} filePath 图片本地地址
   */
  doDidPressChooesImage(index, filePath) {
    let qiniuFileList = this.data.qiniuFileList
    qiniu.upload(filePath, (res) => {
        delete res.avinfo
        delete res.exif
        delete res.hash
        qiniuFileList[index] = res
        this.setData({
          qiniuFileList
        });
        console.log(this.data.qiniuFileList);
      }, (error) => {
        console.error('error: ' + JSON.stringify(error));
      },
      null, cancelTask => this.setData({
        cancelTask
      })
    );
  },
  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    // 初始化七牛云配置
    initQiniu();
  },

  // 中断上传方法
  didCancelTask: function () {
    this.data.cancelTask();
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
    // this.getTabBar().init();
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


// 初始化七牛云相关配置
function initQiniu() {
  var options = {
    // bucket所在区域，这里是华北区。ECN, SCN, NCN, NA, ASG，分别对应七牛云的：华东，华南，华北，北美，新加坡 5 个区域
    region: 'SCN',
    uptoken: '',
    // 从指定 url 通过 HTTP GET 获取 uptoken，返回的格式必须是 json 且包含 uptoken 字段，例如： {"uptoken": "0MLvWPnyy..."}
    uptokenURL: config.api.qiniu_uptoken,
    // uptokenFunc 这个属性的值可以是一个用来生成uptoken的函数，详情请见 README.md
    uptokenFunc: function () {},

    domain: 'http://qiniu.wowyou.cc',
    shouldUseQiniuFileName: true
  };
  // 将七牛云相关配置初始化进本sdk
  qiniu.init(options);
}