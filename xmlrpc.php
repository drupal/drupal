<?php
// $Id: xmlrpc.php,v 1.7 2004/08/04 20:36:22 dries Exp $

include_once "includes/bootstrap.inc";
include_once "includes/common.inc";
include_once 'includes/xmlrpc.inc';
include_once "includes/xmlrpcs.inc";

$functions = module_invoke_all("xmlrpc");

$server = new xmlrpc_server($functions);

?>
