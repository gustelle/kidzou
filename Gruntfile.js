

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
		      "-W044": true  //escapes dans les regex
		    },
			all: ['<%= cfg.kidzou_path %>/public/asets/js/public.js',
				  '<%= cfg.kidzou_path %>/asets/js/kidzou-geo.js.js',
				  '<%= cfg.kidzou_path %>/asets/js/kidzou-storage.js',
				  '<%= cfg.kidzou_path %>/asets/js/kidzou-client.js',
				  '<%= cfg.kidzou_path %>/asets/js/kidzou-events.js',
				  '<%= cfg.kidzou_path %>/asets/js/kidzou-place.js',
				  // '<%= cfg.theme_path %>/js/custom.js',
				  ] 
		},

		//quality reports pour les JS
		plato: {
			options : {
		      jshint : false //deja fait par ailleurs
		    },
		    front: {
		      files: {
		        'reports': ['plugins/kidzou/js/front/*.js'],
		      }
		    },
		  },

		//quality check pour les CSS
		csslint: {
		  strict: {
		    options: {
		      import: false,
		      "unique-headings": false,
		    },
		    src: ['<%= cfg.theme_path %>/style.css'] //'css/vex.css','css/vex-theme-default.css','css/vex-theme-top-w750.css'
		  }
		},
		

		//tache de déploiement en prod
		'ftp-deploy': {
			plugins: {
				auth: {
				  host: 'www.kidzou.fr',
				  port: 21,
				  authKey: 'prod'
				},
				src: './plugins',
				dest: '/wp-content/plugins',
				exclusions: ['./plugins/kidzou', './plugins/kidzou-clients', './plugins/kidzou-contest', './plugins/kidzou-events', './plugins/kidzou-geo', './plugins/kidzou-users', './plugins/seo-automatic-links'] //livré une fois, pas à chauqe fois pour perf du process
				
			},
			
			themes: {
				auth: {
				  host: 'www.kidzou.fr',
				  port: 21,
				  authKey: 'prod'
				},
				src: './themes',
				dest: '/wp-content/themes',
				exclusions: ['./themes/Trim-child', './themes/Trim']

			}
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
		      {expand:true, cwd: '<%= cfg.plugins_root %>/nextend-facebook-connect/', src: ['**'], dest: '<%= cfg.wp_plugins_root %>/nextend-facebook-connect/'},
		      {expand:true, cwd: '<%= cfg.plugins_root %>/nextend-google-connect/', src: ['**'], dest: '<%= cfg.wp_plugins_root %>/nextend-google-connect/'},
		      {expand:true, cwd: '<%= cfg.plugins_root %>/ajaxed-comments/', src: ['**'], dest: '<%= cfg.wp_plugins_root %>/ajaxed-comments/'}, // includes files in path and its subdirs,
		      // {expand:true, cwd: '<%= cfg.plugins_root %>/seo-automatic-links/', src: ['**'], dest: '<%= cfg.wp_plugins_root %>/seo-automatic-links/'}
		    ]
		  },

		  k4: {
		    files: [
		    	{expand:true, cwd: '<%= cfg.kidzou_path %>', src: ['**'], dest: '<%= cfg.wp_kidzou_root %>'}, // includes files in path and its subdirs,
		    ]
		  },

		},
		

	});

	grunt.loadNpmTasks('grunt-contrib-jshint'); //
	grunt.loadNpmTasks('grunt-contrib-imagemin'); //
	grunt.loadNpmTasks('grunt-contrib-uglify'); //
	//grunt.loadNpmTasks('grunt-contrib-cssmin'); //
	grunt.loadNpmTasks('grunt-contrib-csslint'); //
	grunt.loadNpmTasks('grunt-ftp-deploy'); //
	grunt.loadNpmTasks('grunt-contrib-copy'); //
	grunt.loadNpmTasks('grunt-contrib-imagemin'); //
	grunt.loadNpmTasks('grunt-contrib-less'); //
	grunt.loadNpmTasks('grunt-contrib-concat');
	grunt.loadNpmTasks('grunt-plato');

	//distribution automatique des modifs
	grunt.loadNpmTasks('grunt-contrib-watch');

	//nettoyage des fichiers
	grunt.loadNpmTasks('grunt-contrib-clean');

	//lancement de grunt par defaut
	grunt.registerTask('default', ['prepjs','theme', 'plugins']);

	grunt.registerTask('prepjs', ['jshint','plato']);
	grunt.registerTask('theme',  ['copy:divi']); //csslint
	grunt.registerTask('plugins',['copy:deps', 'copy:k4']);

};