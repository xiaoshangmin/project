let host = 'http://xsm.p.com';
let qiniu_host = 'http://qiniu.wowyou.cc';
let config = {
  api: {
    host,
    qiniu_host,
    qiniu_uptoken: `${host}/misc/upload/uptoken`
  }
};
module.exports = config
 