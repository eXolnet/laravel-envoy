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
