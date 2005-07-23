<?php
// $Id: xmlrpc.php,v 1.12 2005/07/23 05:57:27 dries Exp $

/**
 * @file
 * PHP page for handling incoming XML-RPC requests from clients.
 */

include_once 'includes/bootstrap.inc';
drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);
include_once 'includes/xmlrpcs.inc';

xmlrpc_server(module_invoke_all('xmlrpc'));
?>
