window._ = require('lodash');

/**
 * Axios gửi request AJAX kèm header X-Requested-With.
 * (Giao diện BenBooks dùng JS tĩnh trong public/assets/js – file này giữ theo chuẩn Laravel.)
 */
window.axios = require('axios');
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
