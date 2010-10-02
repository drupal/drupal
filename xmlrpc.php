<?php
// $Id: xmlrpc.php,v 1.20 2010/10/02 01:22:41 dries Exp $

/**
 * @file
 * PHP page for handling incoming XML-RPC requests from clients.
 */

/**
 * Root directory of Drupal installation.
 */
define('DRUPAL_ROOT', getcwd());

include_once DRUPAL_ROOT . '/includes/bootstrap.inc';
drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);
include_once DRUPAL_ROOT . '/includes/xmlrpc.inc';
include_once DRUPAL_ROOT . '/includes/xmlrpcs.inc';

xmlrpc_server(module_invoke_all('xmlrpc'));
