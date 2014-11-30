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

  grunt.registerTask('default', [ 'dist' ]);

};
