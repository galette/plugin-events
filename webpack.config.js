const path = require('path')
const webpack = require('webpack');
const CopyWebpackPlugin = require('copy-webpack-plugin');

module.exports = {
  entry: './calendar.js',
  mode: 'none',
  output: {
    filename: "calendar.bundle.js",
    path: path.join(__dirname, 'webroot', 'js')
  },
  plugins: [
    new CopyWebpackPlugin([
      {
        from: path.resolve(__dirname, 'node_modules/@fullcalendar/core/main.min.css'),
        to: path.resolve(__dirname, 'webroot/js/libs/fullcalendar.min.css')
      }, {
        from: path.resolve(__dirname, 'node_modules/@fullcalendar/daygrid/main.min.css'),
        to: path.resolve(__dirname, 'webroot/js/libs/fullcalendar-daygrid.min.css')
      }
    ])
  ],
  externals: {
    // shows how we can rely on browser globals instead of bundling these dependencies,
    // in case we want to access jQuery from a CDN or if we want an easy way to
    // avoid loading all moment locales: https://github.com/moment/moment/issues/1435
    jquery: 'jQuery',
    moment: 'moment'
  },
  devtool: 'sourcemap',
  resolve: {
    extensions: [ '.js' ],
  }
}
