<?php

define('DRUPAL_ROOT', getcwd());
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

include_once DRUPAL_ROOT . '/includes/bootstrap.inc';
drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);

config_write_signed_file_storage_key();
//echo config_sign_data('onetwothree');
$sfs = new SignedFileStorage('one.two.json');

// Write and read
$sfs->write('nothing');
echo $sfs->read() . PHP_EOL;
$existing_content = file_get_contents($sfs->getPath());
echo $sfs->getPath() . PHP_EOL;

// Modify and resign
file_put_contents($sfs->getPath(), $existing_content . 'extra');
$sfs->resign();
echo $sfs->read() . PHP_EOL;

// Fail
file_put_contents($sfs->getPath(), $existing_content . 'extra');
echo $sfs->read() . PHP_EOL;
