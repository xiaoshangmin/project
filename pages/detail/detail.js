// pages/detail/detail.js
const api = require("../../utils/api")
const config = require('../../config');
const qiniu = require("../../utils/qiniuUploader");
const app = getApp()
Page({

  /**
   * 页面的初始数据
   */
  data: {
    p: 1,
    show: false,
    actions: [{
        name: '回复',
      },
      {
        name: '复制',
      }
    ],
    replayUserData: {},
    showAuth: false,
    autoFocus: false,
    bottom: 0, //
    cid: 0, //内容ID
    pid: 0, //评论的id
    fileList: [], //本地图片
    qiniuFileList: [], //七牛图片
    disabled: true, //发送按钮是否可点击
    message: '',
    replay: '', //评论回复的内容
    placeholder: '',
    replay_btn_color: '#cdcdcd', //发送按钮颜色
    autosize: {
      maxHeight: 50,
      minHeight: 10
    },
    contentImages: [], //正文的图片 为了预览
    content: {},
    comments: [],
    hotComments: []
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

  previewCommentImage(e) {
    const current = e.target.dataset.src //获取当前点击的 图片 url
    wx.previewImage({
      current,
      urls: [current]
    })
  },

  detail(cid) {
    api.get(config.api.detail, {
      cid
    }).then(res => {
      console.log(res)
      let content = res.data
      let contentImages = []
      contentImages[content.id] = []
      content.pictures.forEach(pic => {
        contentImages[content.id].push(pic.middlePicUrl)
      })
      this.setData({
        content,
        contentImages,
        placeholder: '回复' + content.user.nickname,
        cid
      })
    })
  },
  listPrimary(cid) {
    api.post(config.api.commentListPrimary, {
      cid,
      p: this.data.p,
      ps: 20
    }).then(res => {
      console.log(res)
      let content = res.data
      // let contentImages = []
      // contentImages[content.id] = []
      // content.pictures.forEach(pic => {
      //   contentImages[content.id].push(pic.middlePicUrl)
      // })
      this.setData({
        comments: content.list,
        hotComments: [],
      })
    })
  },
  focus(e) {
    let heigth = e.detail.heigth
    this.setData({
      bottom: heigth
    })
  },
  blur(e) {
    this.setData({
      bottom: 0
    })
  },
  replayOnChange(e) {
    this.setData({
      replay: e.detail
    })
    this.changeBtnStatus()
  },
  changeBtnStatus() {
    let disabled = true;
    let replay_btn_color = '#cdcdcd';
    if (this.data.replay || this.data.fileList.length >= 1) {
      disabled = false;
      replay_btn_color = '#3387fb';
    }
    this.setData({
      disabled,
      replay_btn_color
    })
  },
  afterRead(event) {
    const {
      file,
      name
    } = event.detail;

  },
  chooseImage() {
    wx.chooseImage({
      count: 1,
      sizeType: ['compressed'],
      success: (result) => {
        this.didPressChooesImage(result.tempFiles)
      },
      fail: (res) => {},
      complete: (res) => {},
    })
  },
  /**
   * 上传图片
   * @param {*} tempFiles 新增的图片数据
   */
  didPressChooesImage(tempFiles) {
    const fileList = this.data.fileList;
    this.setData({
      fileList: fileList.concat(tempFiles)
    });

    let len = fileList.length;
    tempFiles.forEach((res) => {
      console.log('uploading img', res.path)
      this.doDidPressChooesImage(len, res.path)
      len++;
    })
    this.changeBtnStatus()
  },
  /**
   * 上传图片
   * @param {*} index 数组下标
   * @param {*} filePath 图片本地地址
   */
  doDidPressChooesImage(index, filePath) {
    let qiniuFileList = this.data.qiniuFileList;
    qiniu.upload(filePath, (res) => {
        delete res.avinfo
        delete res.exif
        delete res.hash
        qiniuFileList[index] = res
        this.setData({
          qiniuFileList
        });
        console.log('doDidPressChooesImage', this.data.qiniuFileList);
      }, (error) => {
        console.error('error: ' + JSON.stringify(error));
      },
      null, cancelTask => this.setData({
        cancelTask
      })
    );
  },
  addComments() {
    let data = {
      replay: this.data.replay,
      pictures: this.data.qiniuFileList,
      cid: this.data.cid,
      pid: 0
    }
    wx.showLoading({
      title: '发布中',
      icon: 'none'
    })
    api.post(config.api.addComment, data).then(res => {
      console.log(res)
      if (0 == res.status) {
        let content = this.data.content
        content['comment_count'] = 1 + Number(content['comment_count']);
        this.setData({
          content,
          replay: '',
          message: '',
          qiniuFileList: [],
          fileList: [],
          p: 1,
        })
        this.changeBtnStatus();
        this.listPrimary(content['id'])
      }
      wx.hideLoading();
    })
  },
  replayComment(e) {
    console.log(e)
    this.setData({
      show: true,
      replayUserData: e.currentTarget.dataset
    })
  },
  blur() {
    this.setData({
      autoFocus: false
    })
  },
  onSelect(e) {
    console.log(e)
    if ('回复' == e.detail.name) {
      this.setData({
        autoFocus: true,
        placeholder:'回复' + this.data.replayUserData.nickname
      })
    } else if ('复制' == e.detail.name) {
      wx.setClipboardData({
        data: this.data.replayUserData.content,
        success(res) {
          wx.getClipboardData({
            success(res) {
              console.log(res.data) // data
            }
          })
        }
      })
    }
  },
  onClose() {
    this.setData({
      show: false
    })
  },
  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    console.log(options)
    this.detail(options.cid)
    this.listPrimary(options.cid)
    if (options.comment) {
      this.setData({
        autoFocus: true
      })
      wx.pageScrollTo({
        scrollTop: 450,
        selector: '#newcomment',
        duration: 300
      })
    }
    initQiniu()
  },
  delete(event) {
    const {
      index,
      name
    } = event.detail;

    const fileList = this.data.fileList;
    // const qiniuFileList = this.data.qiniuFileList;
    fileList.splice(index, 1);
    // qiniuFileList.splice(index, 1);
    this.setData({
      fileList,
      // qiniuFileList
    });
    this.changeBtnStatus()
  },
  like(e) {
    const cid = e.currentTarget.dataset.cid
    if (app.globalData.isAuthUserInfo) {
      let data = {
        cid
      }
      let content = this.data.content;
      content.liked = true;
      content.like_count += 1;
      api.post(config.api.contentLike, data).then(res => {
        console.log(res)
        if (0 == res.status) {
          this.setData({
            content
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
    let content = this.data.content;
    content.liked = false;
    content.like_count -= 1;
    api.post(config.api.contentUnLike, data).then(res => {
      console.log(res)
      if (0 == res.status) {
        this.setData({
          content
        })
      }
    })
  },
  getUserInfo(event) {
    console.log(event.detail)
    app.getUserInfo()
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
    this.setData({
      p: this.data.p + 1,
    })
    this.listPrimary(this.data.content['id'])
  },

  /**
   * 用户点击右上角分享
   */
  onShareAppMessage: function () {

  },
  onShareTimeline: function (res) {
    console.log(res)
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

    domain: 'http://cdn.wowyou.cc',
    shouldUseQiniuFileName: true
  };
  // 将七牛云相关配置初始化进本sdk
  qiniu.init(options);
}