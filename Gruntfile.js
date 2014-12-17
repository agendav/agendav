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
                { expand: true, cwd: 'bower_components/font-awesome/fonts', src: ['**'], dest: 'web/public/font/' },
                { expand: true, cwd: 'bower_components/bootstrap/js', src: ['button.js', 'tab.js'], dest: 'web/public/js/libs/' },
                { expand: true, cwd: 'bower_components/bootstrap/fonts', src: ['**'], dest: 'web/public/font/' },
                { expand: true, cwd: 'bower_components/dustjs-linkedin/dist', src: ['dust-core.js'], dest: 'web/public/js/templates/' },
                { expand: true, cwd: 'bower_components/dustjs-helpers/dist', src: ['dust-helpers.js'], dest: 'web/public/js/templates/' },
            ],
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
  grunt.loadNpmTasks('grunt-contrib-copy');
  grunt.loadNpmTasks('grunt-dust');

  grunt.registerTask('default', [ 'dist' ]);

};
