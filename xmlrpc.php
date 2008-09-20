<?php
// $Id: xmlrpc.php,v 1.16 2008/09/20 20:22:23 webchick Exp $

/**
 * @file
 * PHP page for handling incoming XML-RPC requests from clients.
 */

/**
 * Root directory of Drupal installation.
 */
define('DRUPAL_ROOT', dirname(realpath(__FILE__)));

include_once DRUPAL_ROOT . '/includes/bootstrap.inc';
drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);
include_once DRUPAL_ROOT . '/includes/xmlrpc.inc';
include_once DRUPAL_ROOT . '/includes/xmlrpcs.inc';

xmlrpc_server(module_invoke_all('xmlrpc'));
