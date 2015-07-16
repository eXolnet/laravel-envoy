<?php

$__current_cwd = getcwd();
chdir(__DIR__);

$__data['__current_cwd'] = $__current_cwd;

$__container->writeCompiledEnvoyFile(
	$__compiler, __DIR__.'/Envoy.blade.php', $__serversOnly
);

include getcwd().'/Envoy.php';

@unlink(getcwd().'/Envoy.php');

chdir($__current_cwd);
