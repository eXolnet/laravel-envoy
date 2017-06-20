<?php

$__current_cwd = getcwd();
chdir(__DIR__);

$__data['__current_cwd'] = $__current_cwd;

$__envoyPath = $__container->writeCompiledEnvoyFile(
	$__compiler, __DIR__.'/Envoy.blade.php', $__serversOnly
) ?: getcwd().'/Envoy.php';

include $__envoyPath;

@unlink($__envoyPath);

chdir($__current_cwd);
