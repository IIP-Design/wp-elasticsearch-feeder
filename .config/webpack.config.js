const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const path = require( 'path' );

module.exports = {
  ...defaultConfig,
  entry: {
    gutenberg: './admin/js/gutenberg-plugin.js',
    settings: './admin/js/settings.js',
  },
  output: {
    filename: 'gpalab-feeder-[name].js',
    path: path.resolve( process.cwd(), 'admin/build' ),
  },
};
