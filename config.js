let host = 'http://xsm.p.com';
let qiniu_host = 'http://qiniu.wowyou.cc';
let config = {
  api: {
    host,
    qiniu_host,
    qiniu_uptoken: `${host}/misc/upload/uptoken`,
    create: `${host}/content/feed/submit`,
    recommend: `${host}/content/feed/recommend`,
    grouplist: `${host}/group/group/list`,
    wxlogin: `${host}/user/user/wxlogin`,
    decrypt: `${host}/user/user/decrypt`,
  }
};
module.exports = config