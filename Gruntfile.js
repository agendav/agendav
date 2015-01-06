module.exports = function(grunt) {

  grunt.initConfig({
    pkg: grunt.file.readJSON('package.json'),

    less: {
      development: {
        options: {
          compress: false
        },
        files: {
          "./web/public/css/agendav.css": "./web/assets/stylesheets/agendav.less"
        }
      }
    },

    copy: {
        development: {
            files: [
                { expand: true, cwd: 'bower_components/jquery/dist', src: ['jquery.js'], dest: 'web/public/js/libs/' },
                { expand: true, cwd: 'bower_components/font-awesome/fonts', src: ['**'], dest: 'web/public/font/' },
                { expand: true, cwd: 'bower_components/bootstrap/js', src: ['button.js', 'tab.js'], dest: 'web/public/js/libs/' },
                { expand: true, cwd: 'bower_components/bootstrap/fonts', src: ['**'], dest: 'web/public/font/' },
                { expand: true, cwd: 'bower_components/dustjs-linkedin/dist', src: ['dust-core.js'], dest: 'web/public/js/templates/' },
                { expand: true, cwd: 'bower_components/dustjs-helpers/dist', src: ['dust-helpers.js'], dest: 'web/public/js/templates/' },
                { expand: true, cwd: 'bower_components/rrule/lib', src: ['rrule.js', 'nlp.js'], dest: 'web/public/js/libs/' },
                { expand: true, cwd: 'bower_components/es5-shim', src: ['es5-shim.js'], dest: 'web/public/js/libs/' },
            ],
        },

        dist: {
            files: [
               { expand: true, cwd: 'bower_components/font-awesome/fonts', src: ['**'], dest: 'web/public/build/font/' },
               { expand: true, cwd: 'bower_components/bootstrap/fonts', src: ['**'], dest: 'web/public/build/font/' },
               { expand: true, cwd: 'web/public/css/images', src: ['**'], dest: 'web/public/build/css/images/' },
               { expand: true, cwd: 'web/public/img', src: ['**'], dest: 'web/public/build/img/' },
            ],
        }
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
          dest: 'web/public/build/css/agendav-<%= pkg.version %>.css'
        },

        printcss: {
          src: [
            'web/public/css/app.print.css',
            'web/public/css/fullcalendar.print.css',
          ],
          dest: 'web/public/build/css/agendav-print-<%= pkg.version %>.css'
        },

        js: {
          src: [
            'web/public/js/libs/jquery.js',
            'web/public/js/libs/moment.js',
            'web/public/js/libs/button.js',
            'web/public/js/libs/jquery-ui.js',
            'web/public/js/libs/tab.js',
            'web/public/js/libs/jquery.timepicker.js',
            'web/public/js/libs/jquery.freeow.min.js',
            'web/public/js/libs/jquery.colorPicker.js',
            'web/public/js/libs/imagesloaded.pkg.min.js',
            'web/public/js/libs/jquery.qtip.js',
            'web/public/js/libs/jquery.colorhelpers.js',
            'web/public/js/libs/jquery.cookie.js',
            'web/public/js/libs/jquery.serializeobject.js',
            'web/public/js/libs/fullcalendar.js',
            'web/public/js/translation.js',
            'web/public/js/templates/dust-core.js',
            'web/public/js/templates/dust-helpers.js',
            'web/public/js/templates/templates.js',
            'web/public/js/datetime.js',
            'web/public/js/libs/rrule.js',
            'web/public/js/libs/nlp.js',
            'web/public/js/app.js',
          ],
          dest: 'web/public/build/js/agendav-<%= pkg.version %>.js'
        }
    },

    uglify: {
      options: {
        banner: '/*! <%= pkg.name %> <%= pkg.version %> <%= grunt.template.today("dd-mm-yyyy") %> */\n'
      },

      dist: {
        files: {
          'web/public/build/js/agendav-<%= pkg.version %>.min.js': ['<%= concat.js.dest %>']
        }
      }
    },

    cssmin: {
      dist: {
        files: [
          { 'web/public/build/css/agendav-<%= pkg.version %>.min.css' : '<%= concat.css.dest %>' },
          { 'web/public/build/css/agendav-print-<%= pkg.version %>.min.css' : '<%= concat.printcss.dest %>' }
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
        tasks: ['less:development']
      },
      dust: {
        files: ['./web/assets/templates/*.dust'],
        tasks: ['dust']
      }
    }
  });

  grunt.loadNpmTasks('grunt-contrib-uglify');
  grunt.loadNpmTasks('grunt-contrib-watch');
  grunt.loadNpmTasks('grunt-contrib-concat');
  grunt.loadNpmTasks('grunt-contrib-less');
  grunt.loadNpmTasks('grunt-contrib-cssmin');
  grunt.loadNpmTasks('grunt-contrib-copy');
  grunt.loadNpmTasks('grunt-dust');

  grunt.registerTask('default', [ 'copy:development', 'less:development', 'dust' ]);
  grunt.registerTask('dist', [ 'less', 'dust', 'copy', 'concat', 'uglify', 'cssmin' ]);

};
