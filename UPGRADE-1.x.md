# Upgrade guide for 1.0.x

## Notable changes

* Many variables have been renamed for consistency and some have been removed since they are no longer used
  * `server` have been replaced with `ssh_host` and `ssh_user`
  * `deploy_to` have been replaced with `deploy_path`
  * `assets_path` have been added and default to the root of the project
  * `repo_url` have been renamed to `repository_url`
  * `repo_branch` have been removed
  * `copied_files` have been added
  * `copied_dirs` have been added
  * `tmp_dir` have been removed
  * `cmd_*` have been added with their respective defaults
* Most of the tasks and macros have been renamed, reorganized and cleaned up
* The git repository is now cloned as a bare repository in a different directory (`repository` instead of `repo`)
* The directory `repo` is removed if it exists when running the `setup`
* SSH `StrictHostKeyChecking` is now enforced when using git over ssh
* Drop support for `git submodules` and repository sub-tree
* New release is now created using git workspace instead of being copied with rsync
* Files and directory can now be copied from the previous release to the new release using `copied_files` and `copied_dirs`
* Yarn, npm and composer are now invoke in less verbose mode
* Composer now have the `--no-dev` option by default
* Drop support for bower and grunt
* Assets are now built in their own step (`deploy:build`)

## `composer.json`

Upgrade this package with composer: `composer require --dev exolnet/laravel-envoy:"~1.0.0@rc"`

## `config/deploy.php`

Update the following in your `config/deploy.php` file:

1. Replace `server` with `ssh_host` and `ssh_user`
2. Rename `deploy_to` to `deploy_path`
3. Rename `repo_url` to `repository_url`
4. Remove `repo_branch`
5. Remove `tmp_dir`

## `Envoy.blade.php`

Update the following in your `Envoy.blade.php` file:

1. Replace `@include(...)` with `@import('exolnet/laravel-envoy')`
2. If a pre-deployment backup is performed, move it in `@task('deploy:backup')`
3. Remove any commands for building assets or override `@task('deploy:build')`
4. Move any publishing/post-deployment commands in `@task('deploy:publish')`

## Setup and deploy

1. Run `vendor/bin/envoy run setup` to setup the bare git repository
2. Run `vendor/bin/envoy run deploy --commit=abcdef` to commit `abcdef`
