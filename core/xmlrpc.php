<?php

/**
 * @file
 * PHP page for handling incoming XML-RPC requests from clients.
 */

// Change the directory to the Drupal root.
chdir('..');

/**
 * Root directory of Drupal installation.
 */
define('DRUPAL_ROOT', getcwd());

include_once DRUPAL_ROOT . '/core/includes/bootstrap.inc';
drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);
include_once DRUPAL_ROOT . '/core/includes/xmlrpc.inc';
include_once DRUPAL_ROOT . '/core/includes/xmlrpcs.inc';

xmlrpc_server(module_invoke_all('xmlrpc'));
