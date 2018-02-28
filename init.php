<?php

$__current_cwd = getcwd();
chdir(__DIR__);

$__data['__current_cwd'] = $__current_cwd;

$__PackageEnvoyPath = $__container->writeCompiledEnvoyFile(
	$__compiler, __DIR__.'/Envoy.blade.php', $__serversOnly
) ?: getcwd().'/Envoy.php';

include $__PackageEnvoyPath;

@unlink($__PackageEnvoyPath);

chdir($__current_cwd);
