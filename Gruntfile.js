/*global node:true */

module.exports = function( grunt ) {
	'use strict';

	require('matchdep').filterDev('grunt-*').forEach( grunt.loadNpmTasks );

	grunt.initConfig({

		makepot: {
			plugin: {
				options: {
					mainFile: 'simple-page-sidebars.php',
					potHeaders: {
						poedit: true
					},
					type: 'wp-plugin',
					updateTimestamp: false
				}
			}
		},

	});

};
