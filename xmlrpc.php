<?php

include_once "includes/bootstrap.inc";
include_once "includes/common.inc";
include_once "includes/xmlrpcs.inc";

$functions = module_invoke_all("xmlrpc");

$server = new xmlrpc_server($functions);

?>
