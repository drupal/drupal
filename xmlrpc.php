<?php
// $Id: xmlrpc.php,v 1.8 2004/08/12 18:00:06 dries Exp $

include_once 'includes/bootstrap.inc';
include_once 'includes/common.inc';
include_once 'includes/xmlrpc.inc';
include_once 'includes/xmlrpcs.inc';

$functions = module_invoke_all('xmlrpc');

$server = new xmlrpc_server($functions);

?>
