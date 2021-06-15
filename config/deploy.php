<?php

return [
    /**
     * @mandatory The name of the application.
     */
    'name' => 'untitled',

    /**
     * @mandatory The environment to use by default.
     */
    'default' => 'production',

    /**
     * @mandatory Environments definitions.
     */
    'environments' => [

        'production' => [

            /**
             * @mandatory SSH hostname to use to connect to the server.
             */
            'ssh_host' => 'hostname',

            /**
             * @mandatory SSH user to use to connect to the server.
             */
            'ssh_user' => 'user',

            /**
             * @optional Additional SSH options.
             */
            'ssh_options' => '',

            /**
             * @mandatory The path on the remote server where the application should be deployed.
             */
            'deploy_path' => '/deployment/path',

            /**
             * @optional The relative path in the project where the assets need to be built.
             */
            'assets_path' => '',

            /**
             * @mandatory URL to the repository.
             */
            'repository_url' => 'ssh://git@hostname/repository.git',

            /**
             * @optional Listed files will be symlinked into each release directory.
             */
            'linked_files' => ['.env'],

            /**
             * @optional Listed directories will be symlinked into the release directory.
             */
            'linked_dirs' => ['storage/app', 'storage/framework', 'storage/logs'],

            /**
             * @optional Listed files will be copied from the current release into each release directory.
             */
            'copied_files' => [],

            /**
             * @optional Listed directories will be copied from the current release into the release directory.
             */
            'copied_dirs' => ['node_modules', 'vendor'],

            /**
             * @optional Listed cronjobs will be installed to the user crontab during deployment.
             */
            'cron_jobs' => [
              // ┌────────────── minute (0 - 59)
              // │  ┌─────────── hour (0 - 23)
              // │  │  ┌──────── day of month (1 - 31)
              // │  │  │  ┌───── month (1 - 12)
              // │  │  │  │  ┌── day of week (0 - 6) (Sunday = 0 or 7)
              // │  │  │  │  │
              // *  *  *  *  *   command
                '*  *  *  *  *   php /path/to/artisan schedule:run >> /dev/null 2>&1',
            ],

            /**
             * @optional Email for cron notifications.
             */
            'cron_mailto' => 'user@example.com',

            /**
             * @optional The last n releases are kept for possible rollbacks.
             */
            'keep_releases' => 5,

            /**
             * @optional Binary to use to invoke git.
             */
            'cmd_git' => 'git',

            /**
             * @optional Binary to use to invoke npm.
             */
            'cmd_npm' => 'npm',

            /**
             * @optional Binary to use to invoke yarn.
             */
            'cmd_yarn' => 'yarn',

            /**
             * @optional Binary to use to invoke bower.
             */
            'cmd_bower' => 'bower',

            /**
             * @optional Binary to use to invoke grunt.
             */
            'cmd_grunt' => 'grunt',

            /**
             * @optional Binary to use to invoke php.
             */
            'cmd_php' => 'php',

            /**
             * @optional Binary to use to invoke composer.
             */
            'cmd_composer' => 'composer',

            /**
             * @optional Additional composer options.
             */
            'cmd_composer_options' => '--no-dev'
        ],
    ],

    /**
     * @optional Slack notification configuration on deployment.
     */
    'slack' => [

        /**
         * @mandatory Slack URL for notifications.
         */
        'url' => 'https://hooks.slack.com/services/XXXXXX/YYYYYY/ZZZZZZ',

        /**
         * @optional Slack channel where to send notifications.
         */
        // 'channel' => '#deployments',
    ],
];
