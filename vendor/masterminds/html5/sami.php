<?php

use Sami\Sami;

return new Sami(__DIR__ . '/src' , array(
    'title'                => 'HTML5-PHP API',
    'build_dir'            => __DIR__.'/build/apidoc',
    'cache_dir'            => __DIR__.'/build/sami-cache',
    'default_opened_level' => 1,
));