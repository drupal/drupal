<?php
// $Id: xmlrpc.php,v 1.9 2004/08/21 06:42:34 dries Exp $

/**
 * @file
 * PHP page for handling incoming XML-RPC requests from clients.
 */

include_once 'includes/bootstrap.inc';
include_once 'includes/common.inc';
include_once 'includes/xmlrpc.inc';
include_once 'includes/xmlrpcs.inc';

$functions = module_invoke_all('xmlrpc');

$server = new xmlrpc_server($functions);

?>
