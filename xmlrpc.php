<?php
// $Id: xmlrpc.php,v 1.11 2005/07/13 18:42:25 dries Exp $

/**
 * @file
 * PHP page for handling incoming XML-RPC requests from clients.
 */

include_once 'includes/bootstrap.inc';
drupal_bootstrap('full');
include_once 'includes/xmlrpcs.inc';

xmlrpc_server(module_invoke_all('xmlrpc'));
?>
