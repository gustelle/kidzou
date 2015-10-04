

module.exports = function(grunt) {
  // Do grunt-related things in here

	grunt.initConfig({
		pkg: grunt.file.readJSON('package.json'),
		cfg: grunt.file.readJSON('config.json'),

		//compilation des CSS pour le theme
	

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
		plato: {
			options : {
		      jshint : false //deja fait par ailleurs
		    },
		    front: {
		      files: {
		        'reports': ['<%= cfg.kidzou_path %>/assets/js/*.js', '<%= cfg.kidzou_path %>/public/assets/js/*.js','<%= cfg.kidzou_path %>/admin/assets/js/*.js', '<%= cfg.theme_path %>/js/custom.js'],
		      }
		    },
		  },

		
		//tache de déploiement en local
		copy: {

		  divi: {
		    files: [
		     	{expand:true, cwd: '<%= cfg.theme_path %>', src: ['**','!css/dev/**'], dest: '<%= cfg.wp_theme_root %>'},
		     	{expand:true, cwd: '<%= cfg.theme_parent_path %>', src: ['**','!css/dev/**'], dest: '<%= cfg.wp_theme_parent_root %>'},

		    ]
		  },

		  deps: {
		    files: [
		      // {expand:true, cwd: '<%= cfg.plugins_root %>/nextend-facebook-connect/', src: ['**'], dest: '<%= cfg.wp_plugins_root %>/nextend-facebook-connect/'},
		      // {expand:true, cwd: '<%= cfg.plugins_root %>/nextend-google-connect/', src: ['**'], dest: '<%= cfg.wp_plugins_root %>/nextend-google-connect/'},
		      // {expand:true, cwd: '<%= cfg.plugins_root %>/ajaxed-comments/', src: ['**'], dest: '<%= cfg.wp_plugins_root %>/ajaxed-comments/'}, // includes files in path and its subdirs,
		      // {expand:true, cwd: '<%= cfg.plugins_root %>/seo-automatic-links/', src: ['**'], dest: '<%= cfg.wp_plugins_root %>/seo-automatic-links/'}
		    ]
		  },

		  k4: {
		    files: [
		    	{expand:true, cwd: '<%= cfg.kidzou_path %>', src: ['**'], dest: '<%= cfg.wp_kidzou_root %>'}, // includes files in path and its subdirs,
		    ]
		  },

		  geods: {
		    files: [
		      {expand:true, cwd: '<%= cfg.plugins_root %>/geo-data-store/', src: ['**'], dest: '<%= cfg.wp_plugins_root %>/geo-data-store/'},
		    ]
		  },

		},

		phpdocumentor: {

	        // Grunt Target used to generate a first documentation
	        plugin_kidzou : {
	            options: {
	                directory : 'plugins/kidzou-4',
	                target : 'docs/'
	            }
	        },

	    }
		

	});

	grunt.loadNpmTasks('grunt-contrib-jshint'); //
	// grunt.loadNpmTasks('grunt-contrib-imagemin'); //
	// grunt.loadNpmTasks('grunt-contrib-uglify'); //
	//grunt.loadNpmTasks('grunt-contrib-cssmin'); //
	// grunt.loadNpmTasks('grunt-contrib-csslint'); //

	// grunt.loadNpmTasks('grunt-ftp-deploy'); //
	// grunt.loadNpmTasks('grunt-sftp-deploy');
	// grunt.loadNpmTasks('grunt-http');

	grunt.loadNpmTasks('grunt-contrib-copy'); //
	// grunt.loadNpmTasks('grunt-contrib-imagemin'); //
	// grunt.loadNpmTasks('grunt-contrib-less'); //
	// grunt.loadNpmTasks('grunt-contrib-concat');
	grunt.loadNpmTasks('grunt-plato');

	//distribution automatique des modifs
	// grunt.loadNpmTasks('grunt-contrib-watch');

	//nettoyage des fichiers
	grunt.loadNpmTasks('grunt-contrib-clean');

	grunt.loadNpmTasks('grunt-phpdocumentor');

	//lancement de grunt par defaut
	grunt.registerTask('default', ['prepjs','theme', 'plugins']);

	//mise en recette
	grunt.registerTask('recette', ['prepjs','theme', 'plugins', 'http:recette']);

	grunt.registerTask('prepjs', ['jshint','plato']);
	grunt.registerTask('theme',  ['copy:divi']); //csslint
	grunt.registerTask('plugins',['copy:deps', 'copy:k4', 'copy:geods']);
	grunt.registerTask('doc',['phpdocumentor:plugin_kidzou']);

};