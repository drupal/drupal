<?php
// $Id: xmlrpc.php,v 1.3 2001/10/20 20:45:05 kjartan Exp $

include_once "includes/xmlrpcs.inc";
include_once "includes/common.inc";

$functions = array();

foreach (module_list() as $name) {
  if (module_hook($name, "xmlrpc")) {
    $functions = array_merge($functions, module_invoke($name, "xmlrpc"));
  }
}

$server = new xmlrpc_server($functions);

?>
