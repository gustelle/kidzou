

module.exports = function(grunt) {
  // Do grunt-related things in here

	grunt.initConfig({
		pkg: grunt.file.readJSON('package.json'),
		cfg: grunt.file.readJSON('config.json'),

		//quality check pour les JS
		jshint: {
			options: {
		      "-W099": true, //mixed spaces and tabs (smarttabs)
		      "-W065": true,	//radix param sur la fonction parseInt(),
		      "-W044": true,  //escapes dans les regex
		      "-W004": true //{a} is already defined
		    },
			all: ['<%= cfg.kidzou_path %>/public/asets/js/public.js',
				  '<%= cfg.kidzou_path %>/asets/js/kidzou-geo.js.js',
				  '<%= cfg.kidzou_path %>/asets/js/kidzou-storage.js',
				  '<%= cfg.kidzou_path %>/asets/js/kidzou-client.js',
				  '<%= cfg.kidzou_path %>/asets/js/kidzou-events.js',
				  '<%= cfg.kidzou_path %>/asets/js/kidzou-place.js',
				  '<%= cfg.theme_path %>/js/custom.js',
				  ] 
		},

		//déploiement des fichiers sur le serveur Apache
		copy: {

			//tache de déploiement du thème enfant
			//et surcharge de certains fichiers du thème parent
			divi: {
				files: [
				 	{expand:true, cwd: '<%= cfg.theme_path %>', src: ['**','!css/dev/**'], dest: '<%= cfg.wp_theme_root %>'},
				 	{expand:true, cwd: '<%= cfg.theme_parent_path %>', src: ['**','!css/dev/**'], dest: '<%= cfg.wp_theme_parent_root %>'},

				]
			},

			//déploiement des fichiers du plugin Kidzou-4
			k4: {
				files: [
					{expand:true, cwd: '<%= cfg.kidzou_path %>', src: ['**'], dest: '<%= cfg.wp_kidzou_root %>'}, // includes files in path and its subdirs,
				]
			},
		},

		//generation de documentation PHP
		phpdocumentor: {

	        // Grunt Target used to generate a first documentation
	        plugin_kidzou : {
	            options: {
	                directory : 'plugins/kidzou-4',
	                target : 'docs/'
	            }
	        },
	    },

	    //tests de performance avec les API WebPageTest.org
	    perfbudget: {
		  default: {
		    options: {
		      url: 'http://www.kidzou.fr',
		      key: '<%= cfg.perf_api_key %>',
		      location : 'ec2-eu-central-1:Chrome'
		    }
		  }
		},

		//compilation des JSX React
		// babel: {
		// 	options: {
		// 		plugins: ['transform-react-jsx'],
		// 		presets: ['es2015', 'react']	
		// 	},
		// 	jsx: {
		// 		files: [{
		// 		  expand: true,
		// 		  cwd: '<%= cfg.kidzou_path %>/admin/assets/js/jsx/', // Custom folder
		// 		  src: ['*.jsx'],
		// 		  dest: '<%= cfg.kidzou_path %>/admin/assets/js', // Custom folder
		// 		  ext: '.js'
		// 		}]
		// 	}
		// },


		babel: {
			options: {
				plugins: ['transform-react-jsx'],
				presets: ['es2015', 'react']
			},
			jsx: {
				files: [{
				  expand: true,
				  cwd: '<%= cfg.theme_path %>/js/jsx/', // Custom folder
				  src: ['*.jsx'],
				  dest: '<%= cfg.theme_path %>/js', // Custom folder
				  ext: '.js'
				}]
			}
		}
		
	});

	///////////////////////////////////////
	// Modules
	//

	grunt.loadNpmTasks('grunt-contrib-jshint'); //
	grunt.loadNpmTasks('grunt-contrib-copy'); //

	//nettoyage des fichiers
	grunt.loadNpmTasks('grunt-contrib-clean');

	//Documentation
	grunt.loadNpmTasks('grunt-phpdocumentor');
	grunt.loadNpmTasks('grunt-babel');
	// grunt.loadNpmTasks('grunt-css-docs');

	//Perf tests avec les API de WebPageTest.org
	//API key is A.3a80d0143024fccd375b277d90f0a07c
	grunt.loadNpmTasks('grunt-perfbudget');

	///////////////////////////////////////
	// Tasks
	//

	grunt.registerTask('doc',['phpdocumentor:plugin_kidzou']);
	grunt.registerTask('perf',['perfbudget']);

	//lancement de grunt par defaut
	grunt.registerTask('default', ['jshint','copy:divi', 'copy:k4']);

	//pour compiler les jsx
	grunt.registerTask('deploy', ['babel','copy:divi', 'copy:k4']);


};