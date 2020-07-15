var gulp = require('gulp');
var sass = require('gulp-sass');
var sourcemaps = require('gulp-sourcemaps');
var inlineimage = require('gulp-inline-image');
var concat = require('gulp-concat');
var rename = require('gulp-rename');
var uglify = require('gulp-uglify'); // minifies js
var terser = require('gulp-terser');
var exec = require('child_process').exec;

// paths
var css_directory = 'dist/css';
var sass_directory = 'src/sass';
var js_dist_directory = 'dist/js';
var js_src_directory = 'src/js';

// js from node_modules to include
var jquery_directory = 'node_modules/jquery/dist/jquery.min.js';
var popper_directory = 'node_modules/popper.js/dist/umd/popper.min.js';
var bootstrap_directory = 'node_modules/bootstrap/dist/js/bootstrap.min.js';

gulp.task('generate_css', function () {
	return gulp.src([
			sass_directory + '/*.scss' // multiple sources generate multiple css files
		])
		.pipe(sass({
			outputStyle: 'compressed',
			sourceComments: false
		}).on('error', sass.logError))
		.pipe(inlineimage())
		.pipe(sourcemaps.init())
		.pipe(sourcemaps.write("")) // adding a path in write, makes it write sourcemaps into a seperate file, which greatly reduces css file size
		.pipe(gulp.dest(css_directory));
});

gulp.task('generate_js', function() {
	return gulp.src([
			//jquery_directory, // disabling this, because drupal provides its own jquery library
			popper_directory,
			bootstrap_directory,
			js_src_directory + '/vendor/**/*.js', // vendor folder
			js_src_directory + '/*.js' // global.js
			//js_src_directory + '/modules/*.js' // modules folder, only when developing html, however, take this out and include each module script into its specific html
		])
		.pipe(concat('scripts.js'))
		.pipe(gulp.dest(js_dist_directory))
		.pipe(rename('scripts.min.js'))
		.pipe(terser())
		.pipe(gulp.dest(js_dist_directory));
});

// This gets executed with command: gulp
gulp.task('default', function() {
	console.log("Running initial generation...");
	exec('gulp generate_css generate_js', function (err, stdout, stderr) {
		if ( stderr ) {
			console.log("Initial generation had error:");
			console.log(stderr);
		} else {
			console.log("Initial generation successfull.");
		}
		console.log("Watching...");
		// watch: array(s) of paths to the files we want to be watched, task(s) to execute upon detecting a file change
		gulp.watch(sass_directory + '/**/*.scss', gulp.series('generate_css'));
		gulp.watch(js_src_directory + '/**/*.js', gulp.series('generate_js'));
	});
});

// If you ever need to include fonts straight into css, instead of url references, uncomment and create appropriate folders
/*
var inlinefonts = require('gulp-inline-fonts');
var merge  = require('merge-stream');

var font_directory = "src/fonts";
var compiled_fonts = "compiled_fonts.css"; // about to be generated

var fonts = [{
	font_family: "open_sans",
	weights: ["regular", "semibold", "bold", "extrabold"],
	formats: ["woff2", "woff"]
}];

gulp.task('generate_font_css', function() {
	// create an accumulated stream
	var fontStream = merge();

	// for each font
	fonts.forEach(function(font_item) {
		// for each weight
		font_item.weights.forEach(function(font_item_weight) {
			// for each format
			font_item.formats.forEach(function(font_item_format) {
				fontStream.add(gulp.src(font_directory + '/font_files/' + font_item.font_family + '/' + font_item.font_family + '_' + font_item_weight + '_webfont.' + font_item_format)
								.pipe(inlinefonts({ name: font_item.font_family, weight: font_item_weight, format: [font_item_format] })));
			});
		});
	});

	return fontStream
		.pipe(concat(compiled_fonts))
		.pipe(gulp.dest(font_directory));
});
*/
