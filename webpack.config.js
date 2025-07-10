const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

module.exports = {
	...defaultConfig,
	entry: {
		admin: './src/admin.js',
	},
	output: {
		filename: '[name].js',
		path: __dirname + '/build',
	},
};
