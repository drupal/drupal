<?php

namespace app;

$app = new \Silex\Application();
$app->register(new \Silex\Provider\SessionServiceProvider());

$def = realpath(__DIR__.'/../vendor/behat/mink/driver-testsuite/web-fixtures');
$ovr = realpath(__DIR__.'/web-fixtures');
$cbk = function ($file) use ($app, $def, $ovr) {
    $file = str_replace('.file', '.php', $file);
    $path = file_exists($ovr.'/'.$file) ? $ovr.'/'.$file : $def.'/'.$file;
    $resp = null;

    ob_start();
    include($path);
    $content = ob_get_clean();

    if ($resp) {
        if ('' === $resp->getContent()) {
            $resp->setContent($content);
        }

        return $resp;
    }

    return $content;
};

$app->get('/{file}', $cbk)->assert('file', '.*');
$app->post('/{file}', $cbk)->assert('file', '.*');

$app['debug'] = true;
$app['exception_handler']->disable();
$app['session.test'] = true;

return $app;
