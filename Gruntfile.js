module.exports = function(grunt) {

  grunt.initConfig({
    pkg: grunt.file.readJSON('package.json'),

    less: {
      main: {
        options: {
          compress: false
        },
        files: {
          "web/public/css/agendav.css": "web/assets/stylesheets/agendav.less"
        }
      }
    },

    // rrule.js needs a patch (https://github.com/jkbrzt/rrule/pull/82)
    exec: {
      patch_rrule: {
        cwd: 'bower_components/rrule/lib',
        command: 'patch -p2 < ../../../patches/rrule-date.patch'
      }
    },

    bowercopy: {
        libs: {
          options: {
            destPrefix: 'web/public/js/libs'
          },
          files: {
            'jquery.js': 'jquery/dist/jquery.js',
            'button.js': 'bootstrap/js/button.js',
            'tab.js': 'bootstrap/js/tab.js',
            'rrule.js': 'rrule/lib/rrule.js',
            'nlp.js': 'rrule/lib/nlp.js',
            'fullcalendar.js': 'fullcalendar/dist/fullcalendar.js',
            'moment.js': 'moment/moment.js',
            'es5-shim.js': 'es5-shim/es5-shim.js',
            'moment-timezone-with-data-2010-2020.min.js': 'moment-timezone/builds/moment-timezone-with-data-2010-2020.min.js',
          }
        },

        fonts: {
          options: {
            destPrefix: 'web/public/font'
          },
          files: {
            'fa': 'font-awesome/fonts/*',
            'bootstrap': 'bootstrap/fonts/*',
          }
        },

        templating: {
          options: {
            destPrefix: 'web/public/js/templates'
          },
          files: {
            'dust-core.js': 'dustjs-linkedin/dist/dust-core.js',
            'dust-helpers.js': 'dustjs-helpers/dist/dust-helpers.js',
          }
        },

        fullcalendarlangs: {
          options: {
            destPrefix: 'web/public/js/fullcalendar'
          },
          files: {
            'lang': 'fullcalendar/dist/lang/*',
          }
        },

        fullcalendarcss: {
          options: {
            destPrefix: 'web/public/css'
          },
          files: {
            'fullcalendar.css': 'fullcalendar/dist/fullcalendar.css',
          }
        },
    },

    concat: {
        css: {
          src: [
            'web/public/css/agendav.css',
            'web/public/css/jquery-ui.css',
            'web/public/css/jquery-ui.structure.css',
            'web/public/css/jquery-ui.theme.css',
            'web/public/css/fullcalendar.css',
            'web/public/css/jquery.qtip.css',
            'web/public/css/freeow.css',
            'web/public/css/jquery.timepicker.css',
            'web/public/css/colorpicker.css',
          ],
          dest: 'web/public/css/agendav-built-<%= pkg.version %>.css'
        },

        printcss: {
          src: [
            'web/public/css/app.print.css',
            'web/public/css/fullcalendar.print.css',
          ],
          dest: 'web/public/css/agendav-built-print-<%= pkg.version %>.css'
        },

        js: {
          src: [
            'web/public/js/libs/jquery.js',
            'web/public/js/libs/moment.js',
            'web/public/js/libs/moment-timezone-with-data-2010-2020.min.js',
            'web/public/js/libs/button.js',
            'web/public/js/libs/jquery-ui.js',
            'web/public/js/libs/tab.js',
            'web/public/js/libs/jquery.timepicker.js',
            'web/public/js/libs/jquery.freeow.min.js',
            'web/public/js/libs/jquery.colorPicker.js',
            'web/public/js/libs/imagesloaded.pkg.min.js',
            'web/public/js/libs/jquery.qtip.js',
            'web/public/js/libs/jquery.serializeobject.js',
            'web/public/js/libs/fullcalendar.js',
            'web/public/js/libs/rrule.js',
            'web/public/js/libs/nlp.js',
            'web/public/js/templates/dust-core.js',
            'web/public/js/templates/dust-helpers.js',
            'web/public/js/templates/templates.js',
            'web/public/js/datetime.js',
            'web/public/js/repeat-form.js',
            'web/public/js/app.js',
          ],
          dest: 'web/public/js/agendav-built-<%= pkg.version %>.js'
        }
    },

    uglify: {
      options: {
        banner: '/*! <%= pkg.name %> <%= pkg.version %> <%= grunt.template.today("dd-mm-yyyy") %> */\n'
      },

      dist: {
        files: {
          'web/public/js/agendav-built-<%= pkg.version %>.min.js': ['<%= concat.js.dest %>']
        }
      }
    },

    cssmin: {
      dist: {
        files: [
          { 'web/public/css/agendav-built-<%= pkg.version %>.min.css' : '<%= concat.css.dest %>' },
          { 'web/public/css/agendav-built-print-<%= pkg.version %>.min.css' : '<%= concat.printcss.dest %>' }
        ]
      }
    },

    dust: {
        defaults: {
            files: {
                "web/public/js/templates/templates.js": "web/assets/templates/*.dust"
            },
            options: {
                wrapper: false,
                runtime: false,
                basePath: "web/assets/templates/"
            }
        }
    },

    watch: {
      less: {
        files: ['./web/assets/stylesheets/*.less'],
        tasks: ['less']
      },
      dust: {
        files: ['./web/assets/templates/*.dust'],
        tasks: ['dust']
      }
    },

    composer: {
      dist: {
        options: {
          cwd: 'web',
          flags: ['no-dev', 'prefer-dist'],
        }
      },
      dev: {
        options: {
          cwd: 'web',
          flags: ['prefer-source'],
        }
      }
    },

    env: {
      dist: {
        COMPOSER_VENDOR_DIR: 'vendor/',
      },
    },

    copy: {
      dist: {
        src: [
          '**',
          '!**/*.phar',
          '!**/bower_components/**',
          '!**/doc/build/**',
          '!**/dist/**',
          '!**/node_modules/**',
          '!**/patches/**',
          '!**/docs/**',
          '!**/web/var/cache/twig/**',
          '!**/web/var/cache/profiler/**',
          '**/web/var/cache/twig/.gitignore',
          '**/web/var/cache/profiler/.gitignore',
          '!vagrant_ansible_inventory_default',
          '!Vagrantfile',
          '!**/ansible/**',
          '!**/artwork/**',
          '!**/config/settings*'
        ],
        dest: 'dist/agendav-<%= pkg.version %>',
        expand: true
      },
    },

    clean: [ "bower_components" ],

    "bower-install-simple": {
      prod: {
        options: {
          color: true,
        }
      },
    },

    compress: {
      targz: {
        options: {
          mode: 'tgz',
          archive: 'dist/agendav-<%= pkg.version %>.tar.gz',
        },
        files: [ 
          { expand: true, cwd: 'dist/', src: 'agendav-<%= pkg.version %>/**' }
        ]
      },
    },

  });


  grunt.loadNpmTasks('grunt-contrib-uglify');
  grunt.loadNpmTasks('grunt-contrib-watch');
  grunt.loadNpmTasks('grunt-contrib-concat');
  grunt.loadNpmTasks('grunt-contrib-less');
  grunt.loadNpmTasks('grunt-contrib-cssmin');
  grunt.loadNpmTasks('grunt-contrib-copy');
  grunt.loadNpmTasks('grunt-dust');
  grunt.loadNpmTasks('grunt-exec');
  grunt.loadNpmTasks('grunt-composer');
  grunt.loadNpmTasks("grunt-bower-install-simple");
  grunt.loadNpmTasks('grunt-bowercopy');
  grunt.loadNpmTasks('grunt-env');
  grunt.loadNpmTasks('grunt-contrib-clean');
  grunt.loadNpmTasks('grunt-contrib-compress');


  grunt.registerTask('common-deps', [
      'clean',
      'bower-install-simple',
      'exec:patch_rrule',
      'bowercopy',
      'less',
      'dust'
  ]);

  // Build development environment by default
  grunt.registerTask('default', [
      'common-deps',
      'composer:dev:install',
      'concat',
      'uglify',
      'cssmin'
  ]);

  grunt.registerTask('build', [
      'common-deps',
      'env:dist',
      'composer:dist:install',
      'concat',
      'uglify',
      'cssmin'
  ]);

  grunt.registerTask('dist', [
      'build',
      'composer:dist:dump-autoload:optimize',
      'copy:dist',
      'compress:targz'
  ]);

};
