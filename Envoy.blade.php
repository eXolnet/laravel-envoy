@setup
	$baseCwd    = isset($__current_cwd) ? $__current_cwd : getcwd();
	$configFile = $baseCwd .'/'. (isset($configFile) ? $configFile : 'app/config/deploy.php');

	if ( ! file_exists($configFile)) {
		throw new Exception('Config file '. $configFile .' not found.');
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
	$commitHash   = isset($commit) ? $commit : array_get($config, 'commit_hash');
	$linkedFiles  = array_get($config, 'linked_files', []);
	$linkedDirs   = array_get($config, 'linked_dirs', []);
	$keepReleases = array_get($config, 'keep_releases', 5);
	$tmp_dir      = array_get($config, 'tmp_dir', '/tmp');
	$cmdGit       = array_get($config, 'cmd_git', 'git');
	$cmdNpm       = array_get($config, 'cmd_npm', 'npm');
	$cmdBower     = array_get($config, 'cmd_bower', 'bower');
	$cmdGrunt     = array_get($config, 'cmd_grunt', 'grunt');
	$cmdWget      = array_get($config, 'cmd_wget', 'wget');
	$cmdPhp       = array_get($config, 'cmd_php', 'php');

	if ( ! $server) {
		throw new Exception('Server URL is not defined for environment '. $environment .'.');
	} elseif ( ! $repoUrl) {
		throw new Exception('Repository URL is not defined for environment '. $environment .'.');
	} elseif ( ! $commitHash) {
		throw new Exception('No commit hash/tag was provided. Please provide one using --commit.');
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

	if [ -f "package.json" ]; then
		{{ $cmdNpm }} install 2>&1
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

		{{ $cmdPhp }} composer.phar install --verbose --prefer-dist --optimize-autoloader --no-progress --no-interaction 2>&1
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
	$endOn     = microtime(true);
	$totalTime = $endOn - $beginOn;

	if ($task === 'deploy:symlink' && $slack) {
		$channel = array_get($slack, 'channel', '#deployments');

		@slack($slack['url'], $channel, $name . ' @ ' . $commitHash .' - Deployed to _'. $environment .'_ after '. round($totalTime, 1) .' sec.')
	}
@endafter
