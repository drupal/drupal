<?php

include_once "includes/common.inc";

$uri = parse_url($REQUEST_URI);

foreach (module_list() as $module) module_invoke($module, "export", $uri["query"]);

?>