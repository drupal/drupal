<?php
// $Id: xmlrpc.php,v 1.10 2005/06/22 20:19:56 dries Exp $

/**
 * @file
 * PHP page for handling incoming XML-RPC requests from clients.
 */

include_once 'includes/bootstrap.inc';
drupal_bootstrap('full');
include_once 'includes/xmlrpcs.inc';

$functions = module_invoke_all('xmlrpc');

$server = new xmlrpc_server($functions);

?>
