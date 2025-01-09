# Laravel Envoy Template

[![Latest Stable Version](https://img.shields.io/packagist/v/eXolnet/laravel-envoy.svg?style=flat-square)](https://packagist.org/packages/eXolnet/laravel-envoy)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![Build Status](https://img.shields.io/github/actions/workflow/status/eXolnet/laravel-envoy/tests.yml?label=tests&style=flat-square)](https://github.com.org/eXolnet/laravel-envoy/actions?query=workflow%3Atests)
[![Total Downloads](https://img.shields.io/packagist/dt/eXolnet/laravel-envoy.svg?style=flat-square)](https://packagist.org/packages/eXolnet/laravel-envoy)

This repository contains automated deployment template for Laravel Envoy. The deployment flow is based on [Capistrano](http://capistranorb.com/).

## Installation

1. Require this package with composer: `composer require --dev exolnet/laravel-envoy:"^1.9"`
2. Create a `Envoy.blade.php` on your project's root with the following content: `@import('exolnet/laravel-envoy')`

    For a typical Laravel project, you should have a file looking like:

    ```blade
    @import('exolnet/laravel-envoy')

    @task('deploy:publish')
        cd "{{ $releasePath }}"

        php artisan down

        php artisan config:cache
        php artisan event:cache
        php artisan route:cache
        php artisan view:cache

        php artisan storage:link

        php artisan migrate --force

        php artisan up
    @endtask
    ```

3. Create your deployment configuration in your Laravel project at `config/deploy.php`. An example config file is provided in this repository at `config/deploy.php`

    For a typical Laravel project, you should have a file looking like:

    ```php
    <?php

    return [
        'name' => 'example',

        'default' => 'production',

        'environments' => [
            'production' => [
                'ssh_host'       => 'example.com',
                'ssh_user'       => 'example',
                'deploy_path'    => '/srv/example',
                'repository_url' => 'git@github.com:example/example.git',
                'linked_files'   => ['.env'],
                'linked_dirs'    => ['storage/app', 'storage/framework', 'storage/logs'],
                'copied_dirs'    => ['node_modules', 'vendor'],
            ],
        ],
    ];
    ```

4. Enjoy!

## Upgrade

Please read [UPGRADE-1.x](UPGRADE-1.x.md) for the procedure to upgrade to version 1.x.

## Usage

The following macro are available:

* `vendor/bin/envoy run setup`: Setup the directory structure and repository on the remote host
* `vendor/bin/envoy run deploy --commit=abcdef`: Deploy commit `abcdef` to the remote host
* `vendor/bin/envoy run deploy:publish --current`: Run the `deploy:publish` task for the current release on the remote host
* `vendor/bin/envoy run releases`: List available releases on the remote host
* `vendor/bin/envoy run rollback [--release=123456]`: Rollback to previous release or to `123456` if specified on the remote host
* `vendor/bin/envoy run backups`: List existing backups on the remote host

You can also use the native Envoy command too:

* `vendor/bin/envoy tasks`: List available tasks and macros
* `vendor/bin/envoy ssh`: Connect to the remote host

Note that you can also use the option `--env=foo` with any of the previous command to connect to an other remote 
define in the configuration.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) and [CODE OF CONDUCT](CODE_OF_CONDUCT.md) for details.

## Security

SSH `StrictHostKeyChecking` is enforced when using git over ssh. If your remote git repository server does not support `SSHFP`/`VerifyHostKeyDNS`, you will need to manually create the `known_hosts` file on the remote host.

If you discover any security related issues, please email security@exolnet.com instead of using the issue tracker.

## Credits

- [Alexandre D'Eschambeault](https://github.com/xel1045)
- [Tom Rochette](https://github.com/tomzx)
- [Patricia Gagnon-Renaud](https://github.com/pgrenaud)
- [Simon Gaudreau](https://github.com/Gandhi11)
- [Martin Blanchette](https://github.com/martinblanchette)
- [All Contributors](../../contributors)

## License

Copyright © [eXolnet](https://www.exolnet.com). All rights reserved.

This code is licensed under the [MIT license](http://choosealicense.com/licenses/mit/).
Please see the [license file](LICENSE) for more information.
