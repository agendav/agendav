// duster.js  
// Watch directory of dust.js templates and automatically compile them
// by Dan McGrady http://dmix.ca

var src_path = "./src/"; // directory of dust templates are stored with .dust file extension
var public_path = "../public/js/templates/"; // directory where the compiled .js files should be saved to

var fs = require('fs');                                                                        
var dust = require('dustjs-linkedin');
var watcher = require('nodewatch');


watcher.add(src_path);

var regexpSwp = new RegExp(/\.swp/);

watcher.onChange(function(path, prev, curr, action) {
  if (!path.match(regexpSwp)) {
	console.log('Something changed');
  	setTimeout(compile_templates, 400, path);
  }
});


var compile_templates = function compilar(path) {
    var files = fs.readdirSync(src_path);
    var result = '';

    for (var i=0;i<files.length;i++) {
        if (!files[i].match(regexpSwp)) {
		var filename = files[i].replace(".dust","");
		var contents = fs.readFileSync(src_path + files[i], 'utf8');
		try {
			result += dust.compile(contents, filename);
		} catch (err) {
			console.log('Oops! There was an error compiling: ');
			console.log(err);
			return;
		}
	}
    }

    var filepath = public_path + "templates.js";

    fs.writeFile(filepath, result, function (err) {
      if (err) throw err;
      console.log('Saved');
    });
  };
