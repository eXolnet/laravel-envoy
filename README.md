# Laravel Envoy Template

This repository contains automated deployment template for Laravel Envoy. The deployment flow is based on [Capistrano](http://capistranorb.com/).

## Installation

1. Add `laravel-envoy` to your `composer.json`'s development requires: `"exolnet/laravel-envoy": "dev-master"`
2. Run `composer.phar update exolnet/laravel-envoy`
3. Create a `Envoy.blade.php` on your project's root with the following content: `@include('vendor/exolnet/laravel-envoy/init.php')`
4. Create your deployment configuration in your Laravel project at `app/config/deploy.php`. An example config file is provided in this repository at `config/deploy.php.example`
5. Enjoy!


## License

This code is licensed under the [MIT license](http://choosealicense.com/licenses/mit/). Please see the [license file](LICENSE) for more information.
