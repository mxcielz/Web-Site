const gulp = require('gulp');
const cssnano = require('gulp-cssnano');
const uglify = require('gulp-uglify');
const imagemin = require('gulp-imagemin');
const concat = require('gulp-concat');
const sourcemaps = require('gulp-sourcemaps');
const autoprefixer = require('gulp-autoprefixer');
const rename = require('gulp-rename');

// Minificar CSS
gulp.task('css', () => {
    return gulp.src(['styles.css', 'nav-footer.css'])
        .pipe(sourcemaps.init())
        .pipe(autoprefixer())
        .pipe(cssnano())
        .pipe(rename({ suffix: '.min' }))
        .pipe(sourcemaps.write('.'))
        .pipe(gulp.dest('dist/css'));
});

// Minificar JavaScript
gulp.task('js', () => {
    return gulp.src(['assets/js/*.js', '!assets/js/*.min.js'])
        .pipe(sourcemaps.init())
        .pipe(uglify())
        .pipe(rename({ suffix: '.min' }))
        .pipe(sourcemaps.write('.'))
        .pipe(gulp.dest('dist/js'));
});

// Otimizar imagens
gulp.task('images', () => {
    return gulp.src('assets/images/**/*')
        .pipe(imagemin([
            imagemin.gifsicle({ interlaced: true }),
            imagemin.mozjpeg({ quality: 75, progressive: true }),
            imagemin.optipng({ optimizationLevel: 5 }),
            imagemin.svgo({
                plugins: [
                    { removeViewBox: true },
                    { cleanupIDs: false }
                ]
            })
        ]))
        .pipe(gulp.dest('dist/images'));
});

// Watch task
gulp.task('watch', () => {
    gulp.watch(['styles.css', 'nav-footer.css'], gulp.series('css'));
    gulp.watch(['assets/js/*.js', '!assets/js/*.min.js'], gulp.series('js'));
    gulp.watch('assets/images/**/*', gulp.series('images'));
});

// Build task
gulp.task('build', gulp.parallel('css', 'js', 'images'));

// Default task
gulp.task('default', gulp.series('build', 'watch')); 