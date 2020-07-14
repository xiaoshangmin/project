var Session = {
    get: function ($key) {
        return wx.getStorageSync($key) || null;
    },

    set: function ($key, $val) {
        wx.setStorageSync($key, $val);
    },
    clear: function ($key) {
        wx.removeStorageSync($key);
    },
};
module.exports = Session;