<?php

include_once "includes/common.inc";
if (variable_get(dev_timing, 0)) timer_start();
module_execute($mod, "page");
if (variable_get(dev_timing, 0)) timer_print();

?>
