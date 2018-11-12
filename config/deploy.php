<?php

return [
    /**
     * The name of the application.
     */
    'name' => 'untitled',

    /**
     * The environment to use by default.
     */
    'default' => 'production',

    /**
     * Environments/servers definitions
     */
    'environments' => [
        'production' => [
            /**
             * SSH url to the server.
             */
            'server' => 'user@hostname',

            /**
             * Additional SSH options.
             */
            'ssh_options' => '',

            /**
             * The path on the remote server where the application should be deployed.
             */
            'deploy_to' => '/deployment/path',

            /**
             * URL to the repository.
             */
            'repo_url' => 'ssh://git@hostname/repository.git',

            /**
             * The branch name to be deployed from SCM.
             */
            'repo_branch' => 'master',

            /**
             * The subtree of the repository to deploy.
             */
            'repo_tree' => '',

            /**
             * Listed files will be symlinked into each release directory during deployment.
             */
            'linked_files' => ['.env.php'],

            /**
             * Listed directories will be symlinked into the release directory during deployment.
             */
            'linked_dirs' => ['app/storage/cache', 'app/storage/logs', 'app/storage/sessions'],

            /**
             * Listed cronjobs will be installed to the user crontab during deployment.
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
             * Email for cron notifications.
             */
            'cron_mailto' => 'user@example.com',

            /**
             * The last n releases are kept for possible rollbacks.
             */
            'keep_releases' => 5,

            /**
             * Temporary directory used during deployments to store data.
             */
            'tmp_dir' => '/tmp',
        ],
    ],

    /**
     * Slack notification configuration on deployment.
     */
    'slack' => [
        /**
         * Slack URL for notifications.
         */
        'url'     => 'https://hooks.slack.com/services/XXXXXX/YYYYYY/ZZZZZZ',

        /**
         * Slack channel where to send notifications.
         */
        'channel' => '#deployments',
    ],
];
