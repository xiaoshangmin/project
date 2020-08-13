let alpha_host = "http://xsm.p.com";
let host = 'https://api.wowyou.cc';
let qiniu_host = 'http://cdn.wowyou.cc';
const accountInfo = wx.getAccountInfoSync();
if (accountInfo.miniProgram.envVersion == "develop") {
  // host = alpha_host;
}
// console.log(accountInfo)
let config = {
  api: {
    host,
    qiniu_host,
    qiniu_uptoken: `${host}/misc/upload/uptoken`,
    create: `${host}/content/feed/submit`,
    recommend: `${host}/content/feed/recommend`,
    detail: `${host}/content/feed/detail`,
    mypost: `${host}/user/user/mypost`,
    grouplist: `${host}/group/group/list`,
    groupinfo:`${host}/group/group/detail`,
    groupPostsList:`${host}/group/group/posts`,
    wxlogin: `${host}/user/user/wxlogin`,
    decrypt: `${host}/user/user/decrypt`,
    addComment: `${host}/content/comment/submit`,
    commentListPrimary: `${host}/content/comment/listPrimary`,
    contentLike: `${host}/content/feed/like`,
    contentUnLike: `${host}/content/feed/unlike`,
    destroy:`${host}/content/feed/destroy`,
  }
};
module.exports = config