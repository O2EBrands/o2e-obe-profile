let gulp = require("gulp"),
  sass = require("gulp-sass")(require("sass")),
  sourcemaps = require("gulp-sourcemaps"),
  $ = require("gulp-load-plugins")(),
  cleanCss = require("gulp-clean-css"),
  rename = require("gulp-rename"),
  postcss = require("gulp-postcss"),
  autoprefixer = require("autoprefixer"),
  postcssInlineSvg = require("postcss-inline-svg"),
  browserSync = require("browser-sync").create();
(pxtorem = require("postcss-pxtorem")),
  (postcssProcessors = [
    postcssInlineSvg({
      removeFill: true,
      paths: ["./node_modules/bootstrap-icons/icons"],
    }),
    pxtorem({
      propList: [
        "font",
        "font-size",
        "line-height",
        "letter-spacing",
        "*margin*",
        "*padding*",
      ],
      mediaQuery: true,
    }),
  ]);

const paths = {
  scss: {
    src: "./scss/style.scss",
    dest: "./css",
    watch: "./scss/**/*.scss",
    bootstrap: "./node_modules/bootstrap/scss/bootstrap.scss",
  },
  js: {
    bootstrap: "./node_modules/bootstrap/dist/js/bootstrap.min.js",
    popper: "./node_modules/@popperjs/core/dist/umd/popper.min.js",
    dest: "./js",
  },
};

// Compile sass into CSS & auto-inject into browsers
function styles() {
  return gulp
    .src([paths.scss.bootstrap, paths.scss.src])
    .pipe(sourcemaps.init())
    .pipe(
      sass({
        includePaths: ["./node_modules/bootstrap/scss"],
      }).on("error", sass.logError)
    )
    .pipe($.postcss(postcssProcessors))
    .pipe(postcss([autoprefixer()]))
    .pipe(sourcemaps.write())
    .pipe(gulp.dest(paths.scss.dest))
    .pipe(cleanCss())
    .pipe(rename({ suffix: ".min" }))
    .pipe(gulp.dest(paths.scss.dest))
    .pipe(browserSync.stream());
}

// Move the javascript files into our js folder
function js() {
  return gulp
    .src([paths.js.bootstrap, paths.js.popper])
    .pipe(gulp.dest(paths.js.dest));
}

// Static Server + watching scss/html files
function serve() {
  browserSync.init({
    proxy: "https://www.drupal.org",
  });

  gulp
    .watch([paths.scss.watch, paths.scss.bootstrap], styles)
    .on("change", browserSync.reload);
}

const build = gulp.series(styles, js);

exports.styles = styles;
exports.js = js;
exports.serve = serve;

exports.default = build;
