const defaultConfig = require("@wordpress/scripts/config/webpack.config");

module.exports = {
  ...defaultConfig,
  entry: {
    index: "./assets/js/src/index.js",
  },
  output: {
    filename: "[name].js",
    path: __dirname + "/assets/js/build",
  },
};
