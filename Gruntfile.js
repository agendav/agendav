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
                { expand: true, cwd: 'bower_components/bootstrap/js', src: ['button.js'], dest: 'web/public/js/libs/' },
            ],
        }
    },

    watch: {
      less: {
        files: ['./web/assets/stylesheets/*.less'],
        tasks: ['less:development']
      }
    }
  });

  grunt.loadNpmTasks('grunt-contrib-uglify');
  grunt.loadNpmTasks('grunt-contrib-watch');
  grunt.loadNpmTasks('grunt-contrib-concat');
  grunt.loadNpmTasks('grunt-contrib-less');
  grunt.loadNpmTasks('grunt-contrib-copy');

  grunt.registerTask('default', [ 'dist' ]);

};
