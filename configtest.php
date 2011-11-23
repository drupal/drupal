<?php

define('DRUPAL_ROOT', getcwd());
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

include_once DRUPAL_ROOT . '/includes/bootstrap.inc';
drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);

$config = config('foo.bar');
$config->foo = 'bar';
$config->save();
echo config('foo.bar')->foo;
echo '<br>That should be bar';
die();

//echo config_sign_data('onetwothree');
$sfs = new SignedFileStorage('one.two');

// Write and read
$sfs->write('nothing');
echo $sfs->read() . PHP_EOL;
$existing_content = file_get_contents($sfs->getFilePath());
echo $sfs->getFilePath() . PHP_EOL;

// Modify and resign
file_put_contents($sfs->getFilePath(), $existing_content . 'extra');
$sfs->resign();
echo $sfs->read() . PHP_EOL;

// Fail
file_put_contents($sfs->getFilePath(), $existing_content . 'extra');
echo $sfs->read() . PHP_EOL;
print_r(config_get_signed_file_storage_names_with_prefix());

print '<hr>';

