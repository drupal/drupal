<?php

include_once "includes/xmlrpcs.inc";
include_once "includes/common.inc";

$functions = module_invoke_all("xmlrpc");

$server = new xmlrpc_server($functions);

?>
