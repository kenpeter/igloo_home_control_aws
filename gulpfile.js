// Hak
// https://laracasts.com/discuss/channels/laravel/angular2-laravel-hello-world-app

// Hak
// https://github.com/laravel/elixir/issues/284
process.env.DISABLE_NOTIFIER = true;

var gulp = require("gulp");
var elixir = require('laravel-elixir');
require('laravel-elixir-livereload');
var elixirTypscript = require('elixir-typescript');


/*
 |--------------------------------------------------------------------------
 | Elixir Asset Management
 |--------------------------------------------------------------------------
 |
 | Elixir provides a clean, fluent API for defining some basic Gulp tasks
 | for your Laravel application. By default, we are compiling the Sass
 | file for our application, as well as publishing vendor resources.
 |
 */

// Hak
elixir(function(mix) {
  mix.sass('app.scss');

  mix.copy('node_modules/@angular', 'public/@angular');
  mix.copy('node_modules/rxjs', 'public/rxjs');
  mix.copy('node_modules/systemjs', 'public/systemjs');
  mix.copy('node_modules/es6-promise', 'public/es6-promise');
  mix.copy('node_modules/es6-shim', 'public/es6-shim');
  mix.copy('node_modules/zone.js', 'public/zone.js');
  mix.copy('node_modules/satellizer', 'public/satellizer');
  mix.copy('node_modules/platform', 'public/platform');
  mix.copy('node_modules/reflect-metadata', 'public/reflect-metadata');

  mix.typescript(
    '/**/*.ts',
    'public/js',
    {
      "target": "es5",
      "module": "system",
      "moduleResolution": "node",
      "sourceMap": true,
      "emitDecoratorMetadata": true,
      "experimentalDecorators": true,
      "removeComments": false,
      "noImplicitAny": false
    }
  );
  
  mix.livereload();

});
