<?php
// $Id: xmlrpc.php,v 1.14 2005/08/28 18:26:20 dries Exp $

/**
 * @file
 * PHP page for handling incoming XML-RPC requests from clients.
 */

include_once './includes/bootstrap.inc';
drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);
include_once './includes/xmlrpc.inc';
include_once './includes/xmlrpcs.inc';

xmlrpc_server(module_invoke_all('xmlrpc'));
