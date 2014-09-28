

module.exports = function(grunt) {
  // Do grunt-related things in here

	grunt.initConfig({
		pkg: grunt.file.readJSON('package.json'),

		//compilation des CSS pour le theme
		less: {
		  trim: {
		    options: {
		      paths: [""]
		    },
		    files: {
		      "themes/Trim-child/css/dist/main.css"		: "themes/Trim-child/css/dev/main.less",
		      "themes/Trim-child/css/dist/ads.css"		: "themes/Trim-child/css/dev/ads.less",
		      "themes/Trim-child/css/dist/admin.css"	: "themes/Trim-child/css/dev/admin.less",
		      "themes/Trim-child/css/dist/nav.css"		: "themes/Trim-child/css/dev/nav.less",
		      "themes/Trim-child/css/dist/crp.css"		: "themes/Trim-child/css/dev/crp.less",
		      "themes/Trim-child/css/dist/panels.css"	: "themes/Trim-child/css/dev/panels.less",
		      "themes/Trim-child/css/dist/icons.css"	: "themes/Trim-child/css/dev/icons.less",
		      "themes/Trim-child/css/dist/messages.css"	: "themes/Trim-child/css/dev/messages.less",
		      "themes/Trim-child/css/dist/votables.css"	: "themes/Trim-child/css/dev/votables.less",
		      "themes/Trim-child/css/dist/links.css"	: "themes/Trim-child/css/dev/links.less",
		    }
		  },
		  kidzou: {
		    options: {
		      paths: [""]
		    },
		    files: {
		      "plugins/kidzou/css/kidzou-megadropdown.css"		: "plugins/kidzou/css/less/kidzou-megadropdown.less",
		      "plugins/kidzou/css/kidzou-form.css"				: "plugins/kidzou/css/less/kidzou-form.less",
		      "plugins/kidzou-events/css/kidzou-events.css"	: "plugins/kidzou-events/css/less/kidzou-events.less",
		    }
		  }
		},
		

		//nettoyage des répertoires ou se trouvent les fichiers minifiés et les CSS compilées
		clean: ["plugins/kidzou/js/front/dist/", 
				"plugins/kidzou/js/worker/dist/", 
				"themes/Trim-child/js/dist/",
				"themes/Trim-child/css/dist/"],
		
		//minification
		uglify: {

		    kidzou: {
		      files: {
		        'plugins/kidzou/js/front/dist/<%= pkg.name %>.<%= pkg.version %>.js': ['plugins/kidzou/js/front/<%= pkg.name %>.concat.js'],
		      }
		    },
		    theme: {
		      files: {
		        'themes/Trim-child/js/dist/custom.<%= pkg.version %>.min.js': ['themes/Trim-child/js/custom.dev.min.js'],
		        'themes/Trim/js/superfish.js': ['themes/Trim/js/superfish.source.js']
		      }
		    },
		    // connections: {
		    //   files: {
		    //     'connections_templates/cmap/template.js': ['connections_templates/cmap/template.source.js'],
		    //   }
		    // },
		    localcache: {
		      files: {
		        'plugins/kidzou/js/front/dist/local-cache.min.js': ['plugins/kidzou/js/front/local-cache.js'],
		      }
		    }
		},

		//quality check pour les JS
		jshint: {
			options: {
		      "-W099": true, //mixed spaces and tabs (smarttabs)
		      "-W065": true,	//radix param sur la fonction parseInt(),
		      "-W044": true  //escapes dans les regex
		    },
			all: ['js/kidzou-dev.js',
				  'themes/Trim-child/js/custom.dev.min.js',
				  'js/kidzou-actions-dev.js',
				  'js/kidzou-login-dev.js',
				  'js/kidzou-message-dev.js',
				  'js/kidzou-tracker-dev.js',
				  'js/kidzou-layout-dev.js',
				  'js/kidzou-storage-dev.js',
				  // 'js/kidzou-geo-dev.js',
				  // 'js/kidzou-map-dev.js',
				  ] //,'plugins_integration/login-with-ajax/login-with-ajax.source.js'
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
		    src: ['themes/Trim-child/css/dist/nav.css'] //'css/vex.css','css/vex-theme-default.css','css/vex-theme-top-w750.css'
		  }
		},

		// cssmin: {
		//   minify: {
		//     expand: true,
		//     src: ['**/*.css', '!*.min.css','!plugins/foxycomplete/css/foxycomplete.css'],
		//     ext: '.min.css'
		//   }
		// },
		

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
				exclusions: ['./plugins/really-simple-events'] //livré une fois, pas à chauqe fois pour perf du process
				
			},
			// connections_templates: {
			// 	auth: {
			// 	  host: 'www.kidzou.fr',
			// 	  port: 21,
			// 	  authKey: 'prod'
			// 	},
			// 	src: './connections_templates',
			// 	dest: '/wp-content/connections_templates',
			// 	// exclusions: []
			// },
			themes: {
				auth: {
				  host: 'www.kidzou.fr',
				  port: 21,
				  authKey: 'prod'
				},
				src: './themes',
				dest: '/wp-content/themes',
				exclusions: ['./themes/Trim-child/css/dev', 
							 './themes/Trim-child/css/dist']

			}
		},

		//tache de déploiement en local
		copy: {
		  trim: {
		    files: [
		    	{expand:true, cwd: 'themes/Trim/', src: ['**'], dest: '/Users/guillaume/Sites/wordpress/wp-content/themes/Trim/'}, // includes files in path and its subdirs,
		     	{expand:true, cwd: 'themes/Trim-child/', src: ['**','!css/dev/**'], dest: '/Users/guillaume/Sites/wordpress/wp-content/themes/Trim-child/'},
		    	// {expand:true, cwd: 'themes/Trim-child/css/dist', src: ['style.css'], dest: '../../themes/Trim-child/'},
		    ]
		  },

		  divi: {
		    files: [
		     	{expand:true, cwd: 'themes/Divi-child/', src: ['**','!css/dev/**'], dest: '/Users/guillaume/Sites/wordpress/wp-content/themes/Divi-child/'},
		    ]
		  },

		  // connections: {
		  //   files: [
		  //     {expand:true, cwd: 'plugins/connections/images/', src: ['**'], dest: '/Users/guillaume/Sites/wordpress/wp-content/plugins/connections/images/'},
		  //     {expand:true, cwd: 'plugins/connections/includes/', src: ['**'], dest: '/Users/guillaume/Sites/wordpress/wp-content/plugins/connections/includes/'},
		  //     {expand:true, cwd: 'connections_templates/', src: ['**'], dest: '/Users/guillaume/Sites/wordpress/wp-content/connections_templates/'}
		  //   ]
		  // },
		  // nextend: {
		  //   files: [
		  //     {expand:true, cwd: 'plugins/nextend-facebook-connect/', src: ['**'], dest: '/Users/guillaume/Sites/wordpress/wp-content/plugins/nextend-facebook-connect/'},
		  //     {expand:true, cwd: 'plugins/nextend-google-connect/', src: ['**'], dest: '/Users/guillaume/Sites/wordpress/wp-content/plugins/nextend-google-connect/'}
		  //   ]
		  // },
		  // kidzou: {
		  //   files: [
		  //   	{expand:true, cwd: 'plugins/kidzou/', src: ['**'], dest: '/Users/guillaume/Sites/wordpress/wp-content/plugins/kidzou/'}, // includes files in path and its subdirs,
		  //   ]
		  // },

		  // events: {
		  //   files: [
		  //   	{expand:true, cwd: 'plugins/kidzou-events/', src: ['**','less/**'], dest: '/Users/guillaume/Sites/wordpress/wp-content/plugins/kidzou-events/'}, // includes files in path and its subdirs,
		  //   ]
		  // },
		  // clients: {
		  //   files: [
		  //   	{expand:true, cwd: 'plugins/kidzou-clients/', src: ['**'], dest: '/Users/guillaume/Sites/wordpress/wp-content/plugins/kidzou-clients/'}, // includes files in path and its subdirs,
		  //   ]
		  // },
		  // geo: {
		  //   files: [
		  //   	{expand:true, cwd: 'plugins/kidzou-geo/', src: ['**'], dest: '/Users/guillaume/Sites/wordpress/wp-content/plugins/kidzou-geo/'}, // includes files in path and its subdirs,
		  //   ]
		  // },
		  // contest: {
		  //   files: [
		  //   	{expand:true, cwd: 'plugins/kidzou-contest/', src: ['**'], dest: '/Users/guillaume/Sites/wordpress/wp-content/plugins/kidzou-contest/'}, // includes files in path and its subdirs,
		  //   ]
		  // },
		  // users: {
		  //   files: [
		  //   	{expand:true, cwd: 'plugins/kidzou-users/', src: ['**'], dest: '/Users/guillaume/Sites/wordpress/wp-content/plugins/kidzou-users/'}, // includes files in path and its subdirs,
		  //   ]
		  // },
		  // crp: {
		  //   files: [
		  //   	{expand:true, cwd: 'plugins/contextual-related-posts/', src: ['**'], dest: '/Users/guillaume/Sites/wordpress/wp-content/plugins/contextual-related-posts/'}, // includes files in path and its subdirs,
		  //   ]
		  // },
		  kidzou4: {
		    files: [
		    	{expand:true, cwd: 'plugins/kidzou-4/', src: ['**'], dest: '/Users/guillaume/Sites/wordpress/wp-content/plugins/kidzou-4/'}, // includes files in path and its subdirs,
		    ]
		  },
		  // wpsp: { //superchache
		  //   files: [
		  //     {expand:true, cwd: 'plugins/wp-super-cache/', src: ['**'], dest: '/Users/guillaume/Sites/wordpress/wp-content/plugins/wp-super-cache/'}
		  //   ]
		  // },

		},

		concat: {
			trim: {
			  src: ['themes/Trim-child/css/dist/main.css',
			  		'themes/Trim-child/css/dist/links.css',
			  		'themes/Trim-child/css/dist/messages.css',
			  		'themes/Trim-child/css/dist/votables.css',
			  		'themes/Trim-child/css/dist/icons.css',
			  		'themes/Trim-child/css/dist/events.css',
			  		'themes/Trim-child/css/dist/panels.css',
			  		'themes/Trim-child/css/dist/crp.css',
			  		'themes/Trim-child/css/dist/ads.css',
			  		'themes/Trim-child/css/dist/admin.css',
			  		'themes/Trim-child/css/dist/nav.css',
			  		'themes/Trim-child/css/vex.css',
			  		'themes/Trim-child/css/vex-theme-default.css',
			  		'themes/Trim-child/css/vex-theme-top-w750.css'],
			  dest: 'themes/Trim-child/style.css',
			},
			js: {
			  src: ['plugins/kidzou/js/front/<%= pkg.name %>-actions-dev.js',
			  		'plugins/kidzou/js/front/<%= pkg.name %>-login-dev.js',
			  		'plugins/kidzou/js/front/<%= pkg.name %>-tracker-dev.js',
			  		'plugins/kidzou/js/front/<%= pkg.name %>-message-dev.js',
			  		'plugins/kidzou/js/front/<%= pkg.name %>-layout-dev.js',
			  		'plugins/kidzou/js/front/<%= pkg.name %>-storage-dev.js',
			  		// 'plugins/kidzou/js/front/<%= pkg.name %>-geo-dev.js',
			  		// 'plugins/kidzou/js/front/<%= pkg.name %>-map-dev.js',
			  		'plugins/kidzou/js/front/<%= pkg.name %>-dev.js'],
			  dest: 'plugins/kidzou/js/front/<%= pkg.name %>.concat.js',
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
	grunt.registerTask('default', ['clean','prepjs', 'theme', 'plugins']);

	grunt.registerTask('prepjs', ['concat:js','jshint','uglify','plato']); 
	grunt.registerTask('theme', ['copy:divi']); //csslint
	grunt.registerTask('plugins', ['copy:kidzou4']); 

};