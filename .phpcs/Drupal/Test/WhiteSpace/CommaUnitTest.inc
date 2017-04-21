<?php

$x = array('uid','rid');

function foo($bar,$x) {}
function foo2($bar,  $x) {}

foo(1,2);
foo(1,  2);

list(,,$bundle) = entity_extract_ids('node', $entity);