module.exports = function(grunt) {

	grunt.loadNpmTasks('grunt-contrib-uglify');
	grunt.loadNpmTasks('grunt-contrib-cssmin');
	grunt.loadNpmTasks('grunt-contrib-jshint');

	// Project configuration.
	grunt.initConfig({
		pkg: grunt.file.readJSON('package.json'),
		cssmin: {
			minify: {
				expand: true,
				cwd: 'css/',
				src: ['*.css', '!*.min.css'],
				dest: 'css/',
				ext: '.min.css'
			}
		},
		jshint: {
			grunt: {
				src: ['Gruntfile.js']
			},
			pluginjs: {
				expand: true,
				cwd: 'js/',
				src: [
					'*.js',
					'!*.min.js'
				]
			}
		},
		uglify: {
			theme: {
				expand: true,
				files: {
					'js/general.min.js': [ 'js/general.js' ],
					'js/page_edit_jobtype_dbdump.min.js': [ 'js/page_edit_jobtype_dbdump.js' ],
					'js/page_edit_jobtype_file.min.js': [ 'js/page_edit_jobtype_file.js' ],
					'js/page_edit_tab_cron.min.js': [ 'js/page_edit_tab_cron.js' ],
					'js/page_edit_tab_job.min.js': [ 'js/page_edit_tab_job.js' ],
					'js/page_settings.min.js': [ 'js/page_settings.js' ],
				}
			}
		}
	});

	// Register tasks
	grunt.registerTask('production', ['jshint', 'cssmin', 'uglify']);

	// Default task
	grunt.registerTask('default', ['production']);
};
