<?php
// $Id: xmlrpc.php,v 1.5 2003/11/18 19:44:35 dries Exp $

include_once "includes/xmlrpcs.inc";
include_once "includes/bootstrap.inc";
include_once "includes/common.inc";

$functions = module_invoke_all("xmlrpc");

$server = new xmlrpc_server($functions);

?>
