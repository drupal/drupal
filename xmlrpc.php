<?php
// $Id: xmlrpc.php,v 1.6 2004/01/26 18:51:37 dries Exp $

include_once "includes/bootstrap.inc";
include_once "includes/common.inc";
include_once "includes/xmlrpcs.inc";

$functions = module_invoke_all("xmlrpc");

$server = new xmlrpc_server($functions);

?>
