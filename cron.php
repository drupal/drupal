<?php

include_once "includes/common.inc";

foreach (module_list() as $module) module_invoke($module, "cron");

?>