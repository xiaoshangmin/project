/**
 * 简单封装
 */
const request = (url, data, method, load) => {

  if (load) {
    // wx.showNavigationBarLoading();
    // wx.showLoading({
    //   title: 'Loading...',
    //   icon: 'none'
    // })
  }
  return new Promise((resolve, reject) => {
    wx.request({
      url: url,
      data: data,
      method: method,
      success(res) {
        if (res.statusCode === 200) {
          resolve(res.data)
        } else {
          reject(res)
        }
      },
      fail(err) {
        reject(err)
        // wx.showModal({
        //   title: '温馨提示',
        //   content: '网络请求失败',
        //   showCancel: false
        // })
      },
      complete(res) {
        if (load) {
          // wx.hideNavigationBarLoading()
          // wx.hideLoading();
        }
      }
    })
  })
}
/**
 * 
 * @param {*} url 
 * @param {*} data 
 * @param {*} load 是否显示loading
 */
const get = (url, data, load = true) => {
  return request(url, data, 'GET', load)
}
const post = (url, data, load = true) => {
  return request(url, data, 'POST', load)
}

module.exports = {
  get,
  post,
  request
}