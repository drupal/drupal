<?php

$file = __DIR__.'/../vendor/autoload.php';
if (!file_exists($file)) {
    throw new RuntimeException('Install dependencies to run test suite.');
}

require_once $file;

spl_autoload_register(function ($class) {
    if (0 === strpos($class, 'Symfony\Cmf\Component\Routing\\')) {
        $path = __DIR__.'/../'.implode('/', array_slice(explode('\\', $class), 4)).'.php';
        if (!stream_resolve_include_path($path)) {
            return false;
        }
        require_once $path;

        return true;
    }
});
