# Laravel Envoy Template

[![Latest Stable Version](https://poser.pugx.org/eXolnet/laravel-envoy/v/stable?format=flat-square)](https://packagist.org/packages/eXolnet/laravel-envoy)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Build Status](https://img.shields.io/travis/eXolnet/laravel-envoy/master.svg?style=flat-square)](https://travis-ci.org/eXolnet/laravel-envoy)
[![Total Downloads](https://img.shields.io/packagist/dt/eXolnet/laravel-envoy.svg?style=flat-square)](https://packagist.org/packages/eXolnet/laravel-envoy)

This repository contains automated deployment template for Laravel Envoy. The deployment flow is based on [Capistrano](http://capistranorb.com/).

## Installation

1. Require this package with composer: `composer require exolnet/laravel-envoy`
2. Create a `Envoy.blade.php` on your project's root with the following content: `@include('vendor/exolnet/laravel-envoy/init.php')`
3. Create your deployment configuration in your Laravel project at `app/config/deploy.php`. An example config file is provided in this repository at `config/deploy.php.example`
4. Setup the deployment folders on your remote host: `vendor/bin/envoy run deploy:setup`
5. Enjoy!

## Usage

To deploy a new version, run the following command: `vendor/bin/envoy run deploy --commit=master`

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) and [CODE OF CONDUCT](CODE_OF_CONDUCT.md) for details.

## Security

If you discover any security related issues, please email security@exolnet.com instead of using the issue tracker.

## Credits

- [Alexandre D'Eschambeault](https://github.com/xel1045)
- [Tom Rochette](https://github.com/tomzx)
- [All Contributors](../../contributors)

## License

This code is licensed under the [MIT license](http://choosealicense.com/licenses/mit/). 
Please see the [license file](LICENSE) for more information.
