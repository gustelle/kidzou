

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

		//quality reports pour les JS
		// plato: {
		// 	options : {
		//       jshint : false //deja fait par ailleurs
		//     },
		//     front: {
		//       files: {
		//         'reports': ['<%= cfg.kidzou_path %>/assets/js/*.js', '<%= cfg.kidzou_path %>/public/assets/js/*.js','<%= cfg.kidzou_path %>/admin/assets/js/*.js', '<%= cfg.theme_path %>/js/custom.js'],
		//       }
		//     },
		//   },


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

	    perfbudget: {
		  default: {
		    options: {
		      url: 'http://www.kidzou.fr',
		      key: '<%= cfg.perf_api_key %>',
		      location : 'ec2-eu-central-1:Chrome'
		    }
		  }
		}
		

	});

	grunt.loadNpmTasks('grunt-contrib-jshint'); //
	grunt.loadNpmTasks('grunt-contrib-copy'); //
	// grunt.loadNpmTasks('grunt-plato');

	//nettoyage des fichiers
	grunt.loadNpmTasks('grunt-contrib-clean');

	//Documentation
	grunt.loadNpmTasks('grunt-phpdocumentor');
	// grunt.loadNpmTasks('grunt-css-docs');

	//Perf tests avec les API de WebPageTest.org
	//API key is A.3a80d0143024fccd375b277d90f0a07c
	//The following browser/location combinations are available:
	// Dulles_IE9
	// Dulles_IE10
	// Dulles_IE11
	// Dulles:Chrome
	// Dulles:Canary
	// Dulles:Firefox
	// Dulles:Firefox Nightly
	// Dulles:Safari
	// Dulles_MotoG:Motorola G - Chrome
	// Dulles_MotoG:Motorola G - Chrome Beta
	// Dulles_MotoG:Motorola G - Chrome Dev
	// ec2-us-east-1:Chrome
	// ec2-us-east-1:IE 11
	// ec2-us-east-1:Firefox
	// ec2-us-east-1:Safari
	// ec2-us-west-1:Chrome
	// ec2-us-west-1:IE 11
	// ec2-us-west-1:Firefox
	// ec2-us-west-1:Safari
	// ec2-us-west-2:Chrome
	// ec2-us-west-2:IE 11
	// ec2-us-west-2:Firefox
	// ec2-us-west-2:Safari
	// ec2-eu-west-1:Chrome
	// ec2-eu-west-1:IE 11
	// ec2-eu-west-1:Firefox
	// ec2-eu-west-1:Safari
	// ec2-eu-central-1:Chrome
	// ec2-eu-central-1:IE 11
	// ec2-eu-central-1:Firefox
	// ec2-eu-central-1:Safari
	// ec2-ap-northeast-1:Chrome
	// ec2-ap-northeast-1:IE 11
	// ec2-ap-northeast-1:Firefox
	// ec2-ap-northeast-1:Safari
	// ec2-ap-southeast-1:Chrome
	// ec2-ap-southeast-1:IE 11
	// ec2-ap-southeast-1:Firefox
	// ec2-ap-southeast-1:Safari
	// ec2-ap-southeast-2:Chrome
	// ec2-ap-southeast-2:IE 11
	// ec2-ap-southeast-2:Firefox
	// ec2-ap-southeast-2:Safari
	// ec2-sa-east-1:Chrome
	// ec2-sa-east-1:IE 11
	// ec2-sa-east-1:Firefox
	// ec2-sa-east-1:Safari
	grunt.loadNpmTasks('grunt-perfbudget');

	//lancement de grunt par defaut
	grunt.registerTask('default', ['prepjs','theme', 'plugins']);

	//mise en recette
	grunt.registerTask('recette', ['prepjs','theme', 'plugins', 'http:recette']);

	grunt.registerTask('prepjs', ['jshint']); //'plato'
	grunt.registerTask('theme',  ['copy:divi']); //csslint
	grunt.registerTask('plugins',['copy:k4']); 
	grunt.registerTask('doc',['phpdocumentor:plugin_kidzou']);

	//execution de tests de perf sur WebPageTest.org
	grunt.registerTask('perf',['perfbudget']);

};