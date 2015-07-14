@setup
	$configFile   = getcwd().'/app/config/deploy.php';

	if ( ! file_exists($configFile)) {
		throw new Exception('Config file app/config/deploy.php not found.');
	}

	$deployConfig = include($configFile);
	$environment  = isset($env) ? $env : array_get($deployConfig, 'default');
	$beginOn      = microtime(true);

	$name   = array_get($deployConfig, 'name', 'untitled');
	$slack  = array_get($deployConfig, 'slack');
	$config = array_get($deployConfig['environments'], $environment);

	// Get configuration
	$server       = array_get($config, 'server');
	$deployTo     = array_get($config, 'deploy_to', '');
	$repoUrl      = array_get($config, 'repo_url');
	$repoBranch   = array_get($config, 'repo_branch', 'master');
	$repoTree     = array_get($config, 'repo_tree', '');
	$linkedFiles  = array_get($config, 'linked_files', []);
	$linkedDirs   = array_get($config, 'linked_dirs', []);
	$keepReleases = array_get($config, 'keep_releases', 5);
	$tmp_dir      = array_get($config, 'tmp_dir', '/tmp');
	$cmdNpm       = array_get($config, 'cmd_npm', 'npm');
	$cmdBower     = array_get($config, 'cmd_bower', 'bower');
	$cmdGrunt     = array_get($config, 'cmd_grunt', 'grunt');
	$cmdWget      = array_get($config, 'cmd_wget', 'wget');
	$cmdPhp       = array_get($config, 'cmd_php', 'php');

	if ( ! $server) {
		throw new Exception('Server URL is not defined for environment '. $environment .'.');
	} elseif ( ! $repoUrl) {
		throw new Exception('Repository URL is not defined for environment '. $environment .'.');
	}

	// Define paths
	$deployTo     = rtrim($deployTo, '/');
	$repoPath     = $deployTo .'/repo';
	$currentPath  = $deployTo .'/current';
	$releasesPath = $deployTo .'/releases';
	$sharedPath   = $deployTo .'/shared';
	$backupsPath  = $deployTo .'/backups';

	$releasePath  = $releasesPath .'/'. (isset($release) ? $release : date('YmdHis'));
@endsetup

@servers(['web' => '-A "'. $server .'"'])

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
		echo "Releases path not found."
		exit 1
	fi

	if [ ! -d "{{ $sharedPath }}" ]; then
		echo "Shared path not found."
		exit 1
	fi

	if [ ! -d "{{ $backupsPath }}" ]; then
		echo "Backups path not found."
		exit 1
	fi
@endtask

@macro('deploy')
	deploy:check
	deploy:update_code
	deploy:release
	deploy:shared
	deploy:vendors
	deploy:migrate
	deploy:compile_assets
	deploy:symlink
	deploy:cleanup
@endmacro

@macro('deploy:rollback')
	deploy:check
	deploy:revert_release
@endmacro

@task('deploy:update_code')
	if [ -d "{{ $repoPath }}" ]; then
		rm -Rf "{{ $repoPath }}"
	fi

	export GIT_SSH_COMMAND="ssh -o PasswordAuthentication=no -o StrictHostKeyChecking=no"

	git clone -b {{ $repoBranch }} --depth 1 --recursive -q {{ $repoUrl }} "{{ $repoPath }}"

	cd "{{ $repoPath }}"
	git rev-list --max-count=1 --abbrev-commit {{ $repoBranch }} > REVISION
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
	rsync -avz --exclude .git/ "{{ $repoPath }}/" "{{ $releasePath }}"
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

	{{ $cmdNpm }} install
	{{ $cmdBower }} install
	{{ $cmdGrunt }} build:release

	if [ ! -f "composer.phar" ]; then
		{{ $cmdWget }} -nc https://getcomposer.org/composer.phar
	else
		{{ $cmdPhp }} composer.phar self-update
	fi

	{{ $cmdPhp }} composer.phar install --verbose --prefer-dist --optimize-autoloader --no-progress --no-interaction
@endtask

@task('deploy:migrate')
	cd "{{ $releasePath }}" && php artisan migrate
@endtask

@task('deploy:compile_assets')
	cd "{{ $releasePath }}" && grunt build:release
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

@error
	if ($task === 'deploy:check') {
		throw new Exception('Unmet prerequisites to deploy. Have you run deploy:setup?');
	}
@enderror

@after
	$endOn     = microtime(true);
	$totalTime = $endOn - $beginOn;

	if ($task === 'deploy:symlink' && $slack) {
    $channel = array_get($slack, 'channel', '#deployments');

		@slack($slack['url'], $channel, $name .' - Deployed to _'. $environment .'_ after '. round($totalTime, 1) .' sec.')
	}
@endafter
