//app.js
const api = require("utils/api");
const config = require('config');
App({

  globalData: {
    userInfo: null,
    isAuthUserInfo: false
  },

  onLaunch: function () {
    // 展示本地存储能力
    // var logs = wx.getStorageSync('logs') || []
    // logs.unshift(Date.now())
    // wx.setStorageSync('logs', logs)

    // 获取用户信息
    wx.getSetting({
      success: res => {
        if (res.authSetting['scope.userInfo']) {
          // 已经授权，可以直接调用 getUserInfo 获取头像昵称，不会弹框
          this.globalData.isAuthUserInfo = true;
          wx.getUserInfo({
            success: res => {
              // 可以将 res 发送给后台解码出 unionId
              this.globalData.userInfo = res.userInfo

              // 由于 getUserInfo 是网络请求，可能会在 Page.onLoad 之后才返回
              // 所以此处加入 callback 以防止这种情况
              if (this.userInfoReadyCallback) {
                this.userInfoReadyCallback(res)
              }
            }
          })
        }
      }
    })
  },

  doWxLogin() {
    return new Promise((resolve, reject) => {
      // 登录
      wx.login({
        success: res => {
          // 发送 res.code 到后台换取 openId, sessionKey, unionId
          api.post(config.api.wxlogin, {
            code: res.code
          }).then(res => {
            // console.log(res)
            resolve(res)
          })
        }
      })
    })
  },
  getUserInfo() {
    wx.checkSession({
      success: res => {
        wx.getUserInfo({
          success: res => {
            console.log(res)
            this.globalData.isAuthUserInfo = true
            this.globalData.userInfo = res.userInfo
            let data = {
              "encryptedData": res.encryptedData,
              "iv": res.iv
            }
            api.post(config.api.decrypt, data).then(res => {

            })
          }
        })
      },
      fail: res => {
        this.doWxLogin().then(res => {
          this.getUserInfo()
        })
      }
    })
  }
})