@setup
    $deploy      = new Exolnet\Envoy\ConfigDeploy(get_defined_vars());
    $environment = $deploy->getEnvironment();

    // Prepare configuration
    $commitHash   = isset($commit) ? $commit : $environment->get('commit_hash');
    $server       = $environment->get('server');
    $sshOptions   = $environment->get('ssh_options', '');
    $repoUrl      = $environment->get('repo_url');
    $repoTree     = $environment->get('repo_tree');
    $linkedFiles  = $environment->get('linked_files', []);
    $linkedDirs   = $environment->get('linked_dirs', []);
    $keepReleases = $environment->get('keep_releases', 5);
    $tmp_dir      = $environment->get('tmp_dir', '/tmp');
    $cmdGit       = $environment->get('cmd_git', 'git');
    $cmdNpm       = $environment->get('cmd_npm', 'npm');
    $cmdYarn      = $environment->get('cmd_npm', 'yarn');
    $cmdBower     = $environment->get('cmd_bower', 'bower');
    $cmdGrunt     = $environment->get('cmd_grunt', 'grunt');
    $cmdWget      = $environment->get('cmd_wget', 'wget');
    $cmdPhp       = $environment->get('cmd_php', 'php');

    $additionalComposerFlags = $environment->get('additional_composer_flags', '');

    // Define paths
    $repoPath     = $environment->getDeployPath('repo');
    $currentPath  = $environment->getDeployPath('current');
    $releasesPath = $environment->getDeployPath('releases');
    $sharedPath   = $environment->getDeployPath('shared');
    $backupsPath  = $environment->getDeployPath('backups');
    $releasePath  = $environment->getDeployReleasePath(isset($release) ? $release : null);
@endsetup

@servers(['web' => '-q -A '. $sshOptions .' "'. $server .'"'])

@task('deploy:setup')
    if [ ! -d "{{ $releasesPath }}" ]; then
        mkdir "{{ $releasesPath }}";
    fi

    if [ ! -d "{{ $sharedPath }}" ]; then
        mkdir "{{ $sharedPath }}";
    fi

    if [ ! -d "{{ $backupsPath }}" ]; then
        mkdir "{{ $backupsPath }}";
    fi
@endtask

@task('deploy:check')
    if [ ! -d "{{ $releasesPath }}" ]; then
        echo "Releases path not found." 1>&2;
        exit 1
    fi

    if [ ! -d "{{ $sharedPath }}" ]; then
        echo "Shared path not found." 1>&2;
        exit 1
    fi

    if [ ! -d "{{ $backupsPath }}" ]; then
        echo "Backups path not found." 1>&2;
        exit 1
    fi
@endtask

@macro('deploy')
    deploy:assert_commit
    deploy:starting
    deploy:check
    deploy:started
    deploy:updating
    deploy:update_code
    deploy:release
    deploy:shared
    deploy:vendors
    deploy:compile_assets
    deploy:updated
    deploy:publishing
    deploy:symlink
    deploy:published
    deploy:finishing
    deploy:cleanup
    deploy:finished
@endmacro

@macro('deploy:rollback')
    deploy:check
    deploy:revert_release
@endmacro

@task('deploy:assert_commit')
    @if (! $commitHash)
        echo "No commit hash/tag was provided. Please provide one using --commit." 1>&2;
        exit 1
    @else
        echo "Deploying commit {{ $commitHash }}..."
    @endif
@endtask

@task('deploy:update_code')
    export GIT_SSH_COMMAND="ssh -o PasswordAuthentication=no -o StrictHostKeyChecking=no"

    if [ -d "{{ $repoPath }}" ]; then
        cd "{{ $repoPath }}"
        {{ $cmdGit }} fetch
    else
        {{ $cmdGit }} clone {{ $repoUrl }} "{{ $repoPath }}"
    fi

    cd "{{ $repoPath }}"

    {{ $cmdGit }} checkout -f {{ $commitHash }}

    {{ $cmdGit }} submodule update --init

    {{ $cmdGit }} rev-list --max-count=1 --abbrev-commit HEAD > REVISION
@endtask

@task('deploy:revert_release')
    @if (isset($release))
        RELEASE="{{ $release }}"
    @else
        cd "{{ $releasesPath }}"
        RELEASE=`ls -1d */ | head -n -1 | tail -n 1 | sed "s/\/$//"`
    @endif

    if [ ! -d "{{ $repoPath }}" ]; then
        echo "Release $RELEASE not found. Could not rollback."
        exit 1
    fi

    echo "Rollback to release $RELEASE"
    echo "TODO — Rollback migrations"

    ln -sfn "{{ $releasesPath }}/$RELEASE" "{{ $currentPath }}"
@endtask

@task('deploy:release')
    rsync -avz --exclude .git/ "{{ $repoPath }}/{{ trim($repoTree) }}/" "{{ $releasePath }}"
@endtask

@task('deploy:shared')
    @run('deploy:shared:dirs')
    @run('deploy:shared:files')
@endtask

@task('deploy:shared:dirs')
    @foreach ($linkedDirs as $dir)
        mkdir -p `dirname "{{ $sharedPath }}/{{ $dir }}"`

        if [ -d "{{ $releasePath }}/{{ $dir }}" ]; then
            if [ ! -d "{{ $sharedPath }}/{{ $dir }}" ]; then
                cp -R "{{ $releasePath }}/{{ $dir }}" "{{ $sharedPath }}/{{ $dir }}"
            fi

            rm -Rf "{{ $releasePath }}/{{ $dir }}"
        fi

        mkdir -p "{{ $sharedPath }}/{{ $dir }}"

        mkdir -p `dirname "{{ $releasePath }}/{{ $dir }}"`

        ln -nfs "{{ $sharedPath }}/{{ $dir }}" "{{ $releasePath }}/{{ $dir }}"
    @endforeach
@endtask

@task('deploy:shared:files')
    @foreach ($linkedFiles as $file)
        mkdir -p `dirname "{{ $sharedPath }}/{{ $file }}"`

        if [ -f "{{ $releasePath }}/{{ $file }}" ]; then
            if [ ! -f "{{ $sharedPath }}/{{ $file }}" ]; then
                cp "{{ $releasePath }}/{{ $file }}" "{{ $sharedPath }}/{{ $file }}"
            fi

            rm -f "{{ $releasePath }}/{{ $file }}"
        fi

        ln -nfs "{{ $sharedPath }}/{{ $file }}" "{{ $releasePath }}/{{ $file }}"
    @endforeach
@endtask

@task('deploy:vendors')
    cd "{{ $releasePath }}"

    if [ -f "package.json" ]; then
        if [ -f "yarn.lock" ]; then
            {{ $cmdYarn }} install --verbose --no-progress --non-interactive 2>&1
        else
            {{ $cmdNpm }} install 2>&1
        fi
    fi

    if [ -f "bower.json" ]; then
        {{ $cmdBower }} install 2>&1
    fi

    if [ -f "composer.json" ]; then
        if [ ! -f "composer.phar" ]; then
            {{ $cmdWget }} -nc https://getcomposer.org/composer.phar 2>&1
        else
            {{ $cmdPhp }} composer.phar self-update 2>&1
        fi

        {{ $cmdPhp }} composer.phar install {{ $additionalComposerFlags }} --verbose --prefer-dist --optimize-autoloader --no-progress --no-interaction 2>&1
    fi
@endtask

@task('deploy:compile_assets')
    cd "{{ $releasePath }}"

    if [ -f "Gruntfile.js" ]; then
        {{ $cmdGrunt }} build:release
    fi
@endtask

@task('deploy:symlink')
    ln -sfn "{{ $releasePath }}" "{{ $currentPath }}"
@endtask

@task('deploy:releases')
    ls "{{ $releasesPath }}"
@endtask

@task('deploy:cleanup')
    cd "{{ $releasesPath }}"
    ls -1d */ | head -n -{{ $keepReleases }} | xargs -d "\n" rm -Rf
@endtask

@task('backup:create')
@endtask

@task('backup:list')
    ls "{{ $backupsPath }}"
@endtask

@task('backup:restore')
@endtask

@task('deploy:starting')
    echo "deploy:starting"
@endtask

@task('deploy:started')
    echo "deploy:started"
@endtask

@task('deploy:updating')
    echo "deploy:updating"
@endtask

@task('deploy:updated')
    echo "deploy:updated"
@endtask

@task('deploy:publishing')
    echo "deploy:publishing"
@endtask

@task('deploy:published')
    echo "deploy:published"
@endtask

@task('deploy:finishing')
    echo "deploy:finishing"
@endtask

@task('deploy:finished')
    echo "deploy:finished"
@endtask

@error
    if ($task === 'deploy:check') {
        throw new Exception('Unmet prerequisites to deploy. Have you run deploy:setup?');
    } else {
        throw new Exception('Whoops, looks like something went wrong');
    }
@enderror

@after
    if ($task === 'deploy:symlink' && $deploy->has('slack')) {
        $slackUrl     = $deploy->get('slack.url');
        $slackChannel = $deploy->get('slack.channel', '#deployments');
        $slackMessage = $deploy->getName() . ' @ ' . $commitHash .' - Deployed to _'. $environment->getName() .'_ after '. round($deploy->getTimeTotal(), 1) .' sec.';

        @slack($slackUrl, $slackChannel, $slackMessage)
    }
@endafter
