/**
 * This file is part of Galette Auto plugin (https://galette.eu).
 * SPDX-FileCopyrightText: Copyright © 2018-2026 The Galette Team
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

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
  externals: {
    // shows how we can rely on browser globals instead of bundling these dependencies,
    // in case we want to access jQuery from a CDN or if we want an easy way to
    // avoid loading all moment locales: https://github.com/moment/moment/issues/1435
    jquery: 'jQuery',
    moment: 'moment'
  },
  devtool: 'source-map',
  resolve: {
    extensions: [ '.js' ],
  },
   performance: {
    hints: false,
    maxEntrypointSize: 512000,
    maxAssetSize: 512000
  }
}
