<?php
// $Id: xmlrpc.php,v 1.4 2003/09/14 18:33:16 dries Exp $

include_once "includes/xmlrpcs.inc";
include_once "includes/common.inc";

$functions = module_invoke_all("xmlrpc");

$server = new xmlrpc_server($functions);

?>
